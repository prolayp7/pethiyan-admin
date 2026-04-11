<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Create the header menu (skip if already exists) ───────────
        $existing = DB::table('menus')->where('slug', 'header_main')->first();
        if ($existing) {
            // Backfill location if not set
            DB::table('menus')->where('slug', 'header_main')->update(['location' => 'header']);
            $menuId = $existing->id;
            // Skip re-seeding items if they already exist
            $hasItems = DB::table('menu_items')->where('menu_id', $menuId)->exists();
            if ($hasItems) {
                goto footer_menus;
            }
        } else {
            $menuId = DB::table('menus')->insertGetId([
                'uuid'        => Str::uuid(),
                'name'        => 'Main Navigation',
                'slug'        => 'header_main',
                'location'    => 'header',
                'description' => 'Primary desktop/mobile header navigation used in NavigationMenu6',
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // ── 2. Top-level nav items ───────────────────────────────────────
        //   type reference:
        //     link          → plain link
        //     shop_dropdown → triggers the Shop quick-links panel
        //     mega_menu     → triggers the full sidebar mega menu

        $topItems = [
            ['label' => 'Home',              'href' => '/',                             'type' => 'link',          'sort_order' => 1],
            ['label' => 'Shop',              'href' => '/shop',                         'type' => 'shop_dropdown', 'sort_order' => 2],
            ['label' => 'Categories',        'href' => '/categories',                   'type' => 'mega_menu',     'sort_order' => 3],
            ['label' => 'Custom Packaging',  'href' => '/categories/custom-packaging',  'type' => 'link',          'sort_order' => 4],
            ['label' => 'Eco Packaging',     'href' => '/categories/eco-packaging',     'type' => 'link',          'sort_order' => 5],
            ['label' => 'New Arrivals',      'href' => '/new-arrivals',                 'type' => 'link',          'sort_order' => 6],
            ['label' => 'Bulk Orders',       'href' => '/bulk',                         'type' => 'link',          'sort_order' => 7],
            ['label' => 'Blog',              'href' => '/blog',                         'type' => 'link',          'sort_order' => 8],
            ['label' => 'FAQ',               'href' => '/faq',                          'type' => 'link',          'sort_order' => 9],
            ['label' => 'Certificates',      'href' => '/certificates',                 'type' => 'link',          'sort_order' => 10],
            ['label' => 'Download Brochure', 'href' => '/brochure',                     'type' => 'link',          'sort_order' => 11],
        ];

        $itemIds = [];
        foreach ($topItems as $item) {
            $itemIds[$item['label']] = DB::table('menu_items')->insertGetId([
                'uuid'        => Str::uuid(),
                'menu_id'     => $menuId,
                'parent_id'   => null,
                'label'       => $item['label'],
                'href'        => $item['href'],
                'type'        => $item['type'],
                'target'      => '_self',
                'sort_order'  => $item['sort_order'],
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // ── 3. Shop dropdown children ────────────────────────────────────
        //   These are children of the "Shop" menu_item.

        $shopChildren = [
            ['label' => 'All Products',   'href' => '/shop',             'description' => 'Browse our full catalogue',       'icon' => 'Layers',    'accent_color' => '#1f4f8a', 'sort_order' => 1],
            ['label' => 'New Arrivals',   'href' => '/new-arrivals',     'description' => 'Freshly added packaging',          'icon' => 'Sparkles',  'accent_color' => '#8b5cf6', 'sort_order' => 2],
            ['label' => 'Best Sellers',   'href' => '/best-sellers',     'description' => 'Most popular products',            'icon' => 'Star',      'accent_color' => '#f59e0b', 'sort_order' => 3],
            ['label' => 'Trending Now',   'href' => '/shop?sort=trending','description' => 'What brands are buying',          'icon' => 'TrendingUp','accent_color' => '#ef4444', 'sort_order' => 4],
            ['label' => 'Bulk Orders',    'href' => '/bulk',             'description' => 'Volume discounts up to 30%',       'icon' => 'Truck',     'accent_color' => '#4caf50', 'sort_order' => 5],
            ['label' => 'Sale & Deals',   'href' => '/shop?sort=sale',   'description' => 'Clearance & limited offers',       'icon' => 'Tag',       'accent_color' => '#e67e22', 'sort_order' => 6],
        ];

        foreach ($shopChildren as $child) {
            DB::table('menu_items')->insert([
                'uuid'         => Str::uuid(),
                'menu_id'      => $menuId,
                'parent_id'    => $itemIds['Shop'],
                'label'        => $child['label'],
                'href'         => $child['href'],
                'type'         => 'link',
                'target'       => '_self',
                'icon'         => $child['icon'],
                'description'  => $child['description'],
                'accent_color' => $child['accent_color'],
                'sort_order'   => $child['sort_order'],
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        // ── 4. Mega menu panels (sidebar categories) ─────────────────────
        //   Each panel belongs to the "Categories" menu_item.

        $categoriesItemId = $itemIds['Categories'];

        $panels = [

            // ── Stand-Up Pouches ─────────────────────────────────────────
            [
                'label'        => 'Stand-Up Pouches',
                'href'         => '/categories/standup-pouches',
                'accent_color' => '#1f4f8a',
                'image_path'   => '/images/banners/1.jpg',
                'tagline'      => 'Retail-ready resealable pouches',
                'sort_order'   => 1,
                'columns' => [
                    [
                        'heading'    => 'By Closure',
                        'sort_order' => 1,
                        'links' => [
                            ['label' => 'Ziplock Stand-Up',     'href' => '/categories/ziplock-pouches'],
                            ['label' => 'Press-Seal Pouches',   'href' => '/categories/press-seal'],
                            ['label' => 'Velcro Closure Bags',  'href' => '/categories/velcro-bags'],
                            ['label' => 'Open Top Pouches',     'href' => '/categories/open-top'],
                            ['label' => 'Heat-Seal Bags',       'href' => '/categories/heat-seal'],
                        ],
                    ],
                    [
                        'heading'    => 'By Material',
                        'sort_order' => 2,
                        'links' => [
                            ['label' => 'Kraft Paper Pouches',  'href' => '/categories/kraft'],
                            ['label' => 'Foil Lined Pouches',   'href' => '/categories/foil'],
                            ['label' => 'Clear PET Pouches',    'href' => '/categories/clear-pet'],
                            ['label' => 'Matte OPP Bags',       'href' => '/categories/matte-opp'],
                            ['label' => 'Biodegradable Pouches','href' => '/categories/bio'],
                        ],
                    ],
                    [
                        'heading'    => 'By Use',
                        'sort_order' => 3,
                        'links' => [
                            ['label' => 'Food Grade Pouches',   'href' => '/categories/food-grade'],
                            ['label' => 'Pet Food Packaging',   'href' => '/categories/pet-food'],
                            ['label' => 'Snack Pouches',        'href' => '/categories/snack-pouches'],
                            ['label' => 'Coffee Bags',          'href' => '/categories/coffee-bags'],
                            ['label' => 'Spice Packaging',      'href' => '/categories/spice-packaging'],
                        ],
                    ],
                ],
            ],

            // ── Ziplock Bags ─────────────────────────────────────────────
            [
                'label'        => 'Ziplock Bags',
                'href'         => '/categories/ziplock-pouches',
                'accent_color' => '#4caf50',
                'image_path'   => '/images/banners/2.jpg',
                'tagline'      => 'Airtight seals for every product',
                'sort_order'   => 2,
                'columns' => [
                    [
                        'heading'    => 'Standard Ziplock',
                        'sort_order' => 1,
                        'links' => [
                            ['label' => 'Small Ziplock Bags',   'href' => '/categories/small-ziplock'],
                            ['label' => 'Medium Ziplock Bags',  'href' => '/categories/medium-ziplock'],
                            ['label' => 'Large Ziplock Bags',   'href' => '/categories/large-ziplock'],
                            ['label' => 'Jumbo Ziplock Bags',   'href' => '/categories/jumbo-ziplock'],
                            ['label' => 'Mini Grip Bags',       'href' => '/categories/mini-grip'],
                        ],
                    ],
                    [
                        'heading'    => 'Premium Ziplock',
                        'sort_order' => 2,
                        'links' => [
                            ['label' => 'Stand-Up Ziplock',     'href' => '/categories/standup-ziplock'],
                            ['label' => 'Ziplock Mylar Bags',   'href' => '/categories/mylar-ziplock'],
                            ['label' => 'Frosted Ziplock',      'href' => '/categories/frosted-ziplock'],
                            ['label' => 'Clear Window Ziplock', 'href' => '/categories/window-ziplock'],
                            ['label' => 'Custom Print Ziplock', 'href' => '/categories/custom-ziplock'],
                        ],
                    ],
                    [
                        'heading'    => 'Industrial',
                        'sort_order' => 3,
                        'links' => [
                            ['label' => 'Heavy Duty Ziplock',   'href' => '/categories/heavy-duty-zip'],
                            ['label' => 'Anti-Static Bags',     'href' => '/categories/anti-static'],
                            ['label' => 'Moisture Proof Bags',  'href' => '/categories/moisture-proof'],
                            ['label' => 'Vacuum Ziplock',       'href' => '/categories/vacuum-ziplock'],
                            ['label' => 'Bulk Ziplock Packs',   'href' => '/categories/bulk-ziplock'],
                        ],
                    ],
                ],
            ],

            // ── Flat Bottom Bags ─────────────────────────────────────────
            [
                'label'        => 'Flat Bottom Bags',
                'href'         => '/categories/flat-bottom-bags',
                'accent_color' => '#e67e22',
                'image_path'   => '/images/banners/3.jpg',
                'tagline'      => 'Premium shelf-stable packaging',
                'sort_order'   => 3,
                'columns' => [
                    [
                        'heading'    => 'Coffee & Tea',
                        'sort_order' => 1,
                        'links' => [
                            ['label' => 'Coffee Flat Bottom',   'href' => '/categories/coffee-flat'],
                            ['label' => 'Tea Packaging Bags',   'href' => '/categories/tea-bags'],
                            ['label' => 'Valve Flat Bottom',    'href' => '/categories/valve-flat'],
                            ['label' => 'Window Flat Bottom',   'href' => '/categories/window-flat'],
                            ['label' => 'Aroma Seal Bags',      'href' => '/categories/aroma-seal'],
                        ],
                    ],
                    [
                        'heading'    => 'Food & Snacks',
                        'sort_order' => 2,
                        'links' => [
                            ['label' => 'Snack Flat Bags',      'href' => '/categories/snack-flat'],
                            ['label' => 'Dry Fruit Bags',       'href' => '/categories/dry-fruit'],
                            ['label' => 'Grain & Seed Bags',    'href' => '/categories/grain-bags'],
                            ['label' => 'Candy Packaging',      'href' => '/categories/candy-bags'],
                            ['label' => 'Nut & Trail Mix',      'href' => '/categories/nut-bags'],
                        ],
                    ],
                    [
                        'heading'    => 'Finishing',
                        'sort_order' => 3,
                        'links' => [
                            ['label' => 'Matte Finish Flat',    'href' => '/categories/matte-flat'],
                            ['label' => 'Glossy Flat Bottom',   'href' => '/categories/glossy-flat'],
                            ['label' => 'Metallic Finish',      'href' => '/categories/metallic-flat'],
                            ['label' => 'Custom Printed Flat',  'href' => '/categories/custom-flat'],
                            ['label' => 'Eco Flat Bags',        'href' => '/categories/eco-flat'],
                        ],
                    ],
                ],
            ],

            // ── Spout Pouches ────────────────────────────────────────────
            [
                'label'        => 'Spout Pouches',
                'href'         => '/categories/spout-pouches',
                'accent_color' => '#9b59b6',
                'image_path'   => '/images/banners/4.jpg',
                'tagline'      => 'Liquid packaging made effortless',
                'sort_order'   => 4,
                'columns' => [
                    [
                        'heading'    => 'By Liquid Type',
                        'sort_order' => 1,
                        'links' => [
                            ['label' => 'Juice Pouches',        'href' => '/categories/juice-pouches'],
                            ['label' => 'Sauce Pouches',        'href' => '/categories/sauce-pouches'],
                            ['label' => 'Oil Pouches',          'href' => '/categories/oil-pouches'],
                            ['label' => 'Honey Pouches',        'href' => '/categories/honey-pouches'],
                            ['label' => 'Syrup Pouches',        'href' => '/categories/syrup-pouches'],
                        ],
                    ],
                    [
                        'heading'    => 'Special Use',
                        'sort_order' => 2,
                        'links' => [
                            ['label' => 'Baby Food Pouches',    'href' => '/categories/baby-food'],
                            ['label' => 'Detergent Pouches',    'href' => '/categories/detergent'],
                            ['label' => 'Cosmetic Pouches',     'href' => '/categories/cosmetic-liquid'],
                            ['label' => 'Chemical Pouches',     'href' => '/categories/chemical'],
                            ['label' => 'Sport Drink Pouches',  'href' => '/categories/sport-drink'],
                        ],
                    ],
                    [
                        'heading'    => 'Capacity',
                        'sort_order' => 3,
                        'links' => [
                            ['label' => '50ml–200ml Pouches',   'href' => '/categories/small-spout'],
                            ['label' => '250ml–500ml Pouches',  'href' => '/categories/medium-spout'],
                            ['label' => '1L–2L Pouches',        'href' => '/categories/large-spout'],
                            ['label' => 'Bulk Spout Pouches',   'href' => '/categories/bulk-spout'],
                            ['label' => 'Custom Volume',        'href' => '/contact'],
                        ],
                    ],
                ],
            ],

            // ── Eco Packaging ────────────────────────────────────────────
            [
                'label'        => 'Eco Packaging',
                'href'         => '/categories/eco-packaging',
                'accent_color' => '#27ae60',
                'image_path'   => '/images/banners/5.jpg',
                'tagline'      => 'Sustainable solutions for green brands',
                'sort_order'   => 5,
                'columns' => [
                    [
                        'heading'    => 'Compostable',
                        'sort_order' => 1,
                        'links' => [
                            ['label' => 'Compostable Pouches',      'href' => '/categories/compostable'],
                            ['label' => 'PLA Bags',                 'href' => '/categories/pla-bags'],
                            ['label' => 'PBAT Pouches',             'href' => '/categories/pbat'],
                            ['label' => 'Corn-Starch Bags',         'href' => '/categories/corn-starch'],
                            ['label' => 'Certified Compostable',    'href' => '/categories/certified-compost'],
                        ],
                    ],
                    [
                        'heading'    => 'Recyclable',
                        'sort_order' => 2,
                        'links' => [
                            ['label' => 'Recyclable PE Bags',       'href' => '/categories/recyclable-pe'],
                            ['label' => 'Mono-Material Pouches',    'href' => '/categories/mono-material'],
                            ['label' => 'Paper Pouches',            'href' => '/categories/paper-bags'],
                            ['label' => 'Kraft Eco Bags',           'href' => '/categories/kraft-eco'],
                            ['label' => 'PCR Content Bags',         'href' => '/categories/pcr-bags'],
                        ],
                    ],
                    [
                        'heading'    => 'Sustainable',
                        'sort_order' => 3,
                        'links' => [
                            ['label' => 'FSC Certified Paper',      'href' => '/categories/fsc-paper'],
                            ['label' => 'Soy Ink Printed',          'href' => '/categories/soy-ink'],
                            ['label' => 'Carbon Neutral Bags',      'href' => '/categories/carbon-neutral'],
                            ['label' => 'Refillable Pouches',       'href' => '/categories/refillable'],
                            ['label' => 'Zero Waste Options',       'href' => '/categories/zero-waste'],
                        ],
                    ],
                ],
            ],

            // ── Custom Packaging ─────────────────────────────────────────
            [
                'label'        => 'Custom Packaging',
                'href'         => '/categories/custom-packaging',
                'accent_color' => '#e74c3c',
                'image_path'   => '/images/banners/6.jpg',
                'tagline'      => 'Your brand, your way',
                'sort_order'   => 6,
                'columns' => [
                    [
                        'heading'    => 'Print Options',
                        'sort_order' => 1,
                        'links' => [
                            ['label' => 'Digital Print',            'href' => '/categories/digital-print'],
                            ['label' => 'Rotogravure Print',        'href' => '/categories/rotogravure'],
                            ['label' => 'Flexographic Print',       'href' => '/categories/flexo-print'],
                            ['label' => 'Spot UV Coating',          'href' => '/categories/spot-uv'],
                            ['label' => 'Embossed Finish',          'href' => '/categories/embossed'],
                        ],
                    ],
                    [
                        'heading'    => 'Brand Services',
                        'sort_order' => 2,
                        'links' => [
                            ['label' => 'Private Label',            'href' => '/categories/private-label'],
                            ['label' => 'Packaging Design',         'href' => '/contact'],
                            ['label' => 'Dieline Templates',        'href' => '/contact'],
                            ['label' => 'Sample Orders',            'href' => '/contact'],
                            ['label' => 'Branded Inserts',          'href' => '/categories/inserts'],
                        ],
                    ],
                    [
                        'heading'    => 'MOQ & Bulk',
                        'sort_order' => 3,
                        'links' => [
                            ['label' => 'Low MOQ Custom',           'href' => '/categories/low-moq'],
                            ['label' => 'Bulk Custom Orders',       'href' => '/bulk'],
                            ['label' => 'White Label Bags',         'href' => '/categories/white-label'],
                            ['label' => 'OEM Packaging',            'href' => '/categories/oem'],
                            ['label' => 'Request a Quote',          'href' => '/contact'],
                        ],
                    ],
                ],
            ],

            // ── Window Bags ──────────────────────────────────────────────
            [
                'label'        => 'Window Bags',
                'href'         => '/categories/window-bags',
                'accent_color' => '#16a085',
                'image_path'   => '/images/banners/1.jpg',
                'tagline'      => 'Let your product shine through',
                'sort_order'   => 7,
                'columns' => [
                    [
                        'heading'    => 'Window Styles',
                        'sort_order' => 1,
                        'links' => [
                            ['label' => 'Full-Front Window',        'href' => '/categories/full-window'],
                            ['label' => 'Die-Cut Window',           'href' => '/categories/die-cut-window'],
                            ['label' => 'Oval Window Bags',         'href' => '/categories/oval-window'],
                            ['label' => 'Strip Window Bags',        'href' => '/categories/strip-window'],
                            ['label' => 'Round Window Bags',        'href' => '/categories/round-window'],
                        ],
                    ],
                    [
                        'heading'    => 'Applications',
                        'sort_order' => 2,
                        'links' => [
                            ['label' => 'Bakery Window Bags',       'href' => '/categories/bakery-window'],
                            ['label' => 'Candy Window Bags',        'href' => '/categories/candy-window'],
                            ['label' => 'Cookie Packaging',         'href' => '/categories/cookie-bags'],
                            ['label' => 'Gift Packaging',           'href' => '/categories/gift-bags'],
                            ['label' => 'Retail Window Bags',       'href' => '/categories/retail-window'],
                        ],
                    ],
                    [
                        'heading'    => 'Material & Finish',
                        'sort_order' => 3,
                        'links' => [
                            ['label' => 'Kraft with Window',        'href' => '/categories/kraft-window'],
                            ['label' => 'Foil with Window',         'href' => '/categories/foil-window'],
                            ['label' => 'Clear PET Window',         'href' => '/categories/clear-window'],
                            ['label' => 'Matte Window Bags',        'href' => '/categories/matte-window'],
                            ['label' => 'Custom Print + Window',    'href' => '/categories/custom-window'],
                        ],
                    ],
                ],
            ],

            // ── Vacuum Pouches ───────────────────────────────────────────
            [
                'label'        => 'Vacuum Pouches',
                'href'         => '/categories/vacuum-pouches',
                'accent_color' => '#2980b9',
                'image_path'   => '/images/banners/2.jpg',
                'tagline'      => 'Maximum freshness, minimum waste',
                'sort_order'   => 8,
                'columns' => [
                    [
                        'heading'    => 'Food Vacuum',
                        'sort_order' => 1,
                        'links' => [
                            ['label' => 'Meat Vacuum Bags',         'href' => '/categories/meat-vacuum'],
                            ['label' => 'Cheese Vacuum Bags',       'href' => '/categories/cheese-vacuum'],
                            ['label' => 'Seafood Bags',             'href' => '/categories/seafood-vacuum'],
                            ['label' => 'Poultry Packaging',        'href' => '/categories/poultry'],
                            ['label' => 'Deli Vacuum Bags',         'href' => '/categories/deli-vacuum'],
                        ],
                    ],
                    [
                        'heading'    => 'Industrial',
                        'sort_order' => 2,
                        'links' => [
                            ['label' => 'Heavy Duty Vacuum',        'href' => '/categories/hd-vacuum'],
                            ['label' => 'Multi-Layer Vacuum',       'href' => '/categories/multi-layer'],
                            ['label' => 'ESD Vacuum Bags',          'href' => '/categories/esd-vacuum'],
                            ['label' => 'Long-Term Storage',        'href' => '/categories/long-term'],
                            ['label' => 'Barrier Vacuum Bags',      'href' => '/categories/barrier-vacuum'],
                        ],
                    ],
                    [
                        'heading'    => 'Specialty',
                        'sort_order' => 3,
                        'links' => [
                            ['label' => 'Embossed Vacuum',          'href' => '/categories/embossed-vacuum'],
                            ['label' => 'Textured Vacuum',          'href' => '/categories/textured-vacuum'],
                            ['label' => 'Dual-Track Zip+Vac',       'href' => '/categories/zip-vac'],
                            ['label' => 'High-Barrier Vacuum',      'href' => '/categories/hb-vacuum'],
                            ['label' => 'Custom Vacuum Bags',       'href' => '/categories/custom-vacuum'],
                        ],
                    ],
                ],
            ],

        ]; // end $panels

        // ── Insert panels → columns → links ─────────────────────────────
        foreach ($panels as $panel) {
            $panelId = DB::table('mega_menu_panels')->insertGetId([
                'uuid'         => Str::uuid(),
                'menu_item_id' => $categoriesItemId,
                'label'        => $panel['label'],
                'href'         => $panel['href'],
                'accent_color' => $panel['accent_color'],
                'image_path'   => $panel['image_path'],
                'tagline'      => $panel['tagline'],
                'sort_order'   => $panel['sort_order'],
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            foreach ($panel['columns'] as $column) {
                $columnId = DB::table('mega_menu_columns')->insertGetId([
                    'panel_id'   => $panelId,
                    'heading'    => $column['heading'],
                    'sort_order' => $column['sort_order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $linkOrder = 1;
                foreach ($column['links'] as $link) {
                    DB::table('mega_menu_links')->insert([
                        'column_id'  => $columnId,
                        'label'      => $link['label'],
                        'href'       => $link['href'],
                        'target'     => '_self',
                        'is_active'  => true,
                        'sort_order' => $linkOrder++,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        footer_menus:
        // ── 5. Footer menus ──────────────────────────────────────────────
        $footerMenus = [
            [
                'name'        => 'Footer – Products',
                'slug'        => 'footer_products',
                'description' => 'Footer navigation column: product categories',
                'items'       => [
                    ['label' => 'Standup Pouches',   'href' => '/categories/standup-pouches'],
                    ['label' => 'Ziplock Bags',       'href' => '/categories/ziplock-pouches'],
                    ['label' => 'Custom Packaging',   'href' => '/categories/custom-packaging'],
                    ['label' => 'Eco Packaging',      'href' => '/categories/eco-packaging'],
                    ['label' => 'Bulk Orders',        'href' => '/bulk'],
                    ['label' => 'Wholesale',          'href' => '/wholesale'],
                ],
            ],
            [
                'name'        => 'Footer – Company',
                'slug'        => 'footer_company',
                'description' => 'Footer navigation column: company pages',
                'items'       => [
                    ['label' => 'About Us',       'href' => '/about'],
                    ['label' => 'Sustainability', 'href' => '/sustainability'],
                    ['label' => 'Our Process',    'href' => '/process'],
                    ['label' => 'Careers',        'href' => '/careers'],
                    ['label' => 'Press',          'href' => '/press'],
                    ['label' => 'Blog',           'href' => '/blog'],
                ],
            ],
            [
                'name'        => 'Footer – Support',
                'slug'        => 'footer_support',
                'description' => 'Footer navigation column: customer support pages',
                'items'       => [
                    ['label' => 'Contact',     'href' => '/contact'],
                    ['label' => 'Help Center', 'href' => '/help'],
                    ['label' => 'Shipping',    'href' => '/shipping'],
                    ['label' => 'Returns',     'href' => '/returns'],
                    ['label' => 'Track Order', 'href' => '/track-order'],
                    ['label' => 'FAQs',        'href' => '/faq'],
                ],
            ],
            [
                'name'        => 'Footer – Legal',
                'slug'        => 'footer_legal',
                'description' => 'Footer bottom bar: legal / policy links',
                'items'       => [
                    ['label' => 'Privacy Policy', 'href' => '/privacy'],
                    ['label' => 'Terms',          'href' => '/terms'],
                    ['label' => 'Cookies',        'href' => '/cookies'],
                ],
            ],
        ];

        foreach ($footerMenus as $footerMenu) {
            // Skip if already seeded
            if (DB::table('menus')->where('slug', $footerMenu['slug'])->exists()) {
                continue;
            }
            $fMenuId = DB::table('menus')->insertGetId([
                'uuid'        => Str::uuid(),
                'name'        => $footerMenu['name'],
                'slug'        => $footerMenu['slug'],
                'location'    => 'footer',
                'description' => $footerMenu['description'],
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $order = 1;
            foreach ($footerMenu['items'] as $item) {
                DB::table('menu_items')->insert([
                    'uuid'       => Str::uuid(),
                    'menu_id'    => $fMenuId,
                    'parent_id'  => null,
                    'label'      => $item['label'],
                    'href'       => $item['href'],
                    'type'       => 'link',
                    'target'     => '_self',
                    'sort_order' => $order++,
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
