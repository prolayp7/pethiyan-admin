<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCondition;
use App\Models\ProductTax;
use App\Models\ProductVariant;
use App\Models\Seller;
use App\Models\Store;
use App\Models\StoreProductVariant;
use App\Models\TaxClass;
use App\Models\TaxRate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TapProductsSeeder extends Seeder
{
    public function run(): void
    {
        $seller = Seller::query()->first();
        if (! $seller) {
            $this->command?->warn('TapProductsSeeder skipped: no seller found.');
            return;
        }

        $store = Store::query()->where('seller_id', $seller->id)->first() ?? Store::query()->first();
        if (! $store) {
            $this->command?->warn('TapProductsSeeder skipped: no store found.');
            return;
        }

        $category = Category::query()
            ->where('slug', 'tape')
            ->orWhere('title', 'Tape')
            ->first();

        if (! $category) {
            $category = Category::query()->create([
                'title' => 'Tape',
                'parent_id' => null,
                'description' => 'Tape products',
                'status' => 'active',
                'requires_approval' => false,
                'commission' => 0,
                'metadata' => [
                    'seo_title' => 'Tape',
                    'seo_keywords' => 'tape, adhesive tapee',
                    'seo_description' => 'Tape products category',
                ],
            ]);
        }

        $productCondition = ProductCondition::query()->where('category_id', $category->id)->first();
        if (! $productCondition) {
            $productCondition = ProductCondition::query()->create([
                'category_id' => $category->id,
                'title' => 'Standard Tape',
                'alignment' => 'strip',
            ]);
        }

        [$taxRate, $taxClass] = $this->resolveTaxEntities();
        $taxClass->taxRates()->syncWithoutDetaching([$taxRate->id]);

        $created = 0;
        $updated = 0;

        foreach ($this->tapeRows() as $row) {
            $title = (string) $row['product'];
            $slug = Str::slug($title);
            $price = (float) $row['price'];
            $minQty = (int) $row['min_quantity'];

            $product = Product::query()->where('slug', $slug)->first();
            $isCreate = ! $product;
            if (! $product) {
                $product = new Product();
            }

            $product->fill([
                'seller_id' => $seller->id,
                'category_id' => $category->id,
                'product_condition_id' => $productCondition->id,
                'title' => $title,
                'type' => 'variant',
                'short_description' => "{$row['color']} adhesive tapee",
                'description' => $title,
                'minimum_order_quantity' => $minQty,
                'quantity_step_size' => 1,
                'total_allowed_quantity' => 9999,
                'is_inclusive_tax' => '0',
                'is_returnable' => '0',
                'is_cancelable' => '0',
                'is_attachment_required' => '0',
                'base_prep_time' => 15,
                'status' => 'active',
                'verification_status' => 'approved',
                'featured' => '0',
                'requires_otp' => false,
                'tags' => json_encode(['tape', 'adhesive tapee', strtolower((string) $row['color'])]),
                'hsn_code' => (string) $row['hsn_code'],
                'gst_rate' => (string) $row['tax_gst'],
                'metadata' => [
                    'source' => 'products-tape.xlsx',
                    'excel_total' => (float) $row['total'],
                    'excel_color' => (string) $row['color'],
                    'excel_weight' => (string) $row['weight'],
                ],
                'image_fit' => 'contain',
            ]);
            $product->save();

            if ($isCreate) {
                $created++;
            } else {
                $updated++;
            }

            $product->categories()->syncWithoutDetaching([$category->id]);

            ProductTax::query()->updateOrCreate(
                ['product_id' => $product->id, 'tax_class_id' => $taxClass->id],
                []
            );

            $variantTitle = "{$title} - {$row['color']}";
            $variant = ProductVariant::query()->firstOrNew([
                'product_id' => $product->id,
                'is_default' => true,
            ]);

            $variant->fill([
                'title' => $variantTitle,
                'weight' => $this->parseWeightGram((string) $row['weight']),
                'height' => null,
                'breadth' => null,
                'length' => null,
                'availability' => true,
                'provider' => 'self',
                'barcode' => $this->generateBarcode($slug),
                'visibility' => 'published',
                'is_default' => true,
            ]);
            $variant->save();

            $sku = strtoupper('TAP-' . Str::slug($variantTitle, '-'));
            StoreProductVariant::query()->updateOrCreate(
                ['product_variant_id' => $variant->id, 'store_id' => $store->id],
                [
                    'sku' => substr($sku, 0, 100),
                    'price' => $price,
                    'special_price' => $price,
                    'cost' => $price,
                    'stock' => max($minQty, 500),
                ]
            );
        }

        $this->command?->info("TapeProductsSeeder completed. Created: {$created}, Updated: {$updated}");
    }

    /**
     * @return array<int, array<string, int|float|string>>
     */
    private function tapeRows(): array
    {
        return [
            ['category' => 'Tape', 'hsn_code' => '3919', 'product' => '2 Inch X 100 Meters plain transparent adhesive tapee', 'color' => 'Transparent', 'min_quantity' => 6, 'price' => 89, 'tax_gst' => 18, 'total' => 105.02, 'weight' => '100g'],
            ['category' => 'Tape', 'hsn_code' => '3919', 'product' => '2 Inch X 100 Meters Brown adhesive tapee', 'color' => 'Brown', 'min_quantity' => 6, 'price' => 89, 'tax_gst' => 18, 'total' => 105.02, 'weight' => '100g'],
            ['category' => 'Tape', 'hsn_code' => '3919', 'product' => 'Mesho Printed 2 Inch 80 Meters adhesive tapee', 'color' => 'Colorful', 'min_quantity' => 6, 'price' => 94, 'tax_gst' => 18, 'total' => 110.92, 'weight' => '100g'],
            ['category' => 'Tape', 'hsn_code' => '3919', 'product' => '3 Inch X 100 Meters Brown adhesive tapee', 'color' => 'Brown', 'min_quantity' => 6, 'price' => 89, 'tax_gst' => 18, 'total' => 105.02, 'weight' => '100g'],
            ['category' => 'Tape', 'hsn_code' => '3919', 'product' => 'Flippkart Fragile Printed 2 Inch 80 Meters adhesive tapee', 'color' => 'Colorful', 'min_quantity' => 6, 'price' => 89, 'tax_gst' => 18, 'total' => 105.02, 'weight' => '100g'],
            ['category' => 'Tape', 'hsn_code' => '3919', 'product' => '3 Inch X 100 Meters plain transparent adhesive tapee', 'color' => 'Transparent', 'min_quantity' => 6, 'price' => 94, 'tax_gst' => 18, 'total' => 110.92, 'weight' => '100g'],
            ['category' => 'Tape', 'hsn_code' => '3919', 'product' => 'Amazone Printed 2 Inch 80 Meters adhesive tapee', 'color' => 'Colorful', 'min_quantity' => 6, 'price' => 89, 'tax_gst' => 18, 'total' => 105.02, 'weight' => '100g'],
        ];
    }

    /**
     * @return array{0: TaxRate, 1: TaxClass}
     */
    private function resolveTaxEntities(): array
    {
        $taxRate = TaxRate::query()->firstOrCreate(
            ['gst_slab' => '18', 'is_gst' => true],
            [
                'title' => '18% GST',
                'rate' => 18,
                'cgst_rate' => 9,
                'sgst_rate' => 9,
                'igst_rate' => 18,
                'description' => 'Standard GST 18% for tape products',
                'is_active' => true,
            ]
        );

        $taxClass = TaxClass::query()->firstOrCreate(
            ['title' => 'Tape Products - 18% GST'],
            [
                'description' => 'Tax class for tape product imports from Excel',
                'is_active' => true,
            ]
        );

        return [$taxRate, $taxClass];
    }

    private function parseWeightGram(string $rawWeight): float
    {
        $value = trim(strtolower($rawWeight));
        $numeric = (float) preg_replace('/[^0-9.]/', '', $value);

        if (str_ends_with($value, 'kg')) {
            return $numeric * 1000;
        }

        return $numeric;
    }

    private function generateBarcode(string $seed): string
    {
        return 'TAP' . strtoupper(substr(md5($seed), 0, 12));
    }
}
