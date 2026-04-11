<?php

namespace App\Services;

use App\Enums\Product\ProductStatusEnum;
use App\Enums\Product\ProductTypeEnum;
use App\Enums\Product\ProductVarificationStatusEnum;
use App\Enums\Product\ProductVideoTypeEnum;
use App\Events\Product\ProductAfterUpdate;
use App\Events\Product\ProductStatusAfterUpdate;
use App\Http\Resources\User\ReviewResource;
use App\Models\Category;
use App\Models\GlobalProductAttribute;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttribute;
use App\Models\Review;
use App\Models\StoreProductVariant;
use App\Enums\SpatieMediaCollectionName;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    public static function getProductWithVariants(int $productId)
    {
        return Product::with(['variants.attributes', 'variants.storeProductVariants', 'taxClasses'])->find($productId);
    }

    public function updateProduct(Product $product, array $validated, $request): array
    {
        return $this->processProduct($product, $validated, $request, 'update');
    }

    public function storeProduct(array $validated, $request): array
    {
        return $this->processProduct(null, $validated, $request, 'create');
    }

    /**
     * @throws \Exception
     */
    private function processProduct(?Product $product, array $validated, $request, string $mode): array
    {
        DB::beginTransaction();
        try {
            // Option 1: persist product-level GST override only when explicitly selected.
            $validated['gst_rate'] = $this->normalizeGstRate(
                $validated['gst_rate'] ?? $request->input('gst_rate')
            );
            $validated['hsn_code'] = $this->normalizeHsnCode(
                $validated['hsn_code'] ?? $request->input('hsn_code')
            );

            if ($mode === 'create') {
                $product = $this->createProduct($validated);
            } else {
                $this->updateProductDetails($product, $validated);
            }
            if (!empty($validated['tax_group_id'])) {
                $product->taxClasses()->sync([$validated['tax_group_id']]);
            } else {
                $product->taxClasses()->detach();
            }
            $pricingData = json_decode($validated['pricing'], true);
            // Decide based on incoming request, so we can switch type on update as well
            $incomingIsVariant = ($validated['type'] ?? $product->type) === 'variant' && isset($validated['variants_json']);
            $isVariant = $incomingIsVariant;

            if ($isVariant) {
                $this->processVariantProduct($product, $validated, $pricingData, $mode, $request);
            } else {
                // If switching from variant -> simple during update, clean up old variants first
                if ($mode === 'update' && $product->type === 'variant') {
                    $this->cleanupAllVariants($product);
                }
                $this->processSimpleProduct($product, $request, $pricingData, $mode);
            }

            $this->handleMediaUploads($product, $request);
            DB::commit();
            return [
                'success' => true,
                'product' => $product,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function createProduct(array $validated)
    {
        $product = Product::create([
            'seller_id' => $validated['seller_id'],
            'category_id' => $validated['category_id'],
            'brand_id' => $validated['brand_id'] ?? null,
            'title' => $validated['title'],
            'type' => $validated['type'],
            'base_prep_time' => $validated['base_prep_time'] ?? 0,
            'short_description' => $validated['short_description'],
            'description' => $validated['description'],
            'indicator' => $validated['indicator'] ?? null,
            'image_fit' => $validated['image_fit'] ?? 'cover',
            'hsn_code' => $validated['hsn_code'] ?? null,
            'gst_rate' => $validated['gst_rate'] ?? null,
            'minimum_order_quantity' => $validated['minimum_order_quantity'] ?? 1,
            'quantity_step_size' => $validated['quantity_step_size'] ?? 1,
            'total_allowed_quantity' => $validated['total_allowed_quantity'] ?? null,
            'is_returnable' => (string)($validated['is_returnable'] ?? 0),
            'returnable_days' => $validated['returnable_days'] ?? null,
            'is_cancelable' => (string)($validated['is_cancelable'] ?? 0),
            'cancelable_till' => $validated['cancelable_till'] ?? null,
            'is_attachment_required' => (string)($validated['is_attachment_required'] ?? 0),
            'featured' => (string)($validated['featured'] ?? 0),
            'is_top_product' => (bool)($validated['is_top_product'] ?? false),
            'requires_otp' => (string)($validated['requires_otp'] ?? 0),
            'video_type' => $validated['video_type'],
            'warranty_period' => $validated['warranty_period'] ?? null,
            'guarantee_period' => $validated['guarantee_period'] ?? null,
            'made_in' => $validated['made_in'] ?? null,
            'tags' => json_encode($validated['tags'] ?? []),
            'custom_fields' => $validated['custom_fields'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
            'is_indexable' => $validated['is_indexable'] ?? true,
        ]);
        $category = Category::findOrFail($validated['category_id']);
        if ($category->requires_approval) {
            $product->setStatusAttribute(ProductStatusEnum::DRAFT());
            $product->setVerificationStatus(ProductVarificationStatusEnum::PENDING());
        } else {
            $product->setStatusAttribute(ProductStatusEnum::ACTIVE());
            $product->setVerificationStatus(ProductVarificationStatusEnum::APPROVED());
        }
        $product->save();
        event(new ProductStatusAfterUpdate($product));
        return $product;
    }

    private function isVariantProduct(array $validated): bool
    {
        return $validated['type'] === 'variant' && isset($validated['variants_json']);
    }

    private function processVariantProduct($product, array $validated, array $pricingData, string $mode, $request): void
    {
        $variantsData = json_decode($validated['variants_json'], true);
        $newVariantIds = [];
        // Get existing variants if updating
        $existingVariants = ($mode === 'update')
            ? $product->variants()->with('attributes')->get()
            : collect();
        $existingVariantIds = $existingVariants->pluck('id')->toArray();
        foreach ($variantsData as $variantData) {
            $variant = null;
            $imageName = 'variant_image' . $variantData['id'];

            if ($mode === 'update' && $this->isPersistedVariantId($variantData['id'] ?? null)) {
                $variant = $existingVariants->firstWhere('id', (int)$variantData['id']);
            }

            // Try to find matching variant if updating
            if ($mode === 'update' && !$variant && !empty($variantData['attributes']) && $this->shouldMatchExistingVariant($variantData)) {
                $variant = $this->findMatchingVariant($existingVariants, $variantData, $newVariantIds);
            }
            if ($variant) {
                // Update existing variant
                //                    !empty($variantData['weight']) ? (float)$variantData['weight'] : null
//!empty($variantData['height']) ? (float)$variantData['height'] : null
//!empty($variantData['breadth']) ? (float)$variantData['breadth'] : null
//!empty($variantData['length']) ? (float)$variantData['length'] : null
                $variant->update([
                    'title' => !empty($variantData['title']) ? $variantData['title'] : null,
                    'capacity' => $this->normalizeDimensionValue($variantData['capacity'] ?? null),
                    'capacity_unit' => $this->normalizeUnitValue($variantData['capacity_unit'] ?? null, 'ml'),
                    'weight' => $this->normalizeDimensionValue($variantData['weight'] ?? null),
                    'weight_unit' => $this->normalizeUnitValue($variantData['weight_unit'] ?? null, 'kg'),
                    'height' => $this->normalizeDimensionValue($variantData['height'] ?? null),
                    'height_unit' => $this->normalizeUnitValue($variantData['height_unit'] ?? null, 'cm'),
                    'breadth' => $this->normalizeDimensionValue($variantData['breadth'] ?? null),
                    'breadth_unit' => $this->normalizeUnitValue($variantData['breadth_unit'] ?? null, 'cm'),
                    'length' => $this->normalizeDimensionValue($variantData['length'] ?? null),
                    'length_unit' => $this->normalizeUnitValue($variantData['length_unit'] ?? null, 'cm'),
                    'availability' => $variantData['availability'] === 'no' ? false : true,
                    'barcode' => !empty($variantData['barcode']) ? $variantData['barcode'] : null,
                    'is_default' => $variantData['is_default'] == 'on' ? true : false,
                    'metadata' => array_merge($variant->metadata ?? [], $variantData['metadata'] ?? []),
                ]);


                // Update variant attributes
                if (!empty($variantData['attributes'])) {
                    $variant->attributes()->forceDelete();
                    $this->createVariantAttributes(productId: $product->id, variantId: $variant->id, attributes: $variantData['attributes']);
                }

                $newVariantIds[] = $variant->id;
            } else {
                // Create new variant
                $variant = ProductVariant::create([
                    'uuid' => (string)Str::uuid(),
                    'product_id' => $product->id,
                    'title' => !empty($variantData['title']) ? $variantData['title'] : null,
                    'capacity' => $this->normalizeDimensionValue($variantData['capacity'] ?? null),
                    'capacity_unit' => $this->normalizeUnitValue($variantData['capacity_unit'] ?? null, 'ml'),
                    'weight' => $this->normalizeDimensionValue($variantData['weight'] ?? null),
                    'weight_unit' => $this->normalizeUnitValue($variantData['weight_unit'] ?? null, 'kg'),
                    'height' => $this->normalizeDimensionValue($variantData['height'] ?? null),
                    'height_unit' => $this->normalizeUnitValue($variantData['height_unit'] ?? null, 'cm'),
                    'breadth' => $this->normalizeDimensionValue($variantData['breadth'] ?? null),
                    'breadth_unit' => $this->normalizeUnitValue($variantData['breadth_unit'] ?? null, 'cm'),
                    'length' => $this->normalizeDimensionValue($variantData['length'] ?? null),
                    'length_unit' => $this->normalizeUnitValue($variantData['length_unit'] ?? null, 'cm'),
                    'availability' => $variantData['availability'] === 'no' ? false : true,
                    'barcode' => !empty($variantData['barcode']) ? $variantData['barcode'] : null,
                    'is_default' => $variantData['is_default'] == 'on' ? true : false,
                    'metadata' => $variantData['metadata'] ?? null,
                ]);


                if (!empty($variantData['attributes'])) {
                    $this->createVariantAttributes(productId: $product->id, variantId: $variant->id, attributes: $variantData['attributes']);
                }

                $newVariantIds[] = $variant->id;
            }

            if ($request->hasFile($imageName)) {
                $this->handleVariantMediaUploads($variant, $imageName);
            }
            $this->handleVariantSeoImageUploads($variant, $request, (string) ($variantData['id'] ?? ''));
            // Handle store pricing for this variant
            $this->handleVariantPricing($variant, $variantData, $pricingData, $mode);
        }

        // Delete variants that are no longer in the updated data (only when updating)
        if ($mode === 'update' && !empty($existingVariantIds)) {
            $variantsToDelete = array_diff($existingVariantIds, $newVariantIds);
            if (!empty($variantsToDelete)) {
                ProductVariant::whereIn('id', $variantsToDelete)->delete();
            }
        }
    }

    /**
     * Custom variants created from the "Add Variant" button use ids like "v_custom_*".
     * They must always be treated as new variants in update mode and should not be matched
     * against existing variants by attribute combination.
     */
    private function shouldMatchExistingVariant(array $variantData): bool
    {
        $variantId = (string)($variantData['id'] ?? '');
        if ($variantId === '') {
            return true;
        }

        return !str_starts_with($variantId, 'v_custom_');
    }

    private function isPersistedVariantId($variantId): bool
    {
        if ($variantId === null) {
            return false;
        }
        return ctype_digit((string)$variantId);
    }

    private function findMatchingVariant($existingVariants, $variantData, $alreadyMatchedIds)
    {
        // Create a map of attribute_id => value_id for easier comparison
        $variantAttributeMap = [];
        foreach ($variantData['attributes'] as $attr) {
            $variantAttributeMap[$attr['attribute_id']] = $attr['value_id'];
        }

        // Check each existing variant for a match
        foreach ($existingVariants as $existingVariant) {
            // Skip if this variant has already been matched
            if (in_array($existingVariant->id, $alreadyMatchedIds)) {
                continue;
            }

            // Get existing variant attributes
            $existingAttributes = $existingVariant->attributes;

            // If attribute count doesn't match, it's not the same variant
            if (count($existingAttributes) !== count($variantAttributeMap)) {
                continue;
            }

            // Check if all attributes match
            $allMatch = true;
            foreach ($existingAttributes as $attr) {
                if (!isset($variantAttributeMap[$attr->global_attribute_id]) ||
                    $variantAttributeMap[$attr->global_attribute_id] != $attr->global_attribute_value_id) {
                    $allMatch = false;
                    break;
                }
            }

            if ($allMatch) {
                return $existingVariant;
            }
        }

        return null;
    }

    private function handleVariantPricing($variant, $variantData, $pricingData, $mode): void
    {
        if (empty($pricingData['variant_pricing'])) {
            return;
        }

        // Delete existing store pricing if updating
        if ($mode === 'update') {
            StoreProductVariant::where('product_variant_id', $variant->id)->forceDelete();
        }

        // Find pricing data for this variant
        $variantPricing = array_filter(
            $pricingData['variant_pricing'],
            fn($vp) => isset($vp['variant_id']) && (string)$vp['variant_id'] === (string)$variantData['id']
        );

        // Create new store pricing
        if (!empty($variantPricing)) {
            $this->createStoreProductVariants($variant->id, $variantPricing);
        }
    }

    private function processSimpleProduct($product, $request, array $pricingData, string $mode): void
    {
        $variant = null;

        if ($mode === 'update') {
            // Get the existing variant or create a new one if it doesn't exist
            $variant = $product->variants()->first();
        }

        $variantData = [
            'uuid' => (string)Str::uuid(),
            'product_id' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,
            'capacity' => $this->normalizeDimensionValue($request['capacity'] ?? null),
            'capacity_unit' => $this->normalizeUnitValue($request['capacity_unit'] ?? null, 'ml'),
            'weight' => $this->normalizeDimensionValue($request['weight'] ?? null),
            'weight_unit' => $this->normalizeUnitValue($request['weight_unit'] ?? null, 'kg'),
            'height' => $this->normalizeDimensionValue($request['height'] ?? null),
            'height_unit' => $this->normalizeUnitValue($request['height_unit'] ?? null, 'cm'),
            'breadth' => $this->normalizeDimensionValue($request['breadth'] ?? null),
            'breadth_unit' => $this->normalizeUnitValue($request['breadth_unit'] ?? null, 'cm'),
            'length' => $this->normalizeDimensionValue($request['length'] ?? null),
            'length_unit' => $this->normalizeUnitValue($request['length_unit'] ?? null, 'cm'),
            'barcode' => !empty($request['barcode']) ? $request['barcode'] : null,
            'availability' => 1,
            'is_default' => true,
        ];

        if ($variant) {
            $variant->update($variantData);
        } else {
            $variant = ProductVariant::create($variantData);
        }

        $this->syncSimpleVariantColorAttribute($product, $variant, $request);

        if (!empty($pricingData['store_pricing'])) {
            // Delete existing store pricing if updating
            if ($mode === 'update') {
                StoreProductVariant::where('product_variant_id', $variant->id)->forceDelete();
            }

            // Create new store pricing
            $this->createStoreProductVariants($variant->id, $pricingData['store_pricing']);
        }
    }

    private function syncSimpleVariantColorAttribute(Product $product, ProductVariant $variant, $request): void
    {
        $selectedColorValueId = $request['color_value_id'] ?? null;
        $selectedColorValueId = !empty($selectedColorValueId) ? (int)$selectedColorValueId : null;

        $colorAttribute = GlobalProductAttribute::where('seller_id', $product->seller_id)
            ->whereRaw('LOWER(title) = ?', ['color'])
            ->first();

        if (!$colorAttribute) {
            return;
        }

        $existingColorAttr = ProductVariantAttribute::where('product_variant_id', $variant->id)
            ->where('global_attribute_id', $colorAttribute->id)
            ->first();

        if (!$selectedColorValueId) {
            if ($existingColorAttr) {
                $existingColorAttr->forceDelete();
            }
            return;
        }

        $isValidColorValue = $colorAttribute->values()
            ->where('id', $selectedColorValueId)
            ->exists();

        if (!$isValidColorValue) {
            throw new \InvalidArgumentException('Selected color is not valid for this seller.');
        }

        if ($existingColorAttr) {
            $existingColorAttr->update([
                'global_attribute_value_id' => $selectedColorValueId,
            ]);
            return;
        }

        ProductVariantAttribute::create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'global_attribute_id' => $colorAttribute->id,
            'global_attribute_value_id' => $selectedColorValueId,
        ]);
    }

    private function createVariantAttributes(int $productId, int $variantId, array $attributes): void
    {
        foreach ($attributes as $attribute) {
            ProductVariantAttribute::create([
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'global_attribute_id' => $attribute['attribute_id'],
                'global_attribute_value_id' => $attribute['value_id'],
            ]);
        }
    }

    private function createStoreProductVariants(int $variantId, array $storePricings): void
    {
        foreach ($storePricings as $pricing) {
            $price = isset($pricing['price']) && is_numeric($pricing['price'])
                ? (float) $pricing['price']
                : null;

            // Temporary fallback while Special Price and Cost fields are hidden in UI.
            $specialPrice = (isset($pricing['special_price']) && is_numeric($pricing['special_price']))
                ? (float) $pricing['special_price']
                : $price;

            $cost = (isset($pricing['cost']) && is_numeric($pricing['cost']))
                ? (float) $pricing['cost']
                : $price;

            StoreProductVariant::create([
                'product_variant_id' => $variantId,
                'store_id' => $pricing['store_id'],
                'price' => $price,
                'sku' => $pricing['sku'],
                'special_price' => $specialPrice,
                'cost' => $cost,
                'stock' => $pricing['stock'] ?? 0,
            ]);
        }
    }

    private function updateProductDetails(Product $product, array $validated): void
    {
        $product->update([
            // Allow type to be updated so switching simple <-> variant is possible
            'type' => $validated['type'] ?? $product->type,
            'category_id' => $validated['category_id'],
            'brand_id' => $validated['brand_id'] ?? null,
            'title' => $validated['title'],
            'base_prep_time' => $validated['base_prep_time'] ?? 0,
            'short_description' => $validated['short_description'],
            'description' => $validated['description'],
            'indicator' => $validated['indicator'] ?? null,
            'image_fit' => $validated['image_fit'] ?? $product->image_fit,
            'hsn_code' => $validated['hsn_code'] ?? null,
            'gst_rate' => $validated['gst_rate'] ?? null,
            'minimum_order_quantity' => $validated['minimum_order_quantity'] ?? 1,
            'quantity_step_size' => $validated['quantity_step_size'] ?? 1,
            'total_allowed_quantity' => $validated['total_allowed_quantity'] ?? null,
            'is_returnable' => (string)($validated['is_returnable'] ?? 0),
            'returnable_days' => $validated['returnable_days'] ?? null,
            'is_cancelable' => (string)($validated['is_cancelable'] ?? 0),
            'cancelable_till' => $validated['cancelable_till'] ?? null,
            'is_attachment_required' => (string)($validated['is_attachment_required'] ?? 0),
            'featured' => (string)($validated['featured'] ?? 0),
            'is_top_product' => (bool)($validated['is_top_product'] ?? false),
            'requires_otp' => (string)($validated['requires_otp'] ?? 0),
            'video_type' => $validated['video_type'],
            'warranty_period' => $validated['warranty_period'] ?? null,
            'guarantee_period' => $validated['guarantee_period'] ?? null,
            'made_in' => $validated['made_in'] ?? null,
            'tags' => json_encode($validated['tags'] ?? []),
            'custom_fields' => $validated['custom_fields'] ?? null,
            'metadata' => array_merge($product->metadata ?? [], $validated['metadata'] ?? []),
            'is_indexable' => $validated['is_indexable'] ?? true,
        ]);
        $category = Category::findOrFail($validated['category_id']);
        if ($category->requires_approval) {
            $product->setStatusAttribute(ProductStatusEnum::DRAFT());
            $product->setVerificationStatus(ProductVarificationStatusEnum::PENDING());
        } else {
            $product->setStatusAttribute(ProductStatusEnum::ACTIVE());
            $product->setVerificationStatus(ProductVarificationStatusEnum::APPROVED());
        }
        $product->save();
        event(new ProductStatusAfterUpdate($product));

    }

    private function normalizeGstRate($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $allowed = ['0', '5', '12', '18', '28'];
        return in_array($raw, $allowed, true) ? $raw : null;
    }

    private function normalizeDimensionValue($value): ?float
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '' || !is_numeric($raw)) {
            return null;
        }

        return (float) $raw;
    }

    private function normalizeHsnCode($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);
        return $raw === '' ? null : $raw;
    }

    private function normalizeUnitValue($value, string $default): string
    {
        $raw = trim((string) ($value ?? ''));
        return $raw !== '' ? $raw : $default;
    }

    /**
     * Clean up all variants and their related records for a product.
     * Used when switching from variant product to simple product during update.
     */
    private function cleanupAllVariants(Product $product): void
    {
        $variantIds = $product->variants()->pluck('id')->toArray();
        if (empty($variantIds)) {
            return;
        }
        // Delete related store product variants and attributes first
        StoreProductVariant::whereIn('product_variant_id', $variantIds)->forceDelete();
        ProductVariantAttribute::whereIn('product_variant_id', $variantIds)->forceDelete();
        // Now delete the variants themselves (force delete to clean media as well)
        ProductVariant::whereIn('id', $variantIds)->forceDelete();
    }

    private function handleVariantMediaUploads($variant, $payload_image): void
    {
        // Remove the existing main image
        $variant->clearMediaCollection(SpatieMediaCollectionName::VARIANT_IMAGE());
        // Upload the new image
        $variant->addMediaFromRequest($payload_image)->toMediaCollection(SpatieMediaCollectionName::VARIANT_IMAGE());
    }

    private function handleVariantSeoImageUploads(ProductVariant $variant, $request, string $variantId): void
    {
        if ($variantId === '') {
            return;
        }

        $metadata = $variant->metadata ?? [];
        $updated = false;

        foreach (['og_image', 'twitter_image'] as $field) {
            $requestField = 'variant_' . $field . $variantId;
            if (!$request->hasFile($requestField)) {
                continue;
            }

            $metadata[$field] = $request->file($requestField)->store('seo/product-variant', 'public');
            $updated = true;
        }

        if ($updated) {
            $variant->update([
                'metadata' => array_merge($variant->metadata ?? [], $metadata),
            ]);
        }
    }

    private function handleMediaUploads($product, $request): void
    {
        if ($request->hasFile('main_image')) {
            // Remove existing main image
            $product->clearMediaCollection(SpatieMediaCollectionName::PRODUCT_MAIN_IMAGE());
            // Upload new main image
            SpatieMediaService::upload(model: $product, media: SpatieMediaCollectionName::PRODUCT_MAIN_IMAGE());
        }

        if ($request->hasFile('additional_images')) {
            // Remove existing additional images if requested
            $product->clearMediaCollection(SpatieMediaCollectionName::PRODUCT_ADDITIONAL_IMAGE());

            // Upload new additional images
            foreach ($request->file('additional_images') as $image) {
                SpatieMediaService::uploadFromRequest($product, $image, SpatieMediaCollectionName::PRODUCT_ADDITIONAL_IMAGE());
            }
        }

        if (ProductVideoTypeEnum::LOCAL() === $request->video_type) {
            if ($request->hasFile('product_video')) {
                // Remove existing video
                $product->clearMediaCollection(SpatieMediaCollectionName::PRODUCT_VIDEO());
                // Upload new video
                SpatieMediaService::upload(model: $product, media: SpatieMediaCollectionName::PRODUCT_VIDEO());
            }
        } else {
            $product->update(['video_link' => $request['video_link']]);
        }

        $this->handleSeoImageUploads($product, $request);
    }

    private function handleSeoImageUploads(Product $product, $request): void
    {
        $metadata = $product->metadata ?? [];
        $updated = false;

        foreach (['og_image', 'twitter_image'] as $field) {
            if (!$request->hasFile($field)) {
                continue;
            }

            $metadata[$field] = $request->file($field)->store('seo/product', 'public');
            $updated = true;
        }

        if ($updated) {
            $product->update([
                'metadata' => array_merge($product->metadata ?? [], $metadata),
            ]);
        }
    }
}
