<?php

namespace App\Http\Controllers\Api;

use App\Enums\Product\ProductStatusEnum;
use App\Enums\Product\ProductVarificationStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Product\ProductListResource;
use App\Models\BrowsingHistory;
use App\Models\Product;
use App\Types\Api\ApiResponseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BrowsingHistoryApiController extends Controller
{
    /**
     * Record a product view. Requires auth.
     * POST /api/browsing-history
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|min:1',
        ]);

        $userId = Auth::id();

        BrowsingHistory::updateOrCreate(
            ['user_id' => $userId, 'product_id' => $validated['product_id']],
            ['viewed_at' => now()]
        );

        // Keep only the latest 50 records per user
        $ids = BrowsingHistory::where('user_id', $userId)
            ->orderByDesc('viewed_at')
            ->pluck('id');

        if ($ids->count() > 50) {
            BrowsingHistory::whereIn('id', $ids->slice(50)->values())->delete();
        }

        return ApiResponseType::sendJsonResponse(true, 'Recorded', []);
    }

    /**
     * Get browsing history for the authenticated user.
     * GET /api/browsing-history
     */
    public function index(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $productIds = BrowsingHistory::where('user_id', $userId)
            ->orderByDesc('viewed_at')
            ->limit(50)
            ->pluck('product_id');

        if ($productIds->isEmpty()) {
            return ApiResponseType::sendJsonResponse(true, 'Success', [
                'data' => [],
                'total' => 0,
            ]);
        }

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
            ->sortBy(fn($p) => $productIds->search($p->id))
            ->values();

        return ApiResponseType::sendJsonResponse(true, 'Success', [
            'data'  => ProductListResource::collection($products),
            'total' => $products->count(),
        ]);
    }
}
