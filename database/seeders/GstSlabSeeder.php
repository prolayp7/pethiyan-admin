<?php

namespace Database\Seeders;

use App\Models\TaxClass;
use App\Models\TaxRate;
use Illuminate\Database\Seeder;

/**
 * GstSlabSeeder
 *
 * Seeds all 5 official Indian GST tax rates (2026)
 * and creates packaging-specific tax classes for LCommerce.
 *
 * Run: php artisan db:seed --class=GstSlabSeeder
 */
class GstSlabSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Seed Tax Rates (GST Slabs) ────────────────────────────────────
        $rates = [
            [
                'title'       => 'Nil Rate (0% GST)',
                'rate'        => 0,
                'gst_slab'    => '0',
                'cgst_rate'   => 0,
                'sgst_rate'   => 0,
                'igst_rate'   => 0,
                'description' => 'Exempt goods — fresh vegetables, milk, eggs, printed books, newspapers, jute bags',
                'is_gst'      => true,
                'is_active'   => true,
            ],
            [
                'title'       => '5% GST',
                'rate'        => 5,
                'gst_slab'    => '5',
                'cgst_rate'   => 2.5,
                'sgst_rate'   => 2.5,
                'igst_rate'   => 5,
                'description' => 'Basic necessities — sugar, tea, edible oils, handloom fabrics, bio-degradable carry bags',
                'is_gst'      => true,
                'is_active'   => true,
            ],
            [
                'title'       => '12% GST',
                'rate'        => 12,
                'gst_slab'    => '12',
                'cgst_rate'   => 6,
                'sgst_rate'   => 6,
                'igst_rate'   => 12,
                'description' => 'Paper & paperboard packaging (HSN 4819), processed food, computers, business class air tickets',
                'is_gst'      => true,
                'is_active'   => true,
            ],
            [
                'title'       => '18% GST',
                'rate'        => 18,
                'gst_slab'    => '18',
                'cgst_rate'   => 9,
                'sgst_rate'   => 9,
                'igst_rate'   => 18,
                'description' => 'Plastic packaging & pouches (HSN 3923), electronics, most manufactured goods, AC restaurants',
                'is_gst'      => true,
                'is_active'   => true,
            ],
            [
                'title'       => '28% GST',
                'rate'        => 28,
                'gst_slab'    => '28',
                'cgst_rate'   => 14,
                'sgst_rate'   => 14,
                'igst_rate'   => 28,
                'description' => 'Luxury goods, automobiles, tobacco, aerated drinks, demerit goods',
                'is_gst'      => true,
                'is_active'   => true,
            ],
        ];

        $rateModels = [];
        foreach ($rates as $rateData) {
            $rateModels[$rateData['gst_slab']] = TaxRate::updateOrCreate(
                ['gst_slab' => $rateData['gst_slab'], 'is_gst' => true],
                $rateData
            );
        }

        // ── 2. Seed Tax Classes (packaging-specific for LCommerce) ────────────
        $classes = [
            [
                'title'       => 'Plastic Packaging — 18% GST',
                'description' => 'Stand-up pouches, ziplock bags, BOPP bags, laminated pouches (HSN 3923)',
                'is_active'   => true,
                'slab'        => '18',
            ],
            [
                'title'       => 'Paper & Paperboard Packaging — 12% GST',
                'description' => 'Paper bags, cardboard boxes, kraft paper pouches (HSN 4819)',
                'is_active'   => true,
                'slab'        => '12',
            ],
            [
                'title'       => 'Eco & Biodegradable Packaging — 5% GST',
                'description' => 'Biodegradable/compostable bags, jute-based packaging (HSN 6305)',
                'is_active'   => true,
                'slab'        => '5',
            ],
            [
                'title'       => 'Custom Printed Packaging — 18% GST',
                'description' => 'Custom branded pouches, printed flexible packaging (HSN 3923)',
                'is_active'   => true,
                'slab'        => '18',
            ],
            [
                'title'       => 'Food-Grade Packaging — 18% GST',
                'description' => 'FDA-compliant pouches for food items (HSN 3923)',
                'is_active'   => true,
                'slab'        => '18',
            ],
            [
                'title'       => 'Industrial Packaging — 18% GST',
                'description' => 'Heavy-duty bags, FIBC/bulk bags for industrial use (HSN 3923)',
                'is_active'   => true,
                'slab'        => '18',
            ],
            [
                'title'       => 'Exempt / Nil Rated Packaging',
                'description' => 'Packaging items fully exempt from GST',
                'is_active'   => true,
                'slab'        => '0',
            ],
        ];

        foreach ($classes as $classData) {
            $slab = $classData['slab'];
            unset($classData['slab']);

            $taxClass = TaxClass::updateOrCreate(
                ['title' => $classData['title']],
                $classData
            );

            // Attach the matching tax rate
            if (isset($rateModels[$slab])) {
                $taxClass->taxRates()->syncWithoutDetaching([$rateModels[$slab]->id]);
            }
        }

        $this->command->info('✅ GST slabs seeded: 0%, 5%, 12%, 18%, 28%');
        $this->command->info('✅ Packaging tax classes seeded: 7 categories');
    }
}
