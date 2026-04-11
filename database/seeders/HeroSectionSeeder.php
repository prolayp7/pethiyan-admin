<?php

namespace Database\Seeders;

use App\Models\HeroSlide;
use App\Models\HeroTrustBadge;
use Illuminate\Database\Seeder;

class HeroSectionSeeder extends Seeder
{
    public function run(): void
    {
        // ── Slides (from HeroSection10.tsx) ─────────────────────────────
        $slides = [
            [
                'eyebrow'            => 'Premium Packaging Excellence',
                'heading'            => "Packaging That Protects,\nPresents, and Performs",
                'description'        => 'Discover high-quality stand-up pouches and flexible packaging solutions designed to elevate your products and strengthen brand impact.',
                'primary_cta_label'  => 'Explore Products',
                'primary_cta_href'   => '/shop',
                'secondary_cta_label'=> 'Request Quote',
                'secondary_cta_href' => '/contact',
                'image'              => null,
                'sort_order'         => 1,
            ],
            [
                'eyebrow'            => 'Sustainable Innovation',
                'heading'            => "Eco-Friendly Packaging\nfor Modern Brands",
                'description'        => 'Reduce environmental impact with sustainable packaging crafted for durability, presentation, and responsible growth.',
                'primary_cta_label'  => 'Discover Eco Packaging',
                'primary_cta_href'   => '/categories/eco-packaging',
                'secondary_cta_label'=> 'View Solutions',
                'secondary_cta_href' => '/shop',
                'image'              => null,
                'sort_order'         => 2,
            ],
            [
                'eyebrow'            => 'Custom Brand Presence',
                'heading'            => "Custom Printed Packaging\nThat Builds Recognition",
                'description'        => 'Transform ordinary packaging into a brand asset with premium custom printing designed to stand out on every shelf.',
                'primary_cta_label'  => 'Start Custom Order',
                'primary_cta_href'   => '/categories/custom-packaging',
                'secondary_cta_label'=> 'See Packaging Options',
                'secondary_cta_href' => '/shop',
                'image'              => null,
                'sort_order'         => 3,
            ],
            [
                'eyebrow'            => 'Standup Pouch Specialists',
                'heading'            => "Retail-Ready Pouches\nBuilt to Impress",
                'description'        => 'Resealable standup pouches engineered for maximum shelf presence, consistent freshness, and brand-first design.',
                'primary_cta_label'  => 'Shop Pouches',
                'primary_cta_href'   => '/categories/standup-pouches',
                'secondary_cta_label'=> 'Get Pricing',
                'secondary_cta_href' => '/bulk',
                'image'              => null,
                'sort_order'         => 4,
            ],
            [
                'eyebrow'            => 'Bulk & Wholesale Supply',
                'heading'            => "Scale Your Business\nwith Smarter Bulk Orders",
                'description'        => 'Volume discounts up to 30% with guaranteed quality consistency — whether you order 500 or 50,000 units.',
                'primary_cta_label'  => 'Get Bulk Pricing',
                'primary_cta_href'   => '/bulk',
                'secondary_cta_label'=> 'Contact Sales',
                'secondary_cta_href' => '/contact',
                'image'              => null,
                'sort_order'         => 5,
            ],
            [
                'eyebrow'            => 'Ziplock Solutions',
                'heading'            => "Airtight Seals,\nGuaranteed Freshness",
                'description'        => 'Heavy-duty ziplock bags trusted by food, pharma, and lifestyle brands for secure, long-lasting product integrity.',
                'primary_cta_label'  => 'View Ziplock Range',
                'primary_cta_href'   => '/categories/ziplock-pouches',
                'secondary_cta_label'=> 'Bulk Order',
                'secondary_cta_href' => '/bulk',
                'image'              => null,
                'sort_order'         => 6,
            ],
        ];

        foreach ($slides as $slide) {
            HeroSlide::firstOrCreate(
                ['eyebrow' => $slide['eyebrow']],
                array_merge($slide, ['is_active' => true])
            );
        }

        // ── Trust badges ─────────────────────────────────────────────────
        $badges = [
            ['icon_name' => 'shield-check', 'label' => 'Food Safe',    'sort_order' => 1],
            ['icon_name' => 'leaf',         'label' => 'Eco Friendly',  'sort_order' => 2],
            ['icon_name' => 'package-check','label' => 'Custom Print',  'sort_order' => 3],
            ['icon_name' => 'truck',        'label' => 'Bulk Supply',   'sort_order' => 4],
        ];

        foreach ($badges as $badge) {
            HeroTrustBadge::firstOrCreate(
                ['label' => $badge['label']],
                array_merge($badge, ['is_active' => true])
            );
        }
    }
}
