<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class FeaturedProductsSectionController extends Controller
{
    private const SETTING_KEY = 'featured_products_section';

    // ──────────────────────────────────────────────────────────────────────────
    // Index
    // ──────────────────────────────────────────────────────────────────────────

    public function show(): View
    {
        $settings   = $this->getSettings();
        $categories = Category::select('id', 'title', 'parent_id')
            ->where('status', 'active')
            ->orderByRaw('ISNULL(parent_id) DESC, parent_id ASC, title ASC')
            ->get();

        // Load a preview of products matching the selected categories
        $selectedCategoryIds = $settings['category_ids'] ?? [];
        $productCount        = $settings['product_count'] ?? 8;

        $products = collect();
        if (!empty($selectedCategoryIds)) {
            $products = Product::with('category')
                ->whereIn('category_id', $selectedCategoryIds)
                ->where('status', 'active')
                ->latest()
                ->take($productCount)
                ->get();
        }

        return view('admin.featured-products-section.index', compact('settings', 'categories', 'products'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Save Settings
    // ──────────────────────────────────────────────────────────────────────────

    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'is_active'    => 'required|boolean',
            'eyebrow'      => 'nullable|string|max:120',
            'heading'      => 'nullable|string|max:255',
            'subheading'   => 'nullable|string|max:255',
            'product_count'=> 'required|integer|min:1|max:50',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'view_all_link'=> 'nullable|string|max:500',
        ]);

        $data['category_ids'] = $data['category_ids'] ?? [];

        Setting::updateOrCreate(
            ['variable' => self::SETTING_KEY],
            ['value'    => json_encode($data)]
        );

        $this->triggerFrontendRevalidate();

        return response()->json(['success' => true, 'message' => 'Featured Products section settings saved.']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Preview Products (AJAX)
    // ──────────────────────────────────────────────────────────────────────────

    public function previewProducts(Request $request): JsonResponse
    {
        $categoryIds  = $request->input('category_ids', []);
        $productCount = (int) $request->input('product_count', 8);

        if (empty($categoryIds)) {
            return response()->json(['success' => true, 'products' => [], 'total' => 0]);
        }

        $products = Product::with('category')
            ->whereIn('category_id', $categoryIds)
            ->where('status', 'active')
            ->latest()
            ->take(min($productCount, 50))
            ->get()
            ->map(fn($p) => [
                'id'            => $p->id,
                'name'          => $p->name,
                'category_name' => $p->category?->title,
                'price'         => $p->price,
                'image'         => $p->getFirstMediaUrl('images') ?: null,
            ]);

        $total = Product::whereIn('category_id', $categoryIds)->where('status', 'active')->count();

        return response()->json(['success' => true, 'products' => $products, 'total' => $total]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function getSettings(): array
    {
        $setting = Setting::where('variable', self::SETTING_KEY)->first();
        $value   = [];
        if ($setting) {
            $value = is_array($setting->value)
                ? $setting->value
                : (json_decode((string) $setting->value, true) ?: []);
        }

        return [
            'is_active'     => $value['is_active']     ?? true,
            'eyebrow'       => $value['eyebrow']        ?? 'BESTSELLERS',
            'heading'       => $value['heading']        ?? 'Featured Products',
            'subheading'    => $value['subheading']     ?? 'Handpicked packaging solutions loved by thousands of brands',
            'product_count' => $value['product_count']  ?? 8,
            'category_ids'  => $value['category_ids']   ?? [],
            'view_all_link' => $value['view_all_link']  ?? '/shop',
        ];
    }

    private function triggerFrontendRevalidate(): void
    {
        $frontendUrl = rtrim((string) env('FRONTEND_APP_URL', ''), '/');
        $secret      = (string) env('FRONTEND_REVALIDATE_SECRET', '');

        if ($frontendUrl === '' || $secret === '') {
            return;
        }

        try {
            Http::timeout(3)->post("{$frontendUrl}/api/revalidate", [
                'secret' => $secret,
                'tags'   => ['featured-products'],
                'paths'  => ['/'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Featured products revalidation failed.', ['message' => $e->getMessage()]);
        }
    }
}
