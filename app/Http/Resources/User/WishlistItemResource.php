<?php

namespace App\Http\Resources\User;

use App\Models\Review;
use App\Services\DeliveryZoneService;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistItemResource extends JsonResource
{
    public function toArray($request): array
    {
        $product = $this->product;
        if (!$product) {
            return [
                'id' => $this->id,
                'wishlist_id' => $this->wishlist_id,
                'product' => null,
                'variant' => null,
                'store' => null,
                'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            ];
        }

        $reviews = Review::scopeProductRatingStats($product->id);

        if (isset($request->latitude) && isset($request->longitude)) {
            $product->user_latitude = $request->latitude;
            $product->user_longitude = $request->longitude;
            $product->zone_info = DeliveryZoneService::getZonesAtPoint($request->latitude, $request->longitude);
        }

        $firstVariant = $product->variants->first();
        $firstStoreProductVariant = $firstVariant?->storeProductVariants->first();
        $variantStoreProduct = $this->variant?->storeProductVariants?->firstWhere('store_id', $this->store_id)
            ?? $this->variant?->storeProductVariants?->first();

        return [
            'id' => $this->id,
            'wishlist_id' => $this->wishlist_id,
            'product' => [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'image' => $product->main_image,
                'minimum_order_quantity' => $product->minimum_order_quantity,
                'quantity_step_size' => $product->quantity_step_size,
                'total_allowed_quantity' => $product->total_allowed_quantity,
                'short_description' => $product->short_description,
                'estimated_delivery_time' => $product->estimated_delivery_time,
                'image_fit' => $product->image_fit,
                'store_status' => $firstStoreProductVariant?->store?->checkStoreStatus() ?? [],
                'ratings' => $reviews['average_rating'] ?? 0,
                'rating_count' => $reviews['total_reviews'] ?? 0,
            ],
            'variant' => $this->when($this->variant, [
                'id' => $this->variant?->id,
                'sku' => $this->variant?->sku,
                'image' => $this->variant?->image,
                'price' => $variantStoreProduct?->price,
                'special_price' => $variantStoreProduct?->special_price,
                'store_id' => $variantStoreProduct?->store_id ?? null,
                'store_slug' => $variantStoreProduct?->store?->slug ?? null,
                'store_name' => $variantStoreProduct?->store?->name ?? null,
                'stock' => $variantStoreProduct?->stock ?? null,
                'sku' => $variantStoreProduct?->sku ?? null,
            ]),
            'store' => $this->store ? [
                'id' => $this->store->id,
                'name' => $this->store->name,
                'slug' => $this->store->slug,
            ] : null,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
