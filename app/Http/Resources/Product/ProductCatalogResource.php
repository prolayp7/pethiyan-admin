<?php

namespace App\Http\Resources\Product;

use App\Services\CurrencyService;
use App\Services\GstService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductCatalogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $customerStateCode = $request->input('customer_state_code');
        $gstService = app(GstService::class);
        $currency = app(CurrencyService::class);
        $gstRate = (int) ($this->gst_rate ?? 0);

        $variantImages = $this->variants
            ->pluck('image')
            ->filter()
            ->values()
            ->all();

        $allImages = array_values(array_filter(array_unique(array_merge(
            array_filter([$this->main_image]),
            $this->additional_images ?? [],
            $variantImages
        ))));

        $variants = $this->variants->map(function ($variant) use ($gstService, $gstRate, $customerStateCode) {
            $attributes = [];
            foreach ($variant->attributes as $attribute) {
                if ($attribute->attribute && $attribute->attributeValue) {
                    $attributes[$attribute->attribute->slug] = $attribute->attributeValue->title;
                }
            }

            $storePricing = $variant->storeProductVariants->map(function ($storeVariant) use ($gstService, $gstRate, $customerStateCode) {
                $priceExcludingTax = (float) ($storeVariant->price_exclude_tax ?? $storeVariant->getRawOriginal('price') ?? 0);
                $specialPriceExcludingTax = (float) ($storeVariant->special_price_exclude_tax ?? $storeVariant->getRawOriginal('special_price') ?? 0);

                $effectivePriceExcludingTax = $specialPriceExcludingTax > 0
                    ? $specialPriceExcludingTax
                    : $priceExcludingTax;

                $discountPercent = null;
                if ($specialPriceExcludingTax > 0 && $priceExcludingTax > 0 && $specialPriceExcludingTax < $priceExcludingTax) {
                    $discountPercent = (int) round(($priceExcludingTax - $specialPriceExcludingTax) / $priceExcludingTax * 100);
                }

                $storeStateCode = $storeVariant->store->state_code ?? null;
                $supplyType = $gstService->supplyType($storeStateCode, $customerStateCode);
                $gst = $gstService->calculateLineItem(
                    unitPrice: $effectivePriceExcludingTax,
                    quantity: 1,
                    gstRatePct: $gstRate,
                    supplyType: $supplyType,
                    priceInclusive: (bool) $this->is_inclusive_tax
                );

                return [
                    'store_id' => $storeVariant->store_id,
                    'store_name' => $storeVariant->store->name ?? null,
                    'store_slug' => $storeVariant->store->slug ?? null,
                    'store_state_code' => $storeStateCode,
                    'store_state_name' => $storeVariant->store->state_name ?? null,
                    'sku' => $storeVariant->sku,
                    'price' => $gst['total_amount'],
                    'special_price' => $gst['taxable_amount'],
                    'discount_percent' => $discountPercent,
                    'cost' => $storeVariant->cost,
                    'stock' => (int) ($storeVariant->stock ?? 0),
                    'stock_status' => ((int) ($storeVariant->stock ?? 0) > 0) ? 'in_stock' : 'out_of_stock',
                    'gst' => $gst,
                ];
            })->values()->all();

            $meta = $variant->metadata ?? [];

            return [
                'id' => $variant->id,
                'title' => $variant->title,
                'slug' => $variant->slug,
                'image' => $variant->image,
                'barcode' => $variant->barcode,
                'is_default' => (bool) $variant->is_default,
                'availability' => (bool) $variant->availability,
                'availability_label' => ((bool) $variant->availability) ? 'yes' : 'no',
                'capacity' => $variant->capacity,
                'capacity_unit' => $variant->capacity_unit,
                'weight' => $variant->weight,
                'weight_unit' => $variant->weight_unit,
                'height' => $variant->height,
                'height_unit' => $variant->height_unit,
                'breadth' => $variant->breadth,
                'breadth_unit' => $variant->breadth_unit,
                'length' => $variant->length,
                'length_unit' => $variant->length_unit,
                'attributes' => $attributes,
                'store_pricing' => $storePricing,
                'metadata' => $meta ?: null,
                'is_indexable' => $meta['is_indexable'] ?? true,
                'seo_title' => $meta['seo_title'] ?? null,
                'seo_description' => $meta['seo_description'] ?? null,
                'seo_keywords' => $meta['seo_keywords'] ?? null,
                'og_title' => $meta['og_title'] ?? null,
                'og_description' => $meta['og_description'] ?? null,
                'og_image' => !empty($meta['og_image']) ? url('storage/' . ltrim($meta['og_image'], '/')) : null,
                'twitter_title' => $meta['twitter_title'] ?? null,
                'twitter_description' => $meta['twitter_description'] ?? null,
                'twitter_card' => $meta['twitter_card'] ?? null,
                'twitter_image' => !empty($meta['twitter_image']) ? url('storage/' . ltrim($meta['twitter_image'], '/')) : null,
                'schema_mode' => $meta['schema_mode'] ?? 'auto',
                'schema_json_ld' => $meta['schema_json_ld'] ?? null,
            ];
        })->values()->all();

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'seller_id' => $this->seller_id,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn() => $this->category ? [
                'id'    => $this->category->id,
                'title' => $this->category->title,
                'slug'  => $this->category->slug,
            ] : null),
            'brand_id' => $this->brand_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type,
            'status' => $this->status,
            'featured' => $this->featured,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'tags' => $this->tags,
            'images' => [
                'main_image' => $this->main_image,
                'additional_images' => $this->additional_images,
                'variant_images' => $variantImages,
                'all' => $allImages,
            ],
            'video' => [
                'video_type' => $this->video_type,
                'video_link' => $this->video_link,
            ],
            'features' => [
                'warranty_period' => $this->warranty_period,
                'guarantee_period' => $this->guarantee_period,
                'made_in' => $this->made_in,
                'custom_fields' => $this->custom_fields ?? [],
                'metadata' => $this->metadata ?? [],
                'is_indexable' => $this->is_indexable ?? true,
                'seo_title' => $this->metadata['seo_title'] ?? null,
                'seo_description' => $this->metadata['seo_description'] ?? null,
                'seo_keywords' => $this->metadata['seo_keywords'] ?? null,
                'og_title' => $this->metadata['og_title'] ?? null,
                'og_description' => $this->metadata['og_description'] ?? null,
                'og_image' => $this->resolveMetadataImageUrl('og_image'),
                'twitter_title' => $this->metadata['twitter_title'] ?? null,
                'twitter_description' => $this->metadata['twitter_description'] ?? null,
                'twitter_card' => $this->metadata['twitter_card'] ?? null,
                'twitter_image' => $this->resolveMetadataImageUrl('twitter_image'),
                'schema_mode' => $this->metadata['schema_mode'] ?? 'auto',
                'schema_json_ld' => $this->metadata['schema_json_ld'] ?? null,
            ],
            'policies' => [
                'minimum_order_quantity' => $this->minimum_order_quantity,
                'quantity_step_size' => $this->quantity_step_size,
                'total_allowed_quantity' => $this->total_allowed_quantity,
                'is_returnable' => (bool) $this->is_returnable,
                'returnable_days' => $this->returnable_days,
                'is_cancelable' => (bool) $this->is_cancelable,
                'cancelable_till' => $this->cancelable_till,
                'is_attachment_required' => (bool) $this->is_attachment_required,
                'requires_otp' => (bool) $this->requires_otp,
            ],
            'tax' => [
                'tax_group_ids' => $this->taxClasses->pluck('id')->values()->all(),
                'tax_groups' => $this->taxClasses->pluck('title')->values()->all(),
                'hsn_code' => $this->hsn_code,
                'gst_rate' => $this->gst_rate,
                'is_inclusive_tax' => (bool) $this->is_inclusive_tax,
                'customer_state_code' => $customerStateCode,
            ],
            'currency' => [
                'symbol' => $currency->getSymbol(),
                'code' => $currency->getCode(),
            ],
            'variants' => $variants,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function resolveMetadataImageUrl(string $key): ?string
    {
        $path = $this->metadata[$key] ?? null;

        return !empty($path) ? url('storage/' . ltrim($path, '/')) : null;
    }
}
