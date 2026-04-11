<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Admin\BlogSectionController;
use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogApiController extends Controller
{
    public function home(): JsonResponse
    {
        $settings = BlogSectionController::getSettings();

        return response()->json([
            'settings' => [
                'isActive' => $settings['is_active'],
                'eyebrow' => $settings['eyebrow'],
                'heading' => $settings['heading'],
                'subheading' => $settings['subheading'],
                'featuredSectionTitle' => $settings['featured_section_title'],
                'latestSectionTitle' => $settings['latest_section_title'],
                'categoriesSectionTitle' => $settings['categories_section_title'],
                'newsletterTitle' => $settings['newsletter_title'],
                'newsletterSubtitle' => $settings['newsletter_subtitle'],
                'postsPerPage' => $settings['posts_per_page'],
            ],
            'featuredPosts' => $this->transformPosts(
                BlogPost::published()->with('category')->where('is_featured', true)->latest('published_at')->take(4)->get()
            ),
            'latestPosts' => $this->transformPosts(
                BlogPost::published()->with('category')->latest('published_at')->take(9)->get()
            ),
            'categories' => $this->transformCategories(
                BlogCategory::active()->withCount(['posts' => fn ($query) => $query->published()])->orderBy('sort_order')->orderBy('title')->get()
            ),
        ]);
    }

    public function posts(Request $request): JsonResponse
    {
        $query = BlogPost::published()->with('category');

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($builder) => $builder->where('slug', $request->input('category')));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('featured_only')) {
            $query->where('is_featured', true);
        }

        $posts = $query->latest('published_at')->paginate((int) $request->input('per_page', 9));

        return response()->json([
            'current_page' => $posts->currentPage(),
            'last_page' => $posts->lastPage(),
            'per_page' => $posts->perPage(),
            'total' => $posts->total(),
            'data' => $this->transformPosts($posts->getCollection()),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $post = BlogPost::published()
            ->with('category')
            ->where('slug', $slug)
            ->firstOrFail();

        $related = BlogPost::published()
            ->with('category')
            ->where('id', '!=', $post->id)
            ->when($post->blog_category_id, fn ($query) => $query->where('blog_category_id', $post->blog_category_id))
            ->latest('published_at')
            ->take(3)
            ->get();

        return response()->json([
            'post' => $this->transformPost($post, true),
            'relatedPosts' => $this->transformPosts($related),
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = BlogCategory::active()
            ->withCount(['posts' => fn ($query) => $query->published()])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return response()->json([
            'data' => $this->transformCategories($categories),
        ]);
    }

    public function category(string $slug, Request $request): JsonResponse
    {
        $category = BlogCategory::active()
            ->where('slug', $slug)
            ->firstOrFail();

        $posts = BlogPost::published()
            ->with('category')
            ->where('blog_category_id', $category->id)
            ->latest('published_at')
            ->paginate((int) $request->input('per_page', 9));

        return response()->json([
            'category' => $this->transformCategory($category),
            'current_page' => $posts->currentPage(),
            'last_page' => $posts->lastPage(),
            'per_page' => $posts->perPage(),
            'total' => $posts->total(),
            'data' => $this->transformPosts($posts->getCollection()),
        ]);
    }

    private function transformPosts($posts): array
    {
        return $posts->map(fn ($post) => $this->transformPost($post))->values()->all();
    }

    private function transformPost(BlogPost $post, bool $includeContent = false): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'content' => $includeContent ? $post->content : null,
            'featuredImage' => $post->featured_image_url,
            'isFeatured' => $post->is_featured,
            'publishedAt' => optional($post->published_at)?->toIso8601String(),
            'readingTime' => $post->reading_time,
            'tags' => $post->tags ?? [],
            'category' => $post->category ? $this->transformCategory($post->category) : null,
            'author' => [
                'name' => $post->author_name,
                'role' => $post->author_role,
                'bio' => $includeContent ? $post->author_bio : null,
                'avatar' => $post->author_avatar_url,
            ],
            'seo' => [
                'title' => $post->metadata['meta_title'] ?? null,
                'description' => $post->metadata['meta_description'] ?? null,
            ],
        ];
    }

    private function transformCategories($categories): array
    {
        return $categories->map(fn ($category) => $this->transformCategory($category))->values()->all();
    }

    private function transformCategory(BlogCategory $category): array
    {
        return [
            'id' => $category->id,
            'title' => $category->title,
            'slug' => $category->slug,
            'description' => $category->description,
            'coverImage' => $category->cover_image_url,
            'postsCount' => (int) ($category->posts_count ?? 0),
            'seo' => [
                'title' => $category->metadata['meta_title'] ?? null,
                'description' => $category->metadata['meta_description'] ?? null,
            ],
        ];
    }
}
