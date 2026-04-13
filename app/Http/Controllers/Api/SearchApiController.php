<?php

namespace App\Http\Controllers\Api;

use App\Enums\CategoryStatusEnum;
use App\Enums\Product\ProductStatusEnum;
use App\Enums\Product\ProductVarificationStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\Product\ProductCatalogResource;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Product;
use App\Models\SearchLog;
use App\Models\TrendingProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchApiController extends Controller
{
    // ─── POST /api/search/track ─────────────────────────────────────────────────

    public function track(Request $request): JsonResponse
    {
        $query = trim((string) $request->input('query', ''));

        if (mb_strlen($query) < 2) {
            return response()->json(['success' => false]);
        }

        SearchLog::create([
            'query'        => mb_strtolower(mb_substr($query, 0, 255)),
            'result_count' => (int) $request->input('result_count', 0),
            'entity_types' => $request->input('entity_types', ['products']),
            'user_id'      => $request->user()?->id,
            'session_id'   => mb_substr((string) $request->input('session_id', ''), 0, 64) ?: null,
            'ip_address'   => $request->ip(),
        ]);

        return response()->json(['success' => true]);
    }

    // ─── GET /api/search/top-searches ───────────────────────────────────────────

    public function topSearches(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 8), 20);

        $terms = SearchLog::query()
            ->select('query', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit($limit)
            ->pluck('query')
            ->values()
            ->all();

        return response()->json(['data' => $terms]);
    }

    // ─── GET /api/search/trending-products ──────────────────────────────────────

    public function trendingProducts(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 4), 12);

        // Try trending_products table first (populated by artisan command)
        $trendingIds = TrendingProduct::query()
            ->where('period', 'weekly')
            ->orderByDesc('score')
            ->limit($limit)
            ->pluck('product_id')
            ->all();

        // Fall back to is_top_product flag if trending table is empty
        if (empty($trendingIds)) {
            $products = Product::query()
                ->where('verification_status', ProductVarificationStatusEnum::APPROVED->value)
                ->where('status', ProductStatusEnum::ACTIVE->value)
                ->where('is_top_product', true)
                ->with([
                    'category:id,title,slug',
                    'brand:id,title,slug',
                    'taxClasses:id,title',
                    'taxClasses.taxRates:id,title,rate',
                    'variants.attributes.attribute:id,title,slug',
                    'variants.attributes.attributeValue:id,title,swatche_value',
                    'variants.storeProductVariants.store:id,name,slug,state_code,state_name',
                ])
                ->limit($limit)
                ->get();
        } else {
            $products = Product::query()
                ->whereIn('id', $trendingIds)
                ->where('verification_status', ProductVarificationStatusEnum::APPROVED->value)
                ->where('status', ProductStatusEnum::ACTIVE->value)
                ->with([
                    'category:id,title,slug',
                    'brand:id,title,slug',
                    'taxClasses:id,title',
                    'taxClasses.taxRates:id,title,rate',
                    'variants.attributes.attribute:id,title,slug',
                    'variants.attributes.attributeValue:id,title,swatche_value',
                    'variants.storeProductVariants.store:id,name,slug,state_code,state_name',
                ])
                ->get()
                ->sortBy(fn ($p) => array_search($p->id, $trendingIds))
                ->values();
        }

        return response()->json([
            'data' => $products->map(fn ($p) => new ProductCatalogResource($p))->values()->all(),
        ]);
    }

    // ─── GET /api/search?q=&type=all|products|categories|blogs ─────────────────

    public function search(Request $request): JsonResponse
    {
        $q    = trim((string) $request->input('q', ''));
        $type = $request->input('type', 'all'); // all | products | categories | blogs
        $perPage = min((int) $request->input('per_page', 10), 30);

        if (mb_strlen($q) < 1) {
            return response()->json([
                'query'      => $q,
                'products'   => [],
                'categories' => [],
                'blogs'      => [],
                'total'      => 0,
            ]);
        }

        $products   = [];
        $categories = [];
        $blogs      = [];

        // ── Products ──────────────────────────────────────────────────────────────
        if (in_array($type, ['all', 'products'])) {
            $productQuery = Product::query()
                ->where('verification_status', ProductVarificationStatusEnum::APPROVED->value)
                ->where('status', ProductStatusEnum::ACTIVE->value)
                ->where(function ($sq) use ($q) {
                    $sq->where('title', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%")
                        ->orWhere('short_description', 'like', "%{$q}%")
                        ->orWhere('tags', 'like', "%{$q}%");
                })
                ->with([
                    'category:id,title,slug',
                    'brand:id,title,slug',
                    'taxClasses:id,title',
                    'taxClasses.taxRates:id,title,rate',
                    'variants.attributes.attribute:id,title,slug',
                    'variants.attributes.attributeValue:id,title,swatche_value',
                    'variants.storeProductVariants.store:id,name,slug,state_code,state_name',
                ])
                ->limit($type === 'all' ? 8 : $perPage);

            $products = $productQuery->get()
                ->map(fn ($p) => new ProductCatalogResource($p))
                ->values()
                ->all();
        }

        // ── Categories ────────────────────────────────────────────────────────────
        if (in_array($type, ['all', 'categories'])) {
            $catQuery = Category::query()
                ->where('status', CategoryStatusEnum::ACTIVE())
                ->where(function ($sq) use ($q) {
                    $sq->where('title', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%");
                })
                ->with('parent')
                ->limit($type === 'all' ? 5 : $perPage);

            $categories = $catQuery->get()
                ->map(fn ($c) => new CategoryResource($c))
                ->values()
                ->all();
        }

        // ── Blogs ─────────────────────────────────────────────────────────────────
        if (in_array($type, ['all', 'blogs'])) {
            $blogQuery = BlogPost::published()
                ->with('category')
                ->where(function ($sq) use ($q) {
                    $sq->where('title', 'like', "%{$q}%")
                        ->orWhere('excerpt', 'like', "%{$q}%")
                        ->orWhere('tags', 'like', "%{$q}%");
                })
                ->latest('published_at')
                ->limit($type === 'all' ? 4 : $perPage);

            $blogs = $blogQuery->get()
                ->map(fn ($post) => [
                    'id'           => $post->id,
                    'title'        => $post->title,
                    'slug'         => $post->slug,
                    'excerpt'      => $post->excerpt,
                    'featuredImage'=> $post->featured_image_url,
                    'publishedAt'  => optional($post->published_at)?->toIso8601String(),
                    'category'     => $post->category ? [
                        'title' => $post->category->title,
                        'slug'  => $post->category->slug,
                    ] : null,
                ])
                ->values()
                ->all();
        }

        $total = count($products) + count($categories) + count($blogs);

        return response()->json([
            'query'      => $q,
            'products'   => $products,
            'categories' => $categories,
            'blogs'      => $blogs,
            'total'      => $total,
        ]);
    }
}
