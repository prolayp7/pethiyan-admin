<?php

namespace App\Http\Controllers\Api\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\GetProductsByLocationRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\Product\ProductCatalogResource;
use App\Http\Resources\Product\ProductListResource;
use App\Http\Resources\Product\ProductResource;
use App\Enums\Product\ProductStatusEnum;
use App\Enums\Product\ProductVarificationStatusEnum;
use App\Models\Product;
use App\Models\Category;
use App\Models\Store;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

#[Group('Products')]
class ProductApiController extends Controller
{

    /**
     * Get all products with full store pricing (no location required).
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Products Per Page', type: 'int', default: 15, example: 15)]
    #[QueryParameter('categories', description: 'Comma-separated list of category slugs to filter products', type: 'string', example: 'standup-pouches,ziplock-bags')]
    #[QueryParameter('brands', description: 'Comma-separated list of brand slugs to filter products', type: 'string', example: 'pethiyan')]
    #[QueryParameter('exclude_product', description: 'Comma-separated list of product slugs to exclude', type: 'string', example: 'adhesive-tape')]
    #[QueryParameter('sort', description: 'Sort order', type: 'string', example: 'price_asc, price_desc, featured, avg_rated, best_seller')]
    #[QueryParameter('store', description: 'Filter by store slug', type: 'string', example: 'pethiyan-main-store-1')]
    #[QueryParameter('search', description: 'Search term', type: 'string', example: 'tape')]
    #[QueryParameter('include_child_categories', description: 'Include child category products', type: 'boolean', default: false)]
    #[QueryParameter('customer_state_code', description: 'Customer state code for GST calculation', type: 'string', example: 'TN')]
    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'per_page'                 => 'nullable|integer|min:1|max:100',
                'page'                     => 'nullable|integer|min:1',
                'categories'               => 'nullable|string',
                'brands'                   => 'nullable|string',
                'exclude_product'          => 'nullable|string',
                'sort'                     => ['nullable', 'string', \Illuminate\Validation\Rule::in(['price_asc', 'price_desc', 'relevance', 'avg_rated', 'best_seller', 'featured'])],
                'store'                    => 'nullable|string|max:255',
                'search'                   => 'nullable|string|min:2|max:255',
                'include_child_categories' => 'nullable|boolean',
                'customer_state_code'      => 'nullable|string|max:10',
            ]);

            $perPage = (int) ($validated['per_page'] ?? 15);

            $query = Product::query()
                ->where('verification_status', ProductVarificationStatusEnum::APPROVED->value)
                ->where('status', ProductStatusEnum::ACTIVE->value)
                ->with([
                    'category:id,title,slug',
                    'taxClasses:id,title',
                    'taxClasses.taxRates:id,title,rate',
                    'variants.attributes.attribute:id,title,slug',
                    'variants.attributes.attributeValue:id,title,swatche_value',
                    'variants.storeProductVariants.store:id,name,slug,state_code,state_name',
                ]);

            // Category filter
            if (!empty($validated['categories'])) {
                $categorySlugs = array_filter(array_map('trim', explode(',', $validated['categories'])));
                $categoryIds   = Category::whereIn('slug', $categorySlugs)->pluck('id')->toArray();

                if (!empty($validated['include_child_categories'])) {
                    $allIds = $categoryIds;
                    foreach ($categoryIds as $cid) {
                        $allIds = array_merge($allIds, Product::getAllChildCategoryIds($cid));
                    }
                    $categoryIds = array_unique($allIds);
                }
                $query->whereIn('category_id', $categoryIds);
            }

            // Brand filter
            if (!empty($validated['brands'])) {
                $brandSlugs = array_filter(array_map('trim', explode(',', $validated['brands'])));
                $brandIds   = \App\Models\Brand::whereIn('slug', $brandSlugs)->pluck('id')->toArray();
                $query->whereIn('brand_id', $brandIds);
            }

            // Exclude products
            if (!empty($validated['exclude_product'])) {
                $excludeSlugs = array_values(array_filter(array_map('trim', explode(',', $validated['exclude_product']))));
                $query->whereNotIn('slug', $excludeSlugs);
            }

            // Store filter
            if (!empty($validated['store'])) {
                $store = \App\Models\Store::where('slug', $validated['store'])->first();
                if ($store) {
                    $query->whereHas('variants.storeProductVariants', fn($q) => $q->where('store_id', $store->id));
                }
            }

            // Search
            if (!empty($validated['search'])) {
                $term = $validated['search'];
                $query->where(function ($q) use ($term) {
                    $q->where('title', 'LIKE', "%{$term}%")
                      ->orWhere('short_description', 'LIKE', "%{$term}%")
                      ->orWhere('tags', 'LIKE', "%{$term}%")
                      ->orWhereHas('category', fn($cq) => $cq->where('title', 'LIKE', "%{$term}%"))
                      ->orWhereHas('brand',    fn($bq) => $bq->where('title', 'LIKE', "%{$term}%"));
                });
            }

            // Sort
            match ($validated['sort'] ?? null) {
                'price_asc'  => $query->orderBy('title'),
                'price_desc' => $query->orderByDesc('title'),
                'featured'   => $query->orderByDesc('featured')->orderByDesc('id'),
                'avg_rated'  => $query->orderByDesc('id'),
                default      => $query->orderByDesc('id'),
            };

            $products = $query->paginate($perPage);
            $products->getCollection()->transform(fn($p) => new ProductCatalogResource($p));

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.products_fetched_successfully',
                data: [
                    'current_page' => $products->currentPage(),
                    'last_page'    => $products->lastPage(),
                    'per_page'     => $products->perPage(),
                    'total'        => $products->total(),
                    'data'         => $products->items(),
                ]
            );
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(false, 'labels.validation_error', $e->errors());
        } catch (\Exception $e) {
            Log::error('Products index API failed.', ['message' => $e->getMessage()]);
            return ApiResponseType::sendJsonResponse(false, 'labels.error_fetching_products', []);
        }
    }

    /**
     * Get product Store Wise.
     */
    #[QueryParameter('per_page', description: 'Products Per Page', type: 'int', default: 15, example: 15)]
    #[QueryParameter('store_id', description: 'ID of the store to fetch products from', type: 'int', example: 1)]
    #[QueryParameter('store_slug', description: 'Slug of the store to fetch products from', type: 'string', example: 'my-store')]
    public function storeWise(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
            'store_id' => 'nullable|integer|exists:stores,id',
            'store_slug' => 'nullable|string|max:255',
        ]);

        $perPage = (int) ($validated['per_page'] ?? 15);
        $storeId = $validated['store_id'] ?? null;
        $storeSlug = isset($validated['store_slug']) ? trim((string) $validated['store_slug']) : null;

        // Check if at least one of store_id or store_slug is provided
        if (!$storeId && !$storeSlug) {
            return ApiResponseType::sendJsonResponse(success: false, message: __('labels.store_id_or_slug_required'), data: []);
        }

        // If store_slug is provided but not store_id, get the store_id from the slug
        if (!$storeId && $storeSlug) {
            $store = Store::where('slug', $storeSlug)->first();
            if (!$store) {
                return ApiResponseType::sendJsonResponse(success: false, message: __('labels.store_not_found_with_slug'), data: []);
            }
            $storeId = $store->id;
        }

        $query = Product::with([
            'variants' => function ($q) use ($storeId) {
                $q->whereHas('storeProductVariants', function ($sq) use ($storeId) {
                    $sq->where('store_id', $storeId);
                });
            },
            'variants.storeProductVariants' => function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            },
            'variants.attributes.attribute',
            'variants.attributes.attributeValue',
            'variantAttributes.attribute',
            'variantAttributes.attributeValue'
        ]);

        $query->where('verification_status', ProductVarificationStatusEnum::APPROVED->value);
        $query->where('status', ProductStatusEnum::ACTIVE->value);

        $query->whereHas('variants.storeProductVariants', function ($q) use ($storeId) {
            $q->where('store_id', $storeId);
        });
        $products = $query->orderBy('title')->paginate($perPage);
        $products->getCollection()->transform(fn($product) => new ProductListResource($product));
        $response = [
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
            'data' => $products->items(),
        ];
        return ApiResponseType::sendJsonResponse(true, 'labels.product_fetched_successfully', $response);
    }

    /**
     * Get product by Slug — returns full store pricing for all stores.
     */
    #[QueryParameter('customer_state_code', description: 'Optional customer state code for GST split (intra/inter).', type: 'string', example: 'TN')]
    public function show(Request $request, $slug): JsonResponse
    {
        try {
            $product = Product::query()
                ->where('slug', $slug)
                ->where('verification_status', ProductVarificationStatusEnum::APPROVED->value)
                ->where('status', ProductStatusEnum::ACTIVE->value)
                ->with([
                    'taxClasses:id,title',
                    'taxClasses.taxRates:id,title,rate',
                    'variants.attributes.attribute:id,title,slug',
                    'variants.attributes.attributeValue:id,title,swatche_value',
                    'variants.storeProductVariants.store:id,name,slug,state_code,state_name',
                ])
                ->first();

            if (!$product) {
                return ApiResponseType::sendJsonResponse(false, __('labels.product_not_found_with_slug'), []);
            }

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.product_fetched_successfully',
                data: new ProductCatalogResource($product)
            );
        } catch (\Exception $e) {
            Log::error('Products show API failed.', [
                'slug'    => $slug,
                'message' => $e->getMessage(),
            ]);
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.error_fetching_product',
                data: []
            );
        }
    }

    /**
     * Get products All Products.
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('perPage', description: 'Products Per Page.', type: 'int', default: 20, example: 20)]
    #[QueryParameter('search', description: 'Search by product title, slug, tags, and description.', type: 'string', example: 'tape')]
    #[QueryParameter('categories', description: 'Comma-separated list of category slugs to filter products', type: 'string', example: 'tape,ziplock-bags')]
    #[QueryParameter('include_child_categories', description: 'Include child category products', type: 'boolean', default: false)]
    #[QueryParameter('customer_state_code', description: 'Optional customer state code for GST split (intra/inter).', type: 'string', example: 'TN')]
    public function getAllProduct(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'page' => 'nullable|integer|min:1',
                'perPage' => 'nullable|integer|min:1|max:100',
                'per_page' => 'nullable|integer|min:1|max:100',
                'perpage' => 'nullable|integer|min:1|max:100',
                'search' => 'nullable|string|max:255',
                'categories' => 'nullable|string',
                'include_child_categories' => 'nullable|boolean',
                'customer_state_code' => 'nullable|string|max:10',
            ]);

            $perPage = (int) ($validated['perPage'] ?? $validated['per_page'] ?? $validated['perpage'] ?? 20);
            $search = trim((string) ($validated['search'] ?? ''));

            $query = Product::query()
                ->where('verification_status', ProductVarificationStatusEnum::APPROVED->value)
                ->where('status', ProductStatusEnum::ACTIVE->value)
                ->with([
                    'category:id,title,slug',
                    'brand:id,title,slug',
                    'taxClasses:id,title',
                    'taxClasses.taxRates:id,title,rate',
                    'variants' => function ($variantQuery) {
                        $variantQuery->with([
                            'attributes.attribute:id,title,slug',
                            'attributes.attributeValue:id,title,swatche_value',
                            'storeProductVariants' => function ($spvQuery) {
                                $spvQuery->with('store:id,name,slug,state_code,state_name');
                            },
                        ]);
                    },
                ]);

            // Category filter
            $filteredCategory = null;
            if (!empty($validated['categories'])) {
                $categorySlugs = array_filter(array_map('trim', explode(',', $validated['categories'])));
                $categories = Category::whereIn('slug', $categorySlugs)->with('parent')->get();
                $categoryIds = $categories->pluck('id')->toArray();

                if (!empty($validated['include_child_categories'])) {
                    $allIds = $categoryIds;
                    foreach ($categoryIds as $cid) {
                        $allIds = array_merge($allIds, Product::getAllChildCategoryIds($cid));
                    }
                    $categoryIds = array_unique($allIds);
                }

                $query->whereIn('category_id', $categoryIds);

                $filteredCategory = $categories->count() === 1
                    ? new CategoryResource($categories->first())
                    : CategoryResource::collection($categories);
            }

            if ($search !== '') {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('short_description', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('tags', 'like', "%{$search}%");
                });
            }

            $products = $query->orderByDesc('id')->paginate($perPage);
            $products->getCollection()->transform(fn($product) => new ProductCatalogResource($product));

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.product_fetched_successfully',
                data: [
                    'hasNext' => $products->hasMorePages(),
                    'page' => $products->currentPage(),
                    'perPage' => $products->perPage(),
                    'lastPage' => $products->lastPage(),
                    'total' => $products->total(),
                    'search' => $search,
                    'data' => $products->items(),
                    'category' => $filteredCategory,
                ]
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.validation_error',
                data: $e->errors()
            );
        } catch (\Exception $e) {
            Log::error('Products catalog API failed.', [
                'message' => $e->getMessage(),
            ]);
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.error_fetching_products',
                data: [],
            );
        }
    }

    /**
     * Get featured products with full catalog payload.
     */
    #[QueryParameter('customer_state_code', description: 'Optional customer state code for GST split (intra/inter).', type: 'string', example: 'TN')]
    public function getFeaturedProduct(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'customer_state_code' => 'nullable|string|max:10',
            ]);

            $query = Product::query()
                ->where('verification_status', ProductVarificationStatusEnum::APPROVED->value)
                ->where('status', ProductStatusEnum::ACTIVE->value)
                ->where('featured', '1')
                ->with([
                    'category:id,title,slug',
                    'brand:id,title,slug',
                    'taxClasses:id,title',
                    'taxClasses.taxRates:id,title,rate',
                    'variants' => function ($variantQuery) {
                        $variantQuery->with([
                            'attributes.attribute:id,title,slug',
                            'attributes.attributeValue:id,title,swatche_value',
                            'storeProductVariants' => function ($spvQuery) {
                                $spvQuery->with('store:id,name,slug,state_code,state_name');
                            },
                        ]);
                    },
                ]);

            $products = $query->orderByDesc('id')->get()
                ->map(fn($product) => new ProductCatalogResource($product))
                ->values();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.product_fetched_successfully',
                data: $products
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.validation_error',
                data: $e->errors()
            );
        } catch (\Exception $e) {
            Log::error('Featured products catalog API failed.', [
                'message' => $e->getMessage(),
            ]);
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.error_fetching_products',
                data: [],
            );
        }
    }

    /**
     * Get newly added products ordered by creation date.
     */
    #[QueryParameter('days', description: 'How many days back to look for new arrivals', type: 'int', default: 30, example: 30)]
    #[QueryParameter('limit', description: 'Maximum number of products to return', type: 'int', default: 20, example: 20)]
    #[QueryParameter('customer_state_code', description: 'Customer state code for GST calculation', type: 'string', example: 'TN')]
    public function getNewArrivals(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'days'                => ['nullable', 'integer', 'min:1', 'max:365'],
                'limit'               => ['nullable', 'integer', 'min:1', 'max:100'],
                'customer_state_code' => 'nullable|string|max:10',
            ]);

            $days  = (int) ($validated['days']  ?? 30);
            $limit = (int) ($validated['limit'] ?? 20);

            $products = Product::query()
                ->where('verification_status', ProductVarificationStatusEnum::APPROVED->value)
                ->where('status', ProductStatusEnum::ACTIVE->value)
                ->where('created_at', '>=', now()->subDays($days))
                ->with([
                    'category:id,title,slug',
                    'brand:id,title,slug',
                    'taxClasses:id,title',
                    'taxClasses.taxRates:id,title,rate',
                    'variants' => function ($variantQuery) {
                        $variantQuery->with([
                            'attributes.attribute:id,title,slug',
                            'attributes.attributeValue:id,title,swatche_value',
                            'storeProductVariants' => function ($spvQuery) {
                                $spvQuery->with('store:id,name,slug,state_code,state_name');
                            },
                        ]);
                    },
                ])
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get()
                ->map(fn($product) => new ProductCatalogResource($product))
                ->values();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.product_fetched_successfully',
                data: $products
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.validation_error',
                data: $e->errors()
            );
        } catch (\Exception $e) {
            Log::error('New arrivals API failed.', ['message' => $e->getMessage()]);
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.error_fetching_products',
                data: [],
            );
        }
    }

    /**
     * Search products by keywords and group results by keyword.
     */
    #[QueryParameter('latitude', description: 'Latitude of the user location', required: true, type: 'float', example: 23.11684540)]
    #[QueryParameter('longitude', description: 'Longitude of the user location', required: true, type: 'float', example: 70.02805670)]
    #[QueryParameter('keywords', description: 'Comma-separated list of keywords to search for', required: true, type: 'string', example: 'smartphone,mobile,phone')]
    #[QueryParameter('per_page', description: 'Products Per Page per keyword', type: 'int', default: 10, example: 10)]
    public function searchByKeywords(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'keywords' => 'required|string|min:1|max:1000',
                'per_page' => 'integer|min:1|max:50',
            ]);

            $keywords = array_map('trim', explode(',', $validated['keywords']));
            $keywords = array_values(array_filter(array_unique($keywords)));
            if (count($keywords) > 10) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'labels.validation_error',
                    data: ['keywords' => ['A maximum of 10 keywords is allowed.']]
                );
            }
            $perPage = $validated['per_page'] ?? 10;
            $groupedResults = [];

            foreach ($keywords as $keyword) {
                if (empty($keyword)) {
                    continue;
                }

                $filter = ['search' => $keyword];
                $products = Product::getProductsByLocation(
                    latitude: $validated['latitude'],
                    longitude: $validated['longitude'],
                    perPage: $perPage,
                    filter: $filter
                );

                $transformedProducts = $products->getCollection()->map(fn($product) => new ProductListResource($product));

                $groupedResults[] = [
                    'keyword' => $keyword,
                    'total_products' => $products->total(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'products' => $transformedProducts
                ];
            }

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.products_fetched_by_keywords_successfully',
                data: $groupedResults
            );

        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.validation_error',
                data: $e->errors()
            );
        } catch (\Exception $e) {
            Log::error('Products search-by-keywords API failed.', [
                'message' => $e->getMessage(),
            ]);
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.error_fetching_products_by_keywords',
                data: [],
            );
        }
    }
}
