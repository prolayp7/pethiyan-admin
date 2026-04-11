<?php

namespace App\Http\Controllers;

use App\Enums\AdminPermissionEnum;
use App\Enums\Category\CategoryBackgroundTypeEnum;
use App\Enums\CategoryStatusEnum;
use App\Enums\SpatieMediaCollectionName;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\ImageWebpService;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use BackedEnum;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use ChecksPermissions, PanelAware, AuthorizesRequests;

    protected bool $editPermission = false;
    protected bool $deletePermission = false;
    protected bool $createPermission = false;

    // Define media collections for easier management
    private array $mediaCollections = [
        'image' => SpatieMediaCollectionName::CATEGORY_IMAGE,
        'banner' => SpatieMediaCollectionName::CATEGORY_BANNER,
        'icon' => SpatieMediaCollectionName::CATEGORY_ICON,
        'active_icon' => SpatieMediaCollectionName::CATEGORY_ACTIVE_ICON,
        'background_image' => SpatieMediaCollectionName::CATEGORY_BACKGROUND_IMAGE,
    ];

    public function __construct()
    {
        if ($this->getPanel() === 'admin') {
            $this->editPermission = $this->hasPermission(AdminPermissionEnum::CATEGORY_EDIT->value);
            $this->deletePermission = $this->hasPermission(AdminPermissionEnum::CATEGORY_DELETE->value);
            $this->createPermission = $this->hasPermission(AdminPermissionEnum::CATEGORY_CREATE->value);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Category::class);

        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'title', 'name' => 'title', 'title' => __('labels.title')],
            ['data' => 'image', 'name' => 'image', 'title' => __('labels.image')],
            ['data' => 'parent', 'name' => 'parent', 'title' => __('labels.parent')],

            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status')],

            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];

        $editPermission = $this->editPermission;
        $createPermission = $this->createPermission;

        return view($this->panelView('categories.index'), compact('columns', 'editPermission', 'createPermission'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        // Guard: detect if PHP silently dropped uploaded files due to upload_max_filesize
        if ($this->uploadExceededPhpLimit($request)) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'messages.upload_too_large',
                data: ['error' => 'The uploaded file exceeds the server upload limit. Max allowed: ' . ini_get('upload_max_filesize')],
                status: 422
            );
        }

        try {
            $this->authorize('create', Category::class);
            $validated = $request->validated();

            // Set default values
            if (empty($request->status)) {
                $validated['status'] = CategoryStatusEnum::INACTIVE();
            }
            if (empty($request->requires_approval)) {
                $validated['requires_approval'] = false;
            }

            $category = Category::create($validated);

            // Handle file uploads for creation
            $this->handleFileUploadsForStore($request, $category);
            $this->handleSeoImageUploads($request, $category);
            $category->refresh();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.category_created_successfully',
                data: $category,
                status: 201
            );
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.validation_failed',
                data: $e->errors(),
                status: 422
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.permission_denied',
                data: [],
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        try {
            $category = Category::findOrFail($id);
            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.category_retrieved_successfully',
                data: new CategoryResource($category->load('parent'))
            );
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.category_not_found'
            );
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, $id): JsonResponse
    {
        // Guard: detect if PHP silently dropped uploaded files due to upload_max_filesize
        if ($this->uploadExceededPhpLimit($request)) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'messages.upload_too_large',
                data: ['error' => 'The uploaded file exceeds the server upload limit. Max allowed: ' . ini_get('upload_max_filesize')],
                status: 422
            );
        }

        try {
            $category = Category::findOrFail($id);
            $this->authorize('update', $category);
            $validated = $request->validated();

            // Set default values
            if (isset($validated['status']) && $validated['status'] === CategoryStatusEnum::INACTIVE()) {
               if ($category->products()->exists()) {
                   return ApiResponseType::sendJsonResponse(
                       success: false,
                       message: 'messages.category_cannot_be_deactivated_with_products',
                   );
               }
               // Prevent deletion if any direct child category has products assigned
               if ($category->children()->whereHas('products')->exists()) {
                   return ApiResponseType::sendJsonResponse(
                       success: false,
                       message: 'messages.category_cannot_be_deactivated_with_products',
                   );
               }
            }
            if (empty($request->status)) {
                $validated['status'] = CategoryStatusEnum::INACTIVE();
            }
            if (empty($request->requires_approval)) {
                $validated['requires_approval'] = false;
            }

            // Handle background type logic
            $this->handleBackgroundTypeLogic($validated, $category);

            $category->update($validated);

            // Handle file uploads and removals for update
            $this->handleFileUploadsForUpdate($request, $category);
            $this->handleSeoImageUploads($request, $category);
            $category->refresh();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.category_updated_successfully',
                data: $category
            );
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.category_not_found',
                data: [],
                status: 404
            );
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.validation_failed',
                data: $e->errors(),
                status: 422
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.permission_denied',
                data: [],
            );
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {
            DB::beginTransaction();
            $category = Category::findOrFail($id);
            $this->authorize('delete', $category);
            // Prevent deletion if category has any products assigned
            if ($category->products()->exists()) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'messages.category_cannot_be_deleted_with_products',
                );
            }
            // Prevent deletion if any direct child category has products assigned
            if ($category->children()->whereHas('products')->exists()) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'messages.category_cannot_be_deleted_with_products',
                );
            }

            $category->delete();
            $category->clearMediaCollection('category');
            $category->clearMediaCollection('banner');
            $category->clearMediaCollection('icon');
            $category->clearMediaCollection('active_icon');
            $category->clearMediaCollection('background_image');
            $category->children()->update(['parent_id' => null]);
            DB::commit();
            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.category_deleted_successfully',
            );
        } catch (ModelNotFoundException) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.category_not_found',
                status: 404
            );
        } catch (AuthorizationException) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.permission_denied',
                data: [],
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: $e->getMessage(),
                data: ['error' => $e->getMessage()],
            );
        }
    }

    /**
     * Handle file uploads during category creation
     */
    private function handleFileUploadsForStore(StoreCategoryRequest $request, Category $category): void
    {
        foreach ($this->mediaCollections as $requestField => $collectionName) {
            if ($request->hasFile($requestField)) {
                $collectionValue = $this->resolveMediaCollectionName($collectionName);
                $converted = ImageWebpService::convert($request->file($requestField));
                $category->addMedia($converted['path'])
                    ->usingFileName($converted['filename'])
                    ->toMediaCollection($collectionValue);
                if ($converted['isWebp']) {
                    @unlink($converted['path']);
                }
            }
        }
    }

    /**
     * Handle file uploads and removals during category update
     */
    private function handleFileUploadsForUpdate(UpdateCategoryRequest $request, Category $category): void
    {
        foreach ($this->mediaCollections as $requestField => $collectionName) {
            $collectionValue = $this->resolveMediaCollectionName($collectionName);

            $uploadedFile = $request->file($requestField);
            if ($uploadedFile instanceof UploadedFile && $uploadedFile->isValid()) {
                // New file uploaded — replace existing media
                $this->handleSingleFileUpload($request, $category, $requestField, $collectionValue);
            }
        }
    }

    /**
     * Handle single file upload with duplicate check
     */
    private function handleSingleFileUpload(UpdateCategoryRequest $request, Category $category, string $requestField, string $collectionName): void
    {
        $newFile = $request->file($requestField);

        if (! $newFile instanceof UploadedFile || ! $newFile->isValid()) {
            return;
        }

        $category->clearMediaCollection($collectionName);

        $converted = ImageWebpService::convert($newFile);

        $category->addMedia($converted['path'])
            ->usingFileName($converted['filename'])
            ->toMediaCollection($collectionName);

        if ($converted['isWebp']) {
            @unlink($converted['path']);
        }
    }

    private function resolveMediaCollectionName(string|BackedEnum $collectionName): string
    {
        if ($collectionName instanceof BackedEnum) {
            return (string) $collectionName->value;
        }

        return $collectionName;
    }

    /**
     * Detect if PHP silently dropped uploaded files because the upload exceeded upload_max_filesize.
     * When this happens, $_FILES is empty yet Content-Length indicates data was sent.
     */
    private function uploadExceededPhpLimit(Request $request): bool
    {
        // If no files were received but the client sent a multipart body with files
        // it likely means PHP silently dropped them (UPLOAD_ERR_FORM_SIZE or server limit)
        $contentLength = (int) ($request->server('CONTENT_LENGTH', 0));
        $postMaxSize = $this->parsePhpSize(ini_get('post_max_size'));

        if ($contentLength > 0 && $contentLength > $postMaxSize && empty($_FILES)) {
            return true;
        }

        // Check if any uploaded file has UPLOAD_ERR_INI_SIZE or UPLOAD_ERR_FORM_SIZE
        foreach ($_FILES as $file) {
            $error = is_array($file['error']) ? $file['error'][0] : $file['error'];
            if (in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse a PHP size string (e.g. '20M', '1G') into bytes.
     */
    private function parsePhpSize(string $size): int
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $value = (int) $size;

        return match ($last) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    private function handleSeoImageUploads(Request $request, Category $category): void
    {
        $metadata = $category->metadata ?? [];
        $updated = false;

        foreach (['og_image', 'twitter_image'] as $field) {
            if (!$request->hasFile($field)) {
                continue;
            }

            $metadata[$field] = $request->file($field)->store('seo/category', 'public');
            $updated = true;
        }

        if ($updated) {
            $category->update([
                'metadata' => array_merge($category->metadata ?? [], $metadata),
            ]);
        }
    }

    /**
     * Remove media from collection
     */
    private function removeMediaFromCollection(Category $category, string $collectionName): void
    {
        $existingMedia = $category->getFirstMedia($collectionName);
        if ($existingMedia) {
            $existingMedia->delete();
        }
    }

    /**
     * Handle background type logic
     */
    private function handleBackgroundTypeLogic(array &$validated, Category $category): void
    {
        if (isset($validated['background_type'])) {
            if ($validated['background_type'] === CategoryBackgroundTypeEnum::IMAGE()) {
                $validated['background_color'] = null;
            }
            if ($validated['background_type'] === CategoryBackgroundTypeEnum::COLOR()) {
                $this->removeMediaFromCollection($category, SpatieMediaCollectionName::CATEGORY_BACKGROUND_IMAGE());
            }
        }
    }

    public function getCategories(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        $draw = $request->input('draw');
        $start = $request->input('start');
        $length = $request->input('length');
        $searchValue = $request->input('search.value', '');

        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'asc');

        $columns = ['id', 'title', 'description', 'status', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = Category::with('parent');

        $totalRecords = Category::count();
        $filteredRecords = $totalRecords;

        // Search filter
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('title', 'like', "%{$searchValue}%")
                    ->orWhere('description', 'like', "%{$searchValue}%")
                    ->orWhereHas('parent', function ($q) use ($searchValue) {
                        $q->where('title', 'like', "%{$searchValue}%");
                    });
            });
            $filteredRecords = $query->count();
        }

        $data = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'title' => $category->title,
                    'image' => view('partials.image', ['image' => $category->image ?? "", 'title' => $category->title])->render(),
                    'status' => view('partials.status', ['status' => $category->status ?? ""])->render(),

                    'created_at' => $category->created_at->format('Y-m-d'),
                    'parent' => $category->parent ? $category->parent->title : 'N/A',

                    'action' => view('partials.actions', [
                        'modelName' => 'category',
                        'id' => $category->id,
                        'title' => $category->title,
                        'mode' => 'model_view',
                        'editPermission' => $this->editPermission,
                        'deletePermission' => $this->deletePermission
                    ])->render(),
                ];
            })
            ->toArray();

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->input('search'); // Get the search query
        $exceptId = $request->input('exceptId'); // Get the search query
        $findId = $request->input('find_id'); // Specific category ID to find
        $type = $request->input('type'); // Specific category ID to find

        if ($findId) {
            // If find_id is set and not empty, fetch only that category
            $categories = Category::where('id', $findId)
                ->select('id', 'title')
                ->where('status', CategoryStatusEnum::ACTIVE())
                ->get();
        } else if (!empty($type) && $type == 'root') {
            $categories = Category::where('title', 'LIKE', '%' . $query . '%')
                ->select('id', 'title')
                ->where('parent_id', null)
                ->where('status', CategoryStatusEnum::ACTIVE())
                ->get();
        } else {
            // Fetch categories matching the search query
            $categories = Category::where('title', 'like', "%{$query}%")
                ->select('id', 'title') // Fetch only required fields
                ->when($exceptId, function ($q) use ($exceptId) {
                    $q->where('id', '!=', $exceptId);
                })
                ->where('status', CategoryStatusEnum::ACTIVE())
                ->get();
        }

        $results = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'value' => $category->id,
                'text' => $category->title,
            ];
        });

        // Return the categories as JSON
        return response()->json($results);
    }
}
