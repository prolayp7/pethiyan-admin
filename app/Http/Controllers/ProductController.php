<?php

namespace App\Http\Controllers;

use App\Enums\AdminPermissionEnum;
use App\Enums\CategoryStatusEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\Product\ProductStatusEnum;
use App\Enums\Product\ProductTypeEnum;
use App\Enums\Product\ProductVarificationStatusEnum;
use App\Enums\SellerPermissionEnum;
use App\Enums\SpatieMediaCollectionName;
use App\Events\Product\ProductAfterCreate;
use App\Events\Product\ProductAfterUpdate;
use App\Events\Product\ProductBeforeCreate;
use App\Http\Requests\Product\StoreUpdateProductRequest;
use App\Models\AdminUser;
use App\Models\Category;
use App\Models\GlobalProductAttribute;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StoreProductVariant;
use App\Models\Seller;
use App\Models\Store;
use App\Models\State;
use App\Models\TaxClass;
use App\Services\CategoryService;
use App\Services\GlobalAttributeService;
use App\Services\ProductService;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Services\FrontendRevalidateService;
use App\Types\Api\ApiResponseType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;
use phpDocumentor\Reflection\PseudoTypes\Numeric_;

class ProductController extends Controller
{
    use PanelAware, ChecksPermissions, AuthorizesRequests;

    public float $sellerId;
    protected bool $editPermission = false;
    protected bool $deletePermission = false;
    protected bool $createPermission = false;
    protected bool $viewPermission = false;
    protected bool $updateStatusPermission = false;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $guard = $this->getPanel() === 'seller' ? 'seller' : 'admin';
            /** @var AdminUser|\App\Models\User|null $user */
            $user = auth()->guard($guard)->user();
            $seller = $user?->seller();

            $this->sellerId = $seller ? $seller->id : 0;

            if ($this->getPanel() === 'seller') {
                $this->viewPermission = $this->hasPermission(SellerPermissionEnum::PRODUCT_VIEW->value) || ($user?->hasRole(DefaultSystemRolesEnum::SELLER()) ?? false);
                $this->editPermission = $this->hasPermission(SellerPermissionEnum::PRODUCT_EDIT->value) || ($user?->hasRole(DefaultSystemRolesEnum::SELLER()) ?? false);
                $this->deletePermission = $this->hasPermission(SellerPermissionEnum::PRODUCT_DELETE->value) || ($user?->hasRole(DefaultSystemRolesEnum::SELLER()) ?? false);
                $this->createPermission = $this->hasPermission(SellerPermissionEnum::PRODUCT_CREATE->value) || ($user?->hasRole(DefaultSystemRolesEnum::SELLER()) ?? false);
            } elseif ($this->getPanel() === 'admin') {
                $this->viewPermission = $this->hasPermission(AdminPermissionEnum::PRODUCT_VIEW->value);
                $this->createPermission = $this->hasPermission(AdminPermissionEnum::PRODUCT_CREATE->value);
                $this->editPermission = $this->hasPermission(AdminPermissionEnum::PRODUCT_EDIT->value);
                $this->deletePermission = $this->hasPermission(AdminPermissionEnum::PRODUCT_DELETE->value);
                $this->updateStatusPermission = $this->hasPermission(AdminPermissionEnum::PRODUCT_STATUS_UPDATE->value);
            }

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Product::class);

        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'title', 'name' => 'title', 'title' => __('labels.title')],
            ['data' => 'image', 'name' => 'image', 'title' => __('labels.image')],
            ['data' => 'product_type', 'name' => 'product_type', 'title' => __('labels.product_type')],
            ['data' => 'category', 'name' => 'category', 'title' => __('labels.category')],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status')],
            ['data' => 'featured', 'name' => 'featured', 'title' => __('labels.featured')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];

        $editPermission = $this->editPermission;
        $deletePermission = $this->deletePermission;
        $createPermission = $this->createPermission;
        $viewPermission = $this->viewPermission;

        return view($this->panelView('products.index'), compact('columns', 'editPermission', 'deletePermission', 'createPermission', 'viewPermission'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Product::class);

        $categories = CategoryService::getCategoriesWithParent();
        $attributes = GlobalAttributeService::getAttributesWithValue($this->sellerId);

        $categories = json_encode($categories->toArray());
        $attributes = json_encode($attributes->toArray());

        $pethiyanSellerId = null;
        $pethiyanBrandId = null;
        if ($this->getPanel() === 'admin') {
            // Pethiyan is always the default seller (seller_id=1) and brand (brand_id=1)
            $pethiyanSellerId = 1;
            $pethiyanBrandId = 1;
            // Re-fetch attributes scoped to Pethiyan seller
            $attributes = GlobalAttributeService::getAttributesWithValue($pethiyanSellerId);
            $attributes = json_encode($attributes->toArray());
        }

        $taxClasses = TaxClass::where('is_active', true)
            ->with(['taxRates:id,rate'])
            ->orderBy('title')
            ->get(['id', 'title']);
        $gstStates = State::where('country_id', 101)
            ->orderBy('name')
            ->get(['id', 'name', 'state_code', 'gst_code']);

        $taxClassRateMap = $taxClasses->mapWithKeys(function ($taxClass) {
            $rateSum = (float) ($taxClass->taxRates->sum('rate') ?? 0);
            $nearest = collect([0, 5, 12, 18, 28])->sortBy(function ($s) use ($rateSum) {
                return abs($s - $rateSum);
            })->first();
            return [$taxClass->id => (int) $nearest];
        });

        $stores = Store::where('seller_id', $pethiyanSellerId ?? $this->sellerId)
            ->get([
                'id', 'name', 'city', 'contact_number', 'verification_status', 'visibility_status',
                'state_id', 'state_name', 'state_code', 'gstin'
            ]);

        $stateMap = State::whereIn('id', $stores->pluck('state_id')->filter()->unique()->values())
            ->get(['id', 'name', 'state_code', 'gst_code'])
            ->keyBy('id');

        $storeList = $stores->map(function ($store) use ($stateMap) {
                $gstCodeFromGstin = null;
                if (!empty($store->gstin) && strlen($store->gstin) >= 2) {
                    $gstCodeFromGstin = substr($store->gstin, 0, 2);
                }
                $linkedState = $stateMap->get($store->state_id);

                return [
                    'id' => $store->id,
                    'name' => $store->name,
                    'city' => $store->city,
                    'contact_number' => $store->contact_number,
                    'verification_status' => $store->verification_status,
                    'visibility_status' => $store->visibility_status,
                    'state_id' => $store->state_id,
                    'state_name' => $store->state_name ?: ($linkedState->name ?? null),
                    'state_code' => $store->state_code ?: ($linkedState->state_code ?? null),
                    'gst_code' => $linkedState->gst_code ?? $gstCodeFromGstin,
                ];
            })
            ->values();

        return view($this->panelView('products.form'), compact('categories', 'attributes', 'pethiyanSellerId', 'pethiyanBrandId', 'taxClasses', 'taxClassRateMap', 'storeList', 'gstStates'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUpdateProductRequest $request, ProductService $productService): JsonResponse
    {
        try {
            $this->authorize('create', Product::class);

            $validated = $request->validated();
            $user = auth()->user();
            if ($this->getPanel() === 'admin') {
                // Always assign to Pethiyan seller (ID=1) for admin-created products
                $validated['seller_id'] = 1;
            } else {
                $validated['seller_id'] = $user->seller()->id;
            }
            event(new ProductBeforeCreate());
            $result = $productService->storeProduct($validated, $request);
            event(new ProductAfterCreate($result['product']));

            $responseData = [
                'product_id' => $result['product']->id,
                'product_uuid' => $result['product']->uuid,
            ];
            if ($this->getPanel() === 'admin') {
                $responseData['redirect_url'] = route('admin.products.index');
            }
            return ApiResponseType::sendJsonResponse(success: true, message: 'labels.product_created_successfully', data: $responseData);
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.permission_denied', data: []);
        } catch (\Exception $e) {
            Log::error("Error while creating product =>" . $e->getMessage());
            return ApiResponseType::sendJsonResponse(success: false, message: $e->getMessage(), data: $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = ProductService::getProductWithVariants($id);
        if (!$product) {
            abort(404, "Product Not Found");
        }
        $this->authorize('view', $product);

        // Load relationships
        $product->load(['faqs', 'category', 'brand', 'productCondition']);

        // Get media
        $product->product_video = $product->getFirstMediaUrl(SpatieMediaCollectionName::PRODUCT_VIDEO());

        // Get store-wise pricing data for variants
        $storeVariantPricing = [];
        $variants = ProductVariant::where('product_id', $id)->get();

        foreach ($variants as $variant) {
            // Load variant attributes
            $variant->load(['attributes.attribute', 'attributes.attributeValue']);

            // Get store-specific pricing for this variant
            $storePricing = StoreProductVariant::where('product_variant_id', $variant->id)
                ->with('store')
                ->get()
                ->map(function ($item) {
                    return [
                        'store_id' => $item->store_id,
                        'store_name' => $item->store->name ?? '',
                        'price' => $item->price_exclude_tax,
                        'special_price' => $item->special_price_exclude_tax,
                        'cost' => $item->cost,
                        'stock' => $item->stock,
                        'sku' => $item->sku
                    ];
                });

            $storeVariantPricing[$variant->id] = [
                'variant_id' => $variant->id,
                'title' => $variant->title,
                'attributes' => $variant->attributes->map(function ($attr) {
                    return [
                        'attribute_name' => $attr->attribute->title ?? '',
                        'attribute_value' => $attr->attributeValue->title ?? ''
                    ];
                }),
                'store_pricing' => $storePricing
            ];
        }
        $updateStatusPermission = $this->updateStatusPermission;

        return view($this->panelView('products.show'), compact('product', 'storeVariantPricing', 'updateStatusPermission'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $product = ProductService::getProductWithVariants($id);
        if (!$product) {
            abort(404, "Product Not Found");
        }
        $this->authorize('update', $product);
        $productVariants = null;
        $singleProductVariant = null;
        if ($product->type === ProductTypeEnum::VARIANT()) {
            foreach ($product->variants as $key => $variant) {
                $product->variants[$key]->image = $variant->image;
            }
            $productVariants = $product->variants;
        } else {
            $singleProductVariant = $product->variants->first();
        }
        $product->product_video = $product->getFirstMediaUrl(SpatieMediaCollectionName::PRODUCT_VIDEO());
        $categories = CategoryService::getCategoriesWithParent();

        $attributes = GlobalAttributeService::getAttributesWithValue($this->sellerId);
        $categories = json_encode($categories->toArray());
        $attributes = json_encode($attributes->toArray());
        $taxClasses = TaxClass::where('is_active', true)
            ->with(['taxRates:id,rate'])
            ->orderBy('title')
            ->get(['id', 'title']);
        $taxClassRateMap = $taxClasses->mapWithKeys(function ($taxClass) {
            $rateSum = (float) ($taxClass->taxRates->sum('rate') ?? 0);
            $nearest = collect([0, 5, 12, 18, 28])->sortBy(function ($s) use ($rateSum) {
                return abs($s - $rateSum);
            })->first();
            return [$taxClass->id => (int) $nearest];
        });
        $selectedTaxGroupId = null;
        $selectedGstRate = null;
        $gstStates = State::where('country_id', 101)
            ->orderBy('name')
            ->get(['id', 'name', 'state_code', 'gst_code']);

        $stores = Store::where('seller_id', $product->seller_id)
            ->get([
                'id', 'name', 'city', 'contact_number', 'verification_status', 'visibility_status',
                'state_id', 'state_name', 'state_code', 'gstin'
            ]);

        $stateMap = State::whereIn('id', $stores->pluck('state_id')->filter()->unique()->values())
            ->get(['id', 'name', 'state_code', 'gst_code'])
            ->keyBy('id');

        $storeList = $stores->map(function ($store) use ($stateMap) {
                $gstCodeFromGstin = null;
                if (!empty($store->gstin) && strlen($store->gstin) >= 2) {
                    $gstCodeFromGstin = substr($store->gstin, 0, 2);
                }
                $linkedState = $stateMap->get($store->state_id);

                return [
                    'id' => $store->id,
                    'name' => $store->name,
                    'city' => $store->city,
                    'contact_number' => $store->contact_number,
                    'verification_status' => $store->verification_status,
                    'visibility_status' => $store->visibility_status,
                    'state_id' => $store->state_id,
                    'state_name' => $store->state_name ?: ($linkedState->name ?? null),
                    'state_code' => $store->state_code ?: ($linkedState->state_code ?? null),
                    'gst_code' => $linkedState->gst_code ?? $gstCodeFromGstin,
                ];
            })
            ->values();

        // Reliable edit preselection values.
        $selectedTaxGroupId = DB::table('product_taxes')
            ->where('product_id', $product->id)
            ->value('tax_class_id')
            ?: optional($product->taxClasses->first())->id;

        $selectedGstRate = $product->gst_rate !== null && $product->gst_rate !== ''
            ? (string) $product->gst_rate
            : null;

        return view($this->panelView('products.form'), compact(
            'product',
            'productVariants',
            'singleProductVariant',
            'categories',
            'attributes',
            'taxClasses',
            'taxClassRateMap',
            'storeList',
            'gstStates',
            'selectedTaxGroupId',
            'selectedGstRate'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreUpdateProductRequest $request, string $id, ProductService $productService): JsonResponse
    {
        try {
            // Find the product
            $product = Product::findOrFail($id);

            // Authorize the user
            $this->authorize('update', $product);

            $validated = $request->validated();

            // Add seller_id to validated data
            $user = auth()->user();
            if ($this->getPanel() === 'admin') {
                // Keep existing owner or fallback to Pethiyan seller for admin updates.
                $validated['seller_id'] = $product->seller_id ?: 1;
            } else {
                $validated['seller_id'] = $user->seller()->id;
            }

            // Update the product
            $result = $productService->updateProduct($product, $validated, $request);
            event(new ProductAfterUpdate($result['product']));
            return ApiResponseType::sendJsonResponse(success: true, message: 'labels.product_updated_successfully', data: [
                'product_id' => $result['product']->id,
                'product_uuid' => $result['product']->uuid,
            ]);
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.permission_denied', data: []);
        } catch (ModelNotFoundException $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.product_not_found', data: []);
        } catch (\Exception $e) {
            Log::error("Error while creating product =>" . $e->getMessage());
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.failed_to_update_product: ' . $e->getMessage(), data: []);
        }
    }

    public function duplicate(string $id, ProductService $productService): RedirectResponse
    {
        $product = ProductService::getProductWithVariants((int) $id);
        if (!$product) {
            abort(404, 'Product Not Found');
        }

        $this->authorize('view', $product);
        $this->authorize('create', Product::class);

        try {
            $sellerId = $this->getPanel() === 'admin' ? 1 : $product->seller_id;
            $duplicate = $productService->duplicateProduct($product, $sellerId);

            return redirect()
                ->route($this->panelView('products.index'))
                ->with('success', 'Product copied successfully.');
        } catch (AuthorizationException $e) {
            abort(403, __('labels.permission_denied'));
        } catch (\Throwable $e) {
            Log::error('Error while duplicating product => ' . $e->getMessage());
            return redirect()
                ->route($this->panelView('products.index'))
                ->with('error', 'Failed to copy product.');
        }
    }

    /**
     * Get product pricing data for a specific product
     */
    public function getProductPricing(string $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);

            // Authorize the user
            $this->authorize('view', $product);

            $variants = ProductVariant::where('product_id', $id)->get();

            $variantPricing = [];

            foreach ($variants as $variant) {
                $storePricing = StoreProductVariant::where('product_variant_id', $variant->id)
                    ->with('store')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'store_id' => $item->store_id,
                            'store_name' => $item->store->name ?? '',
                            'price' => $item->price_exclude_tax,
                            'special_price' => $item->special_price_exclude_tax,
                            'cost' => $item->cost,
                            'stock' => $item->stock,
                            'sku' => $item->sku
                        ];
                    });

                $variantPricing[$variant->id] = [
                    'variant_id' => $variant->id,
                    'title' => $variant->title,
                    'store_pricing' => $storePricing
                ];
            }
            return ApiResponseType::sendJsonResponse(success: true, message: 'labels.product_pricing_fetched_successfully', data: [
                'product_id' => $product->id,
                'variant_pricing' => $variantPricing
            ]);
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.permission_denied', data: []);
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.failed_to_fetch_product_pricing: ' . $e->getMessage(), data: []);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);

            // Authorize the user
            $this->authorize('delete', $product);

            $product->delete();
            FrontendRevalidateService::revalidateProducts();
            return ApiResponseType::sendJsonResponse(success: true, message: 'labels.product_deleted_successfully', data: []);
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.permission_denied', data: []);
        } catch (ModelNotFoundException $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.product_not_found', data: []);
        }
    }

    /**
     * Get products for datatable
     */
    public function getProducts(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', Product::class);

            [$draw, $start, $length, $searchValue, $filters, $orderColumn, $orderDirection] = $this->extractRequestParams($request);

            $query = $this->buildBaseQuery();

            $totalRecords = $query->count();

            $query = $this->applyFilters($query, $searchValue, $filters);
            $filteredRecords = $query->count();

            $products = $query
                ->orderBy($orderColumn, $orderDirection)
                ->skip($start)
                ->take($length)
                ->get();

            $data = $products->map(fn($product) => $this->formatProductData($product))->toArray();

            return response()->json([
                'draw' => intval($draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', []);
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(false, 'labels.failed_to_fetch_products: ' . $e->getMessage(), []);
        }
    }

    private function extractRequestParams(Request $request): array
    {
        $draw = $request->get('draw');
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        $searchValue = $request->get('search')['value'] ?? '';

        $filters = [
            'product_type' => $request->get('product_type'),
            'product_status' => $request->get('product_status'),
            'verification_status' => $request->get('verification_status'),
            'category_id' => $request->get('category_id'),
        ];

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';
        $columns = ['id', 'title', 'category_id', 'status', 'featured', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        return [$draw, $start, $length, $searchValue, $filters, $orderColumn, $orderDirection];
    }

    private function buildBaseQuery(): Builder
    {
        $query = Product::with(['category', 'seller']);

        if ($this->getPanel() === 'seller') {
            $query->where('seller_id', $this->sellerId);
        }

        return $query;
    }

    private function applyFilters($query, string $searchValue, array $filters)
    {
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('title', 'like', "%{$searchValue}%")
                    ->orWhere('description', 'like', "%{$searchValue}%")
                    ->orWhere('type', 'like', "%{$searchValue}%")
                    ->orWhere('verification_status', 'like', "%{$searchValue}%")
                    ->orWhereHas('category', fn($q) => $q->where('title', 'like', "%{$searchValue}%"));
            });
        }

        if (!empty($filters['product_type'])) {
            $query->where('type', $filters['product_type']);
        }

        if (!empty($filters['product_status'])) {
            $query->where('status', $filters['product_status']);
        }

        if (!empty($filters['verification_status'])) {
            $status = $filters['verification_status'];
            if ($status === 'pending') {
                $status = ProductVarificationStatusEnum::PENDING()->value;
            }
            $query->where('verification_status', $status);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        return $query;
    }

    private function formatProductData(Product $product): array
    {
        return [
            'id' => $product->id,
            'title' => $product->title,
            'image' => view('partials.image', [
                'image' => $product->getFirstMediaUrl(SpatieMediaCollectionName::PRODUCT_MAIN_IMAGE()) ?? "",
                'title' => $product->title
            ])->render(),
            'product_type' => '<span class="badge ' .
                ($product->type == ProductTypeEnum::VARIANT() ? "bg-danger-lt" : "bg-info-lt") .
                '">' . $product->type . '</span>',
            'category' => $product->category?->title ?? '-',
            'status' => view('partials.status', ['status' => $product->status ?? ""])->render(),
            'admin_approval_status' => view('partials.status', ['status' => $product->verification_status ?? ""])->render(),
            'featured' => $product->featured ? 'Yes' : 'No',
            'created_at' => $product->created_at->format('Y-m-d'),
            'action' => view('partials.product-actions', [
                'modelName' => 'product',
                'id' => $product->id,
                'title' => $product->title,
                'status' => $product->status,
                'mode' => 'page_view',
                'route' => route($this->panelView('products.edit'), ['id' => $product->id]),
                'viewRoute' => route($this->panelView('products.show'), ['id' => $product->id]),
                'editPermission' => $this->editPermission,
                'deletePermission' => $this->deletePermission,
                'duplicatePermission' => $this->createPermission,
                'duplicateRoute' => route($this->panelView('products.duplicate'), ['id' => $product->id]),
                'viewPermission' => $this->viewPermission,
            ])->render(),
        ];
    }


    public function search(Request $request): JsonResponse
    {
        $query = $request->input('search'); // Get the search query
        $exceptId = $request->input('exceptId'); // Get the search query
        $findId = $request->input('find_id'); // Specific category ID to find

        if ($findId) {
            // If find_id is set and not empty, fetch only that category
            $products = Product::where('id', $findId)
                ->select('id', 'title')
                ->get();
        } else {
            // Fetch categories matching the search query
            $products = Product::select('id', 'title')
                ->where('title', 'LIKE', "%{$query}%")
                ->when($exceptId, function ($q) use ($exceptId) {
                    $q->where('id', '!=', $exceptId);
                })
                ->when($this->getPanel() === 'seller', function ($q) {
                    $q->where('seller_id', $this->sellerId);
                })
                ->limit(10)
                ->get();
        }
        $results = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'value' => $product->id,
                'text' => $product->title,
            ];
        });
        // Return the categories as JSON
        return response()->json($results);
    }

    public function updateVerificationStatus(Request $request, int $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $this->authorize('verifyProduct', $product);

            $request->validate([
                'verification_status' => ['required', new Enum(ProductVarificationStatusEnum::class)],
                'rejection_reason' => 'required_if:verification_status,' . ProductVarificationStatusEnum::REJECTED() . '|max:500',
            ]);

            $status = $request->input('verification_status');
            if ($status === ProductVarificationStatusEnum::REJECTED() && empty($request->input('rejection_reason'))) {
                return ApiResponseType::sendJsonResponse(success: false, message: 'Rejection reason is required', data: []);
            }

            $product->verification_status = $status;
            $product->rejection_reason = $status === ProductVarificationStatusEnum::REJECTED()
                ? $request->input('rejection_reason')
                : null;
            $product->save();
            FrontendRevalidateService::revalidateProducts();

            return ApiResponseType::sendJsonResponse(success: true, message: 'Verification status updated successfully', data: [
                'id' => $product->id,
                'verification_status' => $product->verification_status,
                'rejection_reason' => $product->rejection_reason,
            ]);
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.permission_denied', data: []);
        } catch (ModelNotFoundException $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.product_not_found', data: []);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: $e->getMessage(), data: $e->errors());
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.something_went_wrong', data: ['error' => $e->getMessage()]);
        }
    }

    public function updateStatus(int $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $this->authorize('verifyProduct', $product);

            // Toggle status between ACTIVE and DRAFT
            $newStatus = $product->status === ProductStatusEnum::ACTIVE->value
                ? ProductStatusEnum::DRAFT->value
                : ProductStatusEnum::ACTIVE->value;

            $product->status = $newStatus;
            $product->save();
            FrontendRevalidateService::revalidateProducts();

            $statusLabel = $newStatus === ProductStatusEnum::ACTIVE->value ? 'Active' : 'Draft';

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: "Product status updated to {$statusLabel} successfully",
                data: [
                    'id' => $product->id,
                    'status' => $product->status,
                ]
            );
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.permission_denied', data: []);
        } catch (ModelNotFoundException $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.product_not_found', data: []);
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.something_went_wrong', data: ['error' => $e->getMessage()]);
        }
    }
}
