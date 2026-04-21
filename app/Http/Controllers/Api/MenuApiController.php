<?php

namespace App\Http\Controllers\Api;

use App\Enums\Product\ProductVarificationStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Product;
use App\Services\CurrencyService;
use App\Types\Api\ApiResponseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class MenuApiController extends Controller
{
    /**
     * Return ALL active footer menus as simple link lists.
     * GET /api/footer-menus
     */
    public function footer(): JsonResponse
    {
        $menus = Menu::where('is_active', true)
            ->where('location', 'footer')
            ->with([
                'items' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
            ])
            ->orderBy('id')
            ->get()
            ->map(fn ($menu) => [
                'id'    => $menu->id,
                'name'  => $menu->name,
                'slug'  => $menu->slug,
                'links' => $menu->items->map(fn ($item) => [
                    'id'     => $item->id,
                    'label'  => $item->label,
                    'href'   => $item->href,
                    'target' => $item->target ?? '_self',
                ])->values(),
            ]);

        return ApiResponseType::sendJsonResponse(true, 'Footer menus retrieved successfully.', $menus->values());
    }

    /**
     * Return ALL active menus with their full navigation tree.
     * GET /api/menus
     *
     * Response shape:
     * {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Main Navigation",
     *       "slug": "header_main",
     *       "nav_items": [
     *         { "id":1, "label":"Home", "href":"/", "type":"link", ... },
     *         { "id":2, "label":"Shop", "type":"shop_dropdown", "shop_dropdown_items":[...] },
     *         { "id":3, "label":"Categories", "type":"mega_menu", "mega_menu_panels":[
     *             { "label":"Stand-Up Pouches", "columns":[
     *                 { "heading":"By Closure", "links":[...] }
     *             ]}
     *         ]}
     *       ]
     *     }
     *   ]
     * }
     */
    public function index(): JsonResponse
    {
        $menus = Menu::where('is_active', true)
            ->with($this->eagerLoads())
            ->orderBy('id')
            ->get()
            ->map(fn ($menu) => $this->formatMenu($menu));

        return ApiResponseType::sendJsonResponse(true, 'Menus retrieved successfully.', $menus->values());
    }

    /**
     * Return a single active menu by slug.
     * GET /api/menus/{slug}
     */
    public function show(string $slug): JsonResponse
    {
        $menu = Menu::where('slug', $slug)
            ->where('is_active', true)
            ->with($this->eagerLoads())
            ->first();

        if (!$menu) {
            return ApiResponseType::sendJsonResponse(false, 'Menu not found.', [], 404);
        }

        return ApiResponseType::sendJsonResponse(true, 'Menu retrieved successfully.', $this->formatMenu($menu));
    }

    /* ─── Private helpers ──────────────────────────────────────────────── */

    /**
     * Shared eager-load definition.
     * Loads top-level items → their dropdown children → their mega menu panels → columns → links.
     * Using Menu::items() which already scopes to whereNull('parent_id').
     */
    private function eagerLoads(): array
    {
        return [
            // Top-level nav items (no parent)
            'items' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),

            // Children of shop_dropdown items
            'items.children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),

            // Mega menu panels for mega_menu items
            'items.megaMenuPanels' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),

            // Columns inside each panel
            'items.megaMenuPanels.columns' => fn ($q) => $q->orderBy('sort_order'),

            // Links inside each column
            'items.megaMenuPanels.columns.links' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
        ];
    }

    private function formatMenu(Menu $menu): array
    {
        return [
            'id'        => $menu->id,
            'name'      => $menu->name,
            'slug'      => $menu->slug,
            'location'  => $menu->location,
            'nav_items' => $menu->items->map(fn ($item) => $this->formatItem($item))->values(),
        ];
    }

    private function formatItem($item): array
    {
        $type = $item->type->value ?? $item->type;
        $currency = app(CurrencyService::class);

        $node = [
            'id'           => $item->id,
            'label'        => $item->label,
            'href'         => $item->href,
            'type'         => $type,
            'target'       => $item->target ?? '_self',
            'icon'         => $item->icon,
            'description'  => $item->description,
            'accent_color' => $item->accent_color,
            'badge'        => $item->badge,
            'sort_order'   => $item->sort_order,
        ];

        // Shop dropdown: attach children as quick-link cards
        if ($type === 'shop_dropdown') {
            $node['shop_dropdown_items'] = $item->children
                ->map(fn ($child) => [
                    'id'           => $child->id,
                    'label'        => $child->label,
                    'href'         => $child->href,
                    'target'       => $child->target ?? '_self',
                    'icon'         => $child->icon,
                    'description'  => $child->description,
                    'accent_color' => $child->accent_color,
                    'badge'        => $child->badge,
                ])->values();
        }

        // Mega menu: attach panels → columns → links
        if ($type === 'mega_menu') {
            $panels = $item->megaMenuPanels
                ->map(function ($panel) use ($currency) {
                    $categorySlug = $this->extractCategorySlug($panel->href);
                    $featuredProducts = $categorySlug
                        ? $this->getPanelProductsByFlag($categorySlug, 'featured', '1')
                        : collect();
                    $topProducts = $categorySlug
                        ? $this->getPanelTopProducts($categorySlug, 2)
                        : collect();

                    return [
                        'id'               => $panel->id,
                        'label'            => $panel->label,
                        'href'             => $panel->href,
                        'accent_color'     => $panel->accent_color,
                        'image_path'       => $panel->image_path,
                        'tagline'          => $panel->tagline,
                        'sort_order'       => $panel->sort_order,
                        'featured_products' => $featuredProducts
                            ->map(fn (Product $product) => $this->formatMenuProduct($product, $currency))
                            ->filter()
                            ->values(),
                        'top_products'     => $topProducts
                            ->map(fn (Product $product) => $this->formatMenuProduct($product, $currency))
                            ->filter()
                            ->values(),
                        'columns'          => $panel->columns->map(fn ($col) => [
                            'id'      => $col->id,
                            'heading' => $col->heading,
                            'links'   => $col->links->map(fn ($link) => [
                                'id'     => $link->id,
                                'label'  => $link->label,
                                'href'   => $link->href,
                                'target' => $link->target ?? '_self',
                            ])->values(),
                        ])->values(),
                    ];
                })->values();

            $node['mega_menu_panels'] = $panels;
            $node['featured_products'] = $this->getFeaturedProducts()
                ->map(fn (Product $product) => $this->formatMenuProduct($product, $currency))
                ->filter()
                ->values();
            $node['top_products'] = $this->getTopProducts(2)
                ->map(fn (Product $product) => $this->formatMenuProduct($product, $currency))
                ->filter()
                ->values();
        }

        return $node;
    }

    private function extractCategorySlug(?string $href): ?string
    {
        if (empty($href)) {
            return null;
        }

        $path = parse_url($href, PHP_URL_PATH) ?: $href;
        $trimmed = trim($path, '/');

        if (Str::startsWith($trimmed, 'category/')) {
            return Str::after($trimmed, 'category/');
        }

        if (Str::startsWith($trimmed, 'categories/')) {
            return Str::after($trimmed, 'categories/');
        }

        return null;
    }

    private function basePanelProductsQuery(string $categorySlug)
    {
        $category = Category::query()
            ->select(['id', 'slug'])
            ->where('slug', $categorySlug)
            ->first();

        if (!$category) {
            return null;
        }

        return Product::query()
            ->with([
                'media',
                'variants' => fn ($query) => $query
                    ->select(['id', 'product_id', 'is_default'])
                    ->orderByDesc('is_default'),
                'variants.storeProductVariants' => fn ($query) => $query
                    ->select(['id', 'product_variant_id', 'price', 'special_price', 'stock'])
                    ->orderByDesc('stock'),
            ])
            ->select(['id', 'category_id', 'title', 'slug', 'featured', 'is_top_product'])
            ->where('category_id', $category->id)
            ->where('status', 'active')
            ->where('verification_status', ProductVarificationStatusEnum::APPROVED->value);
    }

    private function basePublicProductsQuery()
    {
        return Product::query()
            ->with([
                'media',
                'variants' => fn ($query) => $query
                    ->select(['id', 'product_id', 'is_default'])
                    ->orderByDesc('is_default'),
                'variants.storeProductVariants' => fn ($query) => $query
                    ->select(['id', 'product_variant_id', 'price', 'special_price', 'stock'])
                    ->orderByDesc('stock'),
            ])
            ->select(['id', 'category_id', 'title', 'slug', 'featured', 'is_top_product'])
            ->where('status', 'active')
            ->where('verification_status', ProductVarificationStatusEnum::APPROVED->value);
    }

    private function getPanelProductsByFlag(string $categorySlug, string $column, mixed $value)
    {
        $query = $this->basePanelProductsQuery($categorySlug);

        if (!$query) {
            return collect();
        }

        return $query
            ->where($column, $value)
            ->latest('id')
            ->get();
    }

    private function getPanelTopProducts(string $categorySlug, int $limit = 2)
    {
        $query = $this->basePanelProductsQuery($categorySlug);

        if (!$query) {
            return collect();
        }

        return $query
            ->where('is_top_product', true)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    private function getFeaturedProducts()
    {
        return $this->basePublicProductsQuery()
            ->where('featured', '1')
            ->latest('id')
            ->get();
    }

    private function getTopProducts(int $limit = 2)
    {
        return $this->basePublicProductsQuery()
            ->where('is_top_product', true)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    private function formatMenuProduct(?Product $product, CurrencyService $currency): ?array
    {
        if (!$product) {
            return null;
        }

        $frontendUrl = rtrim((string) config('app.frontend_url', config('app.frontendUrl', 'https://pethiyan.com')), '/');
        $variant = $product->variants->first();
        $storeVariant = $variant?->storeProductVariants->first();
        $priceExcludingTax = (float) ($storeVariant?->price_exclude_tax ?? $storeVariant?->getRawOriginal('price') ?? 0);
        $specialPriceExcludingTax = (float) ($storeVariant?->special_price_exclude_tax ?? $storeVariant?->getRawOriginal('special_price') ?? 0);
        $price = $specialPriceExcludingTax > 0
            ? $specialPriceExcludingTax
            : $priceExcludingTax;

        return [
            'image' => $product->main_image,
            'name' => $product->title,
            'price' => (float) $price,
            'currency_symbol' => $currency->getSymbol(),
            'currency_code' => $currency->getCode(),
            'slug' => $product->slug,
            'product_url' => $frontendUrl . '/products/' . ltrim((string) $product->slug, '/'),
        ];
    }
}
