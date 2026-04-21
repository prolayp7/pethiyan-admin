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
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttribute;
use App\Models\Review;
use App\Models\StoreProductVariant;
use App\Enums\SpatieMediaCollectionName;
use App\Services\ImageWebpService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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

    public function duplicateProduct(Product $sourceProduct, int $sellerId): Product
    {
        return DB::transaction(function () use ($sourceProduct, $sellerId) {
            $sourceProduct->loadMissing(['taxClasses', 'variants.attributes', 'variants.storeProductVariants']);

            $duplicate = $sourceProduct->replicate([
                'uuid',
                'slug',
                'status',
                'verification_status',
                'rejection_reason',
                'created_at',
                'updated_at',
                'deleted_at',
            ]);

            $duplicate->seller_id = $sellerId;
            $duplicate->cloned_from_id = $sourceProduct->id;
            $duplicate->status = ProductStatusEnum::DRAFT();
            $duplicate->verification_status = ProductVarificationStatusEnum::APPROVED();
            $duplicate->rejection_reason = null;
            $duplicate->title = $this->makeDuplicateTitle($sourceProduct->title);
            $duplicate->save();

            $duplicate->taxClasses()->sync($sourceProduct->taxClasses->pluck('id')->all());

            $this->copyMediaCollection($sourceProduct, $duplicate, SpatieMediaCollectionName::PRODUCT_MAIN_IMAGE);
            $this->copyMediaCollection($sourceProduct, $duplicate, SpatieMediaCollectionName::PRODUCT_ADDITIONAL_IMAGE);
            $this->copyMediaCollection($sourceProduct, $duplicate, SpatieMediaCollectionName::PRODUCT_VIDEO);

            foreach ($sourceProduct->variants as $sourceVariant) {
                $duplicateVariant = $sourceVariant->replicate([
                    'uuid',
                    'slug',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ]);

                $duplicateVariant->product_id = $duplicate->id;
                $duplicateVariant->uuid = (string) Str::uuid();
                $duplicateVariant->title = $sourceVariant->title;
                $duplicateVariant->save();

                foreach ($sourceVariant->attributes as $attribute) {
                    $duplicateAttribute = $attribute->replicate([
                        'created_at',
                        'updated_at',
                        'deleted_at',
                    ]);
                    $duplicateAttribute->product_id = $duplicate->id;
                    $duplicateAttribute->product_variant_id = $duplicateVariant->id;
                    $duplicateAttribute->save();
                }

                foreach ($sourceVariant->storeProductVariants as $storeVariant) {
                    $duplicateStoreVariant = $storeVariant->replicate([
                        'created_at',
                        'updated_at',
                        'deleted_at',
                    ]);
                    $duplicateStoreVariant->product_variant_id = $duplicateVariant->id;
                    $duplicateStoreVariant->save();
                }

                $this->copyMediaCollection($sourceVariant, $duplicateVariant, SpatieMediaCollectionName::VARIANT_IMAGE);
            }

            return $duplicate->fresh(['taxClasses', 'variants.attributes', 'variants.storeProductVariants']);
        });
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
                $variant->update([
                    'title' => !empty($variantData['title']) ? $variantData['title'] : null,
                    'availability' => $variantData['availability'] === 'no' ? false : true,
                    'is_default' => $variantData['is_default'] == 'on' ? true : false,
                    'weight' => isset($variantData['weight']) && $variantData['weight'] !== null && $variantData['weight'] !== '' ? $variantData['weight'] : null,
                    'weight_unit' => $variantData['weight_unit'] ?? 'g',
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
                    'availability' => $variantData['availability'] === 'no' ? false : true,
                    'is_default' => $variantData['is_default'] == 'on' ? true : false,
                    'weight' => isset($variantData['weight']) && $variantData['weight'] !== null && $variantData['weight'] !== '' ? $variantData['weight'] : null,
                    'weight_unit' => $variantData['weight_unit'] ?? 'g',
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
        $simpleVariantData = $this->extractSimpleVariantData($request);

        if ($mode === 'update') {
            // Get the existing variant or create a new one if it doesn't exist
            $variant = $product->variants()->first();
        }

        $submittedAvailability = $simpleVariantData['availability'] ?? null;
        $availability = match ($submittedAvailability) {
            'yes', 1, '1', true => true,
            'no', 0, '0', false => false,
            default => $variant?->availability ?? true,
        };

        $submittedWeight = $simpleVariantData['weight'] ?? null;
        $weight = $submittedWeight !== null && $submittedWeight !== ''
            ? $submittedWeight
            : ($variant?->weight ?? null);

        $metadata = array_merge($variant?->metadata ?? [], $simpleVariantData['metadata'] ?? []);

        $variantData = [
            'uuid' => (string)Str::uuid(),
            'product_id' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,
            'availability' => $availability,
            'is_default' => true,
            'weight' => $weight,
            'weight_unit' => $simpleVariantData['weight_unit'] ?? ($variant?->weight_unit ?? 'g'),
            'metadata' => $metadata,
        ];

        if ($variant) {
            $variant->update($variantData);
        } else {
            $variant = ProductVariant::create($variantData);
        }

        $this->syncSimpleVariantAttributes($product, $variant, $request);
        $this->handleVariantSeoImageUploads($variant, $request, (string) ($simpleVariantData['id'] ?? 'v_simple'));

        if (!empty($pricingData['store_pricing'])) {
            // Delete existing store pricing if updating
            if ($mode === 'update') {
                StoreProductVariant::where('product_variant_id', $variant->id)->forceDelete();
            }

            // Create new store pricing
            $this->createStoreProductVariants($variant->id, $pricingData['store_pricing']);
        }
    }

    private function extractSimpleVariantData($request): array
    {
        $variantsJson = is_array($request)
            ? ($request['variants_json'] ?? null)
            : $request->input('variants_json');

        if (empty($variantsJson)) {
            return [];
        }

        $variants = json_decode($variantsJson, true);
        if (!is_array($variants) || empty($variants[0]) || !is_array($variants[0])) {
            return [];
        }

        return $variants[0];
    }

    private function syncSimpleVariantAttributes(Product $product, ProductVariant $variant, $request): void
    {
        $json = $request['simple_attributes_json'] ?? null;
        $incoming = [];

        if (!empty($json)) {
            $parsed = json_decode($json, true);
            if (is_array($parsed)) {
                foreach ($parsed as $entry) {
                    $attrId  = isset($entry['attribute_id'])  ? (int) $entry['attribute_id']  : null;
                    $valueId = isset($entry['value_id'])       ? (int) $entry['value_id']       : null;
                    if ($attrId && $valueId) {
                        $incoming[$attrId] = $valueId;
                    }
                }
            }
        }

        // Remove any attributes that are no longer submitted
        ProductVariantAttribute::where('product_variant_id', $variant->id)
            ->whereNotIn('global_attribute_id', array_keys($incoming))
            ->forceDelete();

        // Upsert each submitted attribute
        foreach ($incoming as $attrId => $valueId) {
            ProductVariantAttribute::updateOrCreate(
                [
                    'product_variant_id'      => $variant->id,
                    'global_attribute_id'     => $attrId,
                ],
                [
                    'product_id'                   => $product->id,
                    'global_attribute_value_id'    => $valueId,
                ]
            );
        }
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

            $specialPrice = (isset($pricing['special_price']) && is_numeric($pricing['special_price']) && (float) $pricing['special_price'] > 0)
                ? (float) $pricing['special_price']
                : null;

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

    private function normalizeHsnCode($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);
        return $raw === '' ? null : $raw;
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

        // Upload the new image with WebP conversion
        $file = request()->file($payload_image);
        if ($file) {
            $converted = ImageWebpService::convert($file);
            $variant->addMedia($converted['path'])
                ->usingFileName($converted['filename'])
                ->toMediaCollection(SpatieMediaCollectionName::VARIANT_IMAGE());
            if ($converted['isWebp']) {
                @unlink($converted['path']);
            }
        } else {
            $variant->addMediaFromRequest($payload_image)->toMediaCollection(SpatieMediaCollectionName::VARIANT_IMAGE());
        }
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

            $file      = $request->file($requestField);
            $converted = ImageWebpService::convert($file);
            $stored    = Storage::disk('public')->put('seo/product-variant', new \Illuminate\Http\File($converted['path']), ['visibility' => 'public']);
            $target    = dirname($stored) . '/' . $converted['filename'];
            if ($stored !== $target) {
                Storage::disk('public')->move($stored, $target);
                $stored = $target;
            }
            if ($converted['isWebp']) {
                @unlink($converted['path']);
            }

            $metadata[$field] = $stored;
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

            $file      = $request->file($field);
            $converted = ImageWebpService::convert($file);
            $stored    = Storage::disk('public')->put('seo/product', new \Illuminate\Http\File($converted['path']), ['visibility' => 'public']);
            $target    = dirname($stored) . '/' . $converted['filename'];
            if ($stored !== $target) {
                Storage::disk('public')->move($stored, $target);
                $stored = $target;
            }
            if ($converted['isWebp']) {
                @unlink($converted['path']);
            }

            $metadata[$field] = $stored;
            $updated = true;
        }

        if ($updated) {
            $product->update([
                'metadata' => array_merge($product->metadata ?? [], $metadata),
            ]);
        }
    }

    private function copyMediaCollection($sourceModel, $targetModel, SpatieMediaCollectionName $collection): void
    {
        $sourceModel->getMedia($collection->value)->each(function (Media $media) use ($targetModel, $collection) {
            $media->copy($targetModel, $collection->value);
        });
    }

    private function makeDuplicateTitle(string $title): string
    {
        $trimmedTitle = trim($title);

        if ($trimmedTitle === '') {
            return 'Product Copy';
        }

        if (preg_match('/\(Copy(?:\s+\d+)?\)$/', $trimmedTitle) === 1) {
            return $trimmedTitle;
        }

        return $trimmedTitle . ' (Copy)';
    }
}
