<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminPermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Services\ImageWebpService;
use App\Traits\ChecksPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BlogPostController extends Controller
{
    use ChecksPermissions;

    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            if ($response = $this->authorizeBlogPermission($request)) {
                return $response;
            }

            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $posts = BlogPost::with('category')
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(function ($builder) use ($request) {
                    $builder->where('title', 'like', '%' . $request->input('search') . '%')
                        ->orWhere('excerpt', 'like', '%' . $request->input('search') . '%');
                });
            })
            ->when($request->filled('category_id'), fn ($query) => $query->where('blog_category_id', $request->integer('category_id')))
            ->latest('published_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $categories = BlogCategory::active()->orderBy('title')->get(['id', 'title']);

        return view('admin.blog.posts.index', compact('posts', 'categories'));
    }

    public function create(): View
    {
        $categories = BlogCategory::active()->orderBy('title')->get(['id', 'title']);
        $authors = Author::active()->orderBy('name')->get(['id', 'name', 'role', 'image']);

        return view('admin.blog.posts.form', [
            'post' => new BlogPost(),
            'categories' => $categories,
            'authors' => $authors,
            'tagString' => '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['title']);

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $this->storeImage($request->file('featured_image'), 'blog/posts');
        }

        $post = BlogPost::create($data);
        $this->triggerFrontendRevalidate($post->load('category'));

        return redirect()
            ->route('admin.blog.posts.index')
            ->with('success', 'Blog post created successfully.');
    }

    public function edit(BlogPost $post): View
    {
        $categories = BlogCategory::active()->orderBy('title')->get(['id', 'title']);
        $authors = Author::active()->orderBy('name')->get(['id', 'name', 'role', 'image']);

        return view('admin.blog.posts.form', [
            'post' => $post,
            'categories' => $categories,
            'authors' => $authors,
            'tagString' => implode(', ', $post->tags ?? []),
        ]);
    }

    public function update(Request $request, BlogPost $post): RedirectResponse
    {
        $data = $this->validatedData($request, $post->id);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['title'], $post->id);

        if ($request->hasFile('featured_image')) {
            if ($post->featured_image && !str_starts_with($post->featured_image, 'http')) {
                Storage::disk('public')->delete($post->featured_image);
            }
            $data['featured_image'] = $this->storeImage($request->file('featured_image'), 'blog/posts');
        }

        $post->update($data);
        $this->triggerFrontendRevalidate($post->load('category'));

        return redirect()
            ->route('admin.blog.posts.index')
            ->with('success', 'Blog post updated successfully.');
    }

    public function destroy(BlogPost $post): RedirectResponse
    {
        $post->load('category');

        if ($post->featured_image && !str_starts_with($post->featured_image, 'http')) {
            Storage::disk('public')->delete($post->featured_image);
        }

        $post->delete();
        $this->triggerFrontendRevalidate($post);

        return redirect()
            ->route('admin.blog.posts.index')
            ->with('success', 'Blog post deleted successfully.');
    }

    private function validatedData(Request $request, ?int $postId = null): array
    {
        $data = $request->validate([
            'blog_category_id' => 'nullable|integer|exists:blog_categories,id',
            'author_id' => 'nullable|integer|exists:authors,id',
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_posts,slug,' . ($postId ?? 'NULL') . ',id',
            'excerpt' => 'nullable|string|max:1000',
            'content' => 'nullable|string',
            'featured_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'tags_input' => 'nullable|string|max:500',
            'reading_time' => 'nullable|integer|min:1|max:120',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'published_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:1000',
        ]);

        $content = (string) ($data['content'] ?? '');
        $readingTime = (int) ($data['reading_time'] ?? 0);
        if ($readingTime < 1) {
            $readingTime = max(1, (int) ceil(str_word_count(strip_tags($content)) / 200));
        }

        $excerpt = $data['excerpt'] ?? null;
        if (!$excerpt && $content !== '') {
            $excerpt = Str::limit(trim(strip_tags($content)), 180);
        }

        return [
            'blog_category_id' => $data['blog_category_id'] ?? null,
            'author_id' => $data['author_id'] ?? null,
            'title' => $data['title'],
            'slug' => $data['slug'] ?? '',
            'excerpt' => $excerpt,
            'content' => $content ?: null,
            'tags' => $this->normalizeTags($data['tags_input'] ?? ''),
            'reading_time' => $readingTime,
            'is_featured' => $request->boolean('is_featured', false),
            'is_active' => $request->boolean('is_active', true),
            'published_at' => $data['published_at'] ?? null,
            'metadata' => array_filter([
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'meta_keywords' => $data['meta_keywords'] ?? null,
            ], fn ($value) => !is_null($value) && $value !== ''),
        ];
    }

    private function normalizeTags(string $value): array
    {
        $parts = array_map(fn ($tag) => trim($tag), explode(',', $value));
        $parts = array_values(array_filter($parts, fn ($tag) => $tag !== ''));

        return array_values(array_unique($parts));
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'blog-post';
        $slug = $base;
        $counter = 1;

        while (BlogPost::when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base . '-' . $counter++;
        }

        return $slug;
    }

    private function storeImage($file, string $directory): string
    {
        $converted = ImageWebpService::convert($file);
        $stored = Storage::disk('public')->put($directory, new \Illuminate\Http\File($converted['path']), ['visibility' => 'public']);
        $target = dirname($stored) . '/' . $converted['filename'];

        if ($stored !== $target) {
            Storage::disk('public')->move($stored, $target);
            $stored = $target;
        }

        if ($converted['isWebp']) {
            @unlink($converted['path']);
        }

        return $stored;
    }

    private function triggerFrontendRevalidate(?BlogPost $post = null): void
    {
        $frontendUrl = rtrim((string) env('FRONTEND_URL', env('FRONTEND_APP_URL', '')), '/');
        $secret = (string) env('REVALIDATE_SECRET', env('FRONTEND_REVALIDATE_SECRET', ''));

        if ($frontendUrl === '' || $secret === '') {
            return;
        }

        $paths = ['/blog'];

        if ($post) {
            $paths[] = '/blog/' . $post->slug;

            if ($post->category) {
                $paths[] = '/blog/category/' . $post->category->slug;
            }
        }

        try {
            Http::timeout(3)->post("{$frontendUrl}/api/revalidate", [
                'secret' => $secret,
                'tags' => ['blog'],
                'paths' => array_values(array_unique($paths)),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Blog post revalidation failed.', ['message' => $e->getMessage()]);
        }
    }

    private function authorizeBlogPermission(Request $request)
    {
        $permission = match ($request->route()?->getActionMethod()) {
            'index' => AdminPermissionEnum::BLOG_VIEW->value,
            'create', 'store' => AdminPermissionEnum::BLOG_CREATE->value,
            'edit', 'update' => AdminPermissionEnum::BLOG_EDIT->value,
            'destroy' => AdminPermissionEnum::BLOG_DELETE->value,
            default => null,
        };

        if ($permission === null || $this->hasPermission($permission)) {
            return null;
        }

        abort(403, 'Unauthorized action.');
    }
}
