<?php

namespace App\Http\Controllers;

use App\Models\FaqCategory;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;

class FaqCategoryController extends Controller
{
    use PanelAware, AuthorizesRequests, ChecksPermissions;

    public function __construct() {}

    /**
     * Display a listing of FAQ categories.
     */
    public function index(): View
    {
        $columns = [
            ['data' => 'id',         'name' => 'id',         'title' => __('labels.id')],
            ['data' => 'name',       'name' => 'name',       'title' => __('labels.name')],
            ['data' => 'icon',       'name' => 'icon',       'title' => 'Icon', 'orderable' => false, 'searchable' => false],
            ['data' => 'sort_order', 'name' => 'sort_order', 'title' => 'Sort Order'],
            ['data' => 'status',     'name' => 'status',     'title' => __('labels.status')],
            ['data' => 'action',     'name' => 'action',     'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];

        return view($this->panelView('faq-categories.index'), compact('columns'));
    }

    /**
     * Store a newly created FAQ Category.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'icon'       => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'status'     => 'nullable|in:active,inactive',
        ]);

        $validated['status']     = $validated['status'] ?? 'active';
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $category = FaqCategory::create($validated);

        return ApiResponseType::sendJsonResponse(true, 'FAQ category created successfully.', $category);
    }

    /**
     * Get FAQ Category for editing.
     */
    public function edit(int $id): JsonResponse
    {
        $category = FaqCategory::findOrFail($id);
        return ApiResponseType::sendJsonResponse(true, 'FAQ category fetched successfully.', $category);
    }

    /**
     * Update the specified FAQ Category.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $category = FaqCategory::findOrFail($id);

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'icon'       => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'status'     => 'nullable|in:active,inactive',
        ]);

        $category->update($validated);

        return ApiResponseType::sendJsonResponse(true, 'FAQ category updated successfully.', $category);
    }

    /**
     * Delete the specified FAQ Category.
     */
    public function destroy(int $id): JsonResponse
    {
        $category = FaqCategory::findOrFail($id);
        $category->delete();

        return ApiResponseType::sendJsonResponse(true, 'FAQ category deleted successfully.');
    }

    /**
     * DataTable endpoint for FAQ categories.
     */
    public function getCategories(Request $request): JsonResponse
    {
        $draw         = $request->get('draw');
        $start        = $request->get('start', 0);
        $length       = $request->get('length', 10);
        $searchValue  = $request->get('search')['value'] ?? '';

        $query = FaqCategory::query();

        $totalRecords = $query->count();
        $filteredRecords = $totalRecords;

        if (!empty($searchValue)) {
            $query->where('name', 'like', "%{$searchValue}%");
            $filteredRecords = $query->count();
        }

        $data = $query
            ->orderBy('sort_order')
            ->orderBy('id', 'desc')
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($cat) {
                return [
                    'id'         => $cat->id,
                    'name'       => $cat->name,
                    'icon'       => '<span style="font-size:1.4em">' . e($cat->icon) . '</span>',
                    'sort_order' => $cat->sort_order,
                    'status'     => view('partials.status', ['status' => $cat->status ?? ''])->render(),
                    'action'     => view('partials.actions', [
                        'modelName'        => 'faq-category',
                        'id'               => $cat->id,
                        'title'            => $cat->name,
                        'mode'             => 'model_view',
                        'editPermission'   => true,
                        'deletePermission' => true,
                    ])->render(),
                ];
            })
            ->toArray();

        return response()->json([
            'draw'            => intval($draw),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }
}
