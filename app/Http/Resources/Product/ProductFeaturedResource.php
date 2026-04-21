<?php

namespace App\Http\Resources\Product;

use App\Services\CurrencyService;
use App\Services\GstService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductFeaturedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $customerStateCode = $request->input('customer_state_code');
        $gstService = app(GstService::class);
        $currency = app(CurrencyService::class);
        $gstRate = (int) ($this->gst_rate ?? 0);

        // Only compute full store_pricing for the default (or first) variant
        $defaultVariant = $this->variants->firstWhere('is_default', true)
            ?? $this->variants->first();

        $variantImages = $this->variants->pluck('image')->filter()->values()->all();

        $variants = $this->variants->map(function ($variant) use ($gstService, $gstRate, $customerStateCode, $defaultVariant) {
            $attributes = [];
            foreach ($variant->attributes as $attribute) {
                if ($attribute->attribute && $attribute->attributeValue) {
                    $attributes[$attribute->attribute->slug] = $attribute->attributeValue->title;
                }
            }

            // Compute full store_pricing only for the default/first variant
            $storePricing = [];
            if ($defaultVariant && $variant->id === $defaultVariant->id) {
                foreach ($variant->storeProductVariants as $storeVariant) {
                    $priceExcludingTax = (float) ($storeVariant->price_exclude_tax ?? $storeVariant->getRawOriginal('price') ?? 0);
                    $specialPriceExcludingTax = (float) ($storeVariant->special_price_exclude_tax ?? $storeVariant->getRawOriginal('special_price') ?? 0);
                    $effectiveExcl = $specialPriceExcludingTax > 0 ? $specialPriceExcludingTax : $priceExcludingTax;

                    $discountPercent = null;
                    if ($specialPriceExcludingTax > 0 && $priceExcludingTax > 0 && $specialPriceExcludingTax < $priceExcludingTax) {
                        $discountPercent = (int) round(($priceExcludingTax - $specialPriceExcludingTax) / $priceExcludingTax * 100);
                    }

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
                        'store_id'         => $storeVariant->store_id,
                        'store_name'       => $storeVariant->store->name ?? null,
                        'store_slug'       => $storeVariant->store->slug ?? null,
                        'store_state_code' => $storeStateCode,
                        'store_state_name' => $storeVariant->store->state_name ?? null,
                        'sku'              => $storeVariant->sku,
                        'price'            => $storeVariant->price,
                        'special_price'    => $specialPriceExcludingTax > 0 ? $specialPriceExcludingTax : null,
                        'cost'             => $storeVariant->cost,
                        'discount_percent' => $discountPercent,
                        'stock'            => (int) ($storeVariant->stock ?? 0),
                        'stock_status'     => ((int) ($storeVariant->stock ?? 0) > 0) ? 'in_stock' : 'out_of_stock',
                        'gst'              => $gst,
                    ];
                }
            }

            return [
                'id'           => $variant->id,
                'title'        => $variant->title,
                'slug'         => $variant->slug,
                'image'        => $variant->image,
                'barcode'      => $variant->barcode,
                'is_default'   => (bool) $variant->is_default,
                'availability' => (bool) $variant->availability,
                'weight'       => $variant->weight,
                'weight_unit'  => $variant->weight_unit,
                'attributes'   => $attributes,
                'store_pricing' => $storePricing,
            ];
        })->values()->all();

        return [
            'id'       => $this->id,
            'title'    => $this->title,
            'slug'     => $this->slug,
            'type'     => $this->type,
            'featured' => $this->featured,
            'category' => $this->relationLoaded('category') && $this->category ? [
                'id'    => $this->category->id,
                'title' => $this->category->title,
                'slug'  => $this->category->slug,
            ] : null,
            'images' => [
                'main_image'        => $this->main_image,
                'additional_images' => $this->additional_images ?? [],
                'variant_images'    => $variantImages,
                'all'               => array_values(array_filter(array_unique(array_merge(
                    array_filter([$this->main_image]),
                    $this->additional_images ?? [],
                    $variantImages
                )))),
            ],
            'tax' => [
                'gst_rate'         => $this->gst_rate,
                'hsn_code'         => $this->hsn_code,
                'is_inclusive_tax' => (bool) $this->is_inclusive_tax,
            ],
            'policies' => [
                'minimum_order_quantity' => $this->minimum_order_quantity,
                'quantity_step_size'     => $this->quantity_step_size,
                'total_allowed_quantity' => $this->total_allowed_quantity,
                'is_returnable'          => (bool) $this->is_returnable,
                'is_cancelable'          => (bool) $this->is_cancelable,
            ],
            'currency' => [
                'symbol' => $currency->getSymbol(),
                'code'   => $currency->getCode(),
            ],
            'variants' => $variants,
        ];
    }
}
