<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreProductVariant;
use App\Types\Api\ApiResponseType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $columns = [
            ['data' => 'id',           'name' => 'id',           'title' => '#'],
            ['data' => 'product',      'name' => 'product',      'title' => 'Product',      'orderable' => false],
            ['data' => 'variant',      'name' => 'variant',      'title' => 'Variant',      'orderable' => false, 'searchable' => false],
            ['data' => 'store',        'name' => 'store',        'title' => 'Store',        'orderable' => false],
            ['data' => 'sku',          'name' => 'sku',          'title' => 'SKU'],
            ['data' => 'price',        'name' => 'price',        'title' => 'Price (₹)',    'orderable' => false, 'searchable' => false],
            ['data' => 'stock',        'name' => 'stock',        'title' => 'Stock'],
            ['data' => 'stock_status', 'name' => 'stock_status', 'title' => 'Status',       'orderable' => false, 'searchable' => false],
            ['data' => 'action',       'name' => 'action',       'title' => 'Action',       'orderable' => false, 'searchable' => false],
        ];

        return view('admin.inventory.index', compact('columns'));
    }

    public function datatable(Request $request): JsonResponse
    {
        $query = StoreProductVariant::with([
            'productVariant.product',
            'productVariant.attributes.attribute',
            'productVariant.attributes.attributeValue',
            'store',
        ])->withoutGlobalScopes();

        // Search
        $search = $request->input('search.value');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                  ->orWhereHas('productVariant.product', fn($pq) => $pq->where('title', 'like', "%{$search}%"))
                  ->orWhereHas('store', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        // Stock filter
        if ($request->filled('stock_filter')) {
            match ($request->input('stock_filter')) {
                'out_of_stock' => $query->where('stock', 0),
                'low_stock'    => $query->where('stock', '>', 0)->where('stock', '<=', 10),
                'in_stock'     => $query->where('stock', '>', 10),
                default        => null,
            };
        }

        $total    = $query->count();
        $filtered = $total;

        $orderDir = $request->input('order.0.dir', 'asc');
        $query->orderBy('stock', $orderDir);

        $query->skip($request->input('start', 0))->take($request->input('length', 15));

        $rows = $query->get()->map(function (StoreProductVariant $spv) {
            $product = $spv->productVariant?->product;
            $variant = $spv->productVariant;

            $variantLabel = $variant?->attributes
                ?->map(function ($attrRow) {
                    $attributeName = $attrRow->attribute?->title;
                    $attributeValue = $attrRow->attributeValue?->title;

                    if (!$attributeName && !$attributeValue) {
                        return null;
                    }

                    return trim(($attributeName ?? '—') . ': ' . ($attributeValue ?? '—'));
                })
                ->filter()
                ->join(', ') ?? '—';

            $stockClass = match (true) {
                $spv->stock === 0  => 'danger-lt',
                $spv->stock <= 10  => 'warning-lt',
                default            => 'success-lt',
            };
            $stockLabel = match (true) {
                $spv->stock === 0  => 'Out of Stock',
                $spv->stock <= 10  => 'Low Stock',
                default            => 'In Stock',
            };

            return [
                'id'           => $spv->id,
                'product'      => $product?->title ?? '—',
                'variant'      => $variantLabel,
                'store'        => $spv->store?->name ?? '—',
                'sku'          => $spv->sku ?? '—',
                'price'        => '₹' . number_format($spv->attributes['price'] ?? 0, 2),
                'stock'        => $spv->stock,
                'stock_status' => '<span class="badge bg-' . $stockClass . ' fw-medium">' . $stockLabel . '</span>',
                'action'       => '<button class="btn btn-sm btn-ghost-primary btn-edit-stock d-inline-flex align-items-center gap-1 px-3 text-nowrap"
                                    data-id="' . $spv->id . '"
                                    data-stock="' . $spv->stock . '"
                                    data-product="' . e($product?->title) . '"
                                    data-variant="' . e($variantLabel) . '">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-edit" width="14" height="14"
                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/>
                                        <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"/>
                                    </svg>
                                    Edit Stock</button>',
            ];
        });

        return response()->json([
            'draw'            => (int)$request->input('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows,
        ]);
    }

    public function updateStock(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'stock' => 'required|integer|min:0',
        ]);

        $spv = StoreProductVariant::withoutGlobalScopes()->findOrFail($id);
        $spv->stock = $validated['stock'];
        $spv->save();

        return ApiResponseType::sendJsonResponse(true, 'Stock updated successfully.', ['stock' => $spv->stock]);
    }

    public function bulkUpdateStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items'         => 'required|array|min:1',
            'items.*.id'    => 'required|integer|exists:store_product_variants,id',
            'items.*.stock' => 'required|integer|min:0',
        ]);

        foreach ($validated['items'] as $item) {
            StoreProductVariant::withoutGlobalScopes()
                ->where('id', $item['id'])
                ->update(['stock' => $item['stock']]);
        }

        return ApiResponseType::sendJsonResponse(true, 'Stock updated for ' . count($validated['items']) . ' variants.');
    }
}
