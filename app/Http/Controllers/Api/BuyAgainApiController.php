<?php

namespace App\Http\Controllers\Api;

use App\Enums\Order\OrderItemStatusEnum;
use App\Enums\Product\ProductStatusEnum;
use App\Enums\Product\ProductVarificationStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Product\ProductListResource;
use App\Models\OrderItem;
use App\Models\Product;
use App\Types\Api\ApiResponseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BuyAgainApiController extends Controller
{
    /**
     * GET /api/buy-again
     *
     * Returns distinct products the authenticated user has previously received
     * (order item status = delivered), ordered by most recently delivered first.
     * Excludes products that are no longer active/approved.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();

        // Collect distinct product IDs from delivered order items, most recent first
        $rows = OrderItem::whereHas('order', fn($q) => $q->where('user_id', $userId))
            ->where('status', OrderItemStatusEnum::DELIVERED())
            ->whereNotNull('product_id')
            ->selectRaw('product_id, MAX(updated_at) as last_delivered_at')
            ->groupBy('product_id')
            ->orderByDesc('last_delivered_at')
            ->limit(40)
            ->get();

        if ($rows->isEmpty()) {
            return ApiResponseType::sendJsonResponse(true, 'Success', [
                'data'  => [],
                'total' => 0,
            ]);
        }

        $productIds = $rows->pluck('product_id');

        $products = Product::whereIn('id', $productIds)
            ->where('verification_status', ProductVarificationStatusEnum::APPROVED->value)
            ->where('status', ProductStatusEnum::ACTIVE->value)
            ->with([
                'category:id,title,slug',
                'variants.attributes.attribute:id,title,slug',
                'variants.attributes.attributeValue:id,title,swatche_value',
                'variants.storeProductVariants.store:id,name,slug,state_code,state_name',
            ])
            ->get()
            // Preserve most-recently-delivered order
            ->sortBy(fn($p) => $productIds->search($p->id))
            ->values();

        return ApiResponseType::sendJsonResponse(true, 'Success', [
            'data'  => ProductListResource::collection($products),
            'total' => $products->count(),
        ]);
    }
}
