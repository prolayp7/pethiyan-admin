<?php

namespace App\Http\Resources\Product;

use App\Services\GstService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductFeaturedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $customerStateCode = $request->input('customer_state_code');
        $gstService = app(GstService::class);

        $firstVariant = $this->variants->first();

        $storePricing = [];
        if ($firstVariant) {
            $gstRate = (int) ($this->gst_rate ?? 0);
            foreach ($firstVariant->storeProductVariants as $storeVariant) {
                $priceExcludingTax = (float) ($storeVariant->price_exclude_tax ?? $storeVariant->getRawOriginal('price') ?? 0);
                $specialPriceExcludingTax = (float) ($storeVariant->special_price_exclude_tax ?? $storeVariant->getRawOriginal('special_price') ?? 0);

                $effectiveExcl = $specialPriceExcludingTax > 0 ? $specialPriceExcludingTax : $priceExcludingTax;

                $storeStateCode = $storeVariant->store->state_code ?? null;
                $supplyType = $gstService->supplyType($storeStateCode, $customerStateCode);
                $gst = $gstService->calculateLineItem(
                    unitPrice: $effectiveExcl,
                    quantity: 1,
                    gstRatePct: $gstRate,
                    supplyType: $supplyType,
                    priceInclusive: (bool) $this->is_inclusive_tax
                );

                $storePricing[] = [
                    'store_id' => $storeVariant->store_id,
                    'store_name' => $storeVariant->store->name ?? null,
                    'store_slug' => $storeVariant->store->slug ?? null,
                    'sku' => $storeVariant->sku,
                    'price' => $storeVariant->price,
                    'special_price' => $specialPriceExcludingTax > 0 ? $specialPriceExcludingTax : $storeVariant->special_price,
                    'cost' => $storeVariant->cost,
                    'stock' => (int) ($storeVariant->stock ?? 0),
                    'stock_status' => ((int) ($storeVariant->stock ?? 0) > 0) ? 'in_stock' : 'out_of_stock',
                    'gst' => $gst,
                ];
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->title,
            'title' => $this->title,
            'slug' => $this->slug,
            'thumbnail' => $this->main_image,
            'images' => array_values(array_filter(array_unique(array_merge([$this->main_image], $this->additional_images ?? [])))),
            'tags' => $this->tags,
            'rating' => null,
            'reviews_count' => null,
            'store_pricing' => $storePricing,
        ];
    }
}
