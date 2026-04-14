<?php

namespace Database\Seeders;

use App\Models\GlobalProductAttribute;
use App\Models\GlobalProductAttributeValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GlobalAttributesSeeder extends Seeder
{
    /**
     * Seed global product attributes and their values for seller_id = 1 (admin/Pethiyan).
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        GlobalProductAttributeValue::truncate();
        GlobalProductAttribute::withTrashed()->forceDelete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $attributes = [
            [
                'title'       => 'Color',
                'label'       => 'Color',
                'swatche_type' => 'text',
                'values' => [
                    'Transparent', 'Brown', 'Colorful', 'Light Gray', 'Dark Gray',
                    'White', 'Black', 'Blue', 'Red', 'Green', 'Yellow', 'Orange',
                    'Purple', 'Pink', 'Beige', 'Navy', 'Maroon', 'Teal', 'Olive', 'Coral',
                ],
            ],
            [
                'title'       => 'Size',
                'label'       => 'Size',
                'swatche_type' => 'text',
                'values' => [
                    // Volume
                    '250 ml',
                    // Inch sizes (flat/sheet)
                    '8 inch', '10 inch', '12 inch', '16 inch',
                    // Tape rolls
                    '2 inch x 100 m', '2 inch x 80 m', '3 inch x 100 m', '3 inch x 80 m',
                    // Flat panels / photo frames
                    '14 x 24 inch', '24 x 24 inch', '8 x 10 inch', '8 x 12 inch', '14 x 19 inch',
                    // Box sizes (L x W x H)
                    '3.30 X 2.75 X 1 inch', '4 X 4 X 1.5 inch', '4.60 X 3.70 X 1.60 inch',
                    '6 X 4 X 2 inch', '5 X 4 X 2 inch', '4 X 4 X 2 inch', '7 X 5 X 2 inch',
                    '6 X 5 X 2 inch', '12 X 10 X 3 inch', '4 X 3 X 2 inch', '10 X 8 X 2 inch',
                    '9 X 7 X 2 inch', '7 X 4 X 2 inch', '10 X 7 X 2 inch', '5 X 3 X 2 inch',
                    '8 X 5 X 2 inch', '8 X 7 X 3 inch', '6 X 5 X 3 inch', '10 X 9 X 4 inch',
                    '8 X 4 X 2 inch', '12 X 10 X 1.75 inch', '9 X 6 X 2 inch', '8 X 4 X 3 inch',
                    '7 X 6 X 3 inch', '9 X 5 X 2 inch', '5 X 5 X 2 inch', '10 X 6 X 2 inch',
                    '10 X 8 X 4 inch', '9 X 7 X 3 inch', '8 X 5 X 3 inch', '7 X 5 X 3 inch',
                    '6 X 4 X 3 inch', '6 X 3 X 2 inch', '6.5 X 3.5 X 2.5 inch', '10 X 9 X 3 inch',
                    '9 X 6 X 3 inch', '8 X 6 X 3 inch', '5 X 4 X 3 inch', '10 X 3 X 2 inch',
                    '8 X 7 X 2 inch', '11 X 7.5 X 4 inch', '9 X 8.25 X 1.60 inch',
                    '10 X 4 X 2 inch', '8 X 3 X 2 inch', '10 X 7 X 3 inch', '7 X 7 X 2 inch',
                    '6 X 6 X 2 inch', '10 X 9.10 X 2.30 inch', '10 X 6 X 3 inch',
                    '9 X 4 X 2 inch', '8 X 7 X 4 inch', '7 X 3 X 2 inch', '6 X 5 X 4 inch',
                    '10 X 5 X 2 inch', '8 X 6 X 4 inch', '7 X 6 X 4 inch', '12 X 6 X 2 inch',
                    '10 X 7 X 4 inch', '9 X 6 X 4 inch', '7 X 4 X 3 inch', '10 X 5 X 3 inch',
                    '7 X 5 X 4 inch', '10 X 4 X 3 inch', '9 X 5 X 3 inch', '9 X 4 X 3 inch',
                    '14 X 13 X 3 inch', '10 X 10 X 3 inch', '7 X 6 X 1.5 inch',
                    '6.5 X 5 X 4 inch', '9 X 7 X 4 inch', '7 X 3 X 2.75 inch',
                    '10 X 5 X 4 inch', '9 X 5 X 4 inch', '9 X 3 X 2 inch',
                    '14 X 10 X 5 inch', '5.5 X 5.5 X 1.5 inch', '22 X 10 X 4 inch',
                    '10 X 10 X 2 inch', '12 X 8 X 5 inch', '13 X 13 X 2 inch',
                    '10 X 6 X 4 inch', '8 X 5 X 4 inch', '12 X 10 X 5 inch',
                    '14 X 14 X 2 inch', '11 X 6 X 3 inch',
                    // mm sizes
                    '73 mm', '85 mm', '63 X 76 X 60 mm', '90 X 140 X 63 mm',
                    '101 X 178 X 101 mm', '127 X 204 X 127 mm', '134 X 216 X 152 mm',
                    '165 X 236 X 152 mm', '183 X 241 X 165 mm', '183 X 279 X 190 mm',
                    '177 X 279 X 190 mm', '177 X 279 X 203 mm', '203 X 304 X 288 mm',
                    '355 X 288 X 254 mm',
                    // Apparel sizes
                    'XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL',
                    // Numeric sizes
                    '28', '30', '32', '34', '36', '38', '40', '42',
                ],
            ],
            [
                'title'       => 'Weight',
                'label'       => 'Weight',
                'swatche_type' => 'text',
                'values' => [
                    '10g', '20g', '50g', '100g', '150g', '250g', '500g',
                    '1kg', '2kg', '3kg', '5kg', '10kg', '15kg', '20kg', '25kg',
                ],
            ],
            [
                'title'       => 'Capacity',
                'label'       => 'Capacity',
                'swatche_type' => 'text',
                'values' => [
                    '50 to 70gm', '70 to 100gm', '100 to 150gm', '150 to 250gm',
                    '250 to 400gm', '500 to 750gm', '750gm to 1kg',
                    '100ml', '200ml', '250ml', '500ml', '750ml', '1L', '2L', '5L',
                ],
            ],
            [
                'title'       => 'Material',
                'label'       => 'Material',
                'swatche_type' => 'text',
                'values' => [
                    'Cotton', 'Polyester', 'Leather', 'Wool', 'Silk', 'Linen',
                    'Denim', 'Nylon', 'Velvet', 'Canvas', 'Suede', 'Rubber',
                    'Plastic', 'Metal', 'Wood', 'Glass', 'Ceramic', 'Jute',
                ],
            ],
            [
                'title'       => 'Storage',
                'label'       => 'Storage',
                'swatche_type' => 'text',
                'values' => [
                    '4GB', '8GB', '16GB', '32GB', '64GB', '128GB', '256GB',
                    '512GB', '1TB', '2TB',
                ],
            ],
            [
                'title'       => 'RAM',
                'label'       => 'RAM',
                'swatche_type' => 'text',
                'values' => [
                    '1GB', '2GB', '3GB', '4GB', '6GB', '8GB', '12GB', '16GB', '32GB',
                ],
            ],
            [
                'title'       => 'Pack Size',
                'label'       => 'Pack Size',
                'swatche_type' => 'text',
                'values' => [
                    'Pack of 1', 'Pack of 2', 'Pack of 3', 'Pack of 4', 'Pack of 5',
                    'Pack of 6', 'Pack of 10', 'Pack of 12', 'Pack of 24', 'Pack of 50',
                    'Pack of 100',
                ],
            ],
        ];

        foreach ($attributes as $attrData) {
            $values = $attrData['values'];
            unset($attrData['values']);

            $attribute = GlobalProductAttribute::create(array_merge($attrData, ['seller_id' => 1]));

            foreach ($values as $title) {
                GlobalProductAttributeValue::create([
                    'global_attribute_id' => $attribute->id,
                    'title'               => $title,
                    'swatche_value'       => null,
                ]);
            }
        }

        $this->command->info('Global attributes seeded: ' . count($attributes) . ' attributes.');
    }
}
