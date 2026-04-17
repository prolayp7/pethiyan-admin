<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Services\ImageWebpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BlogCategoryController extends Controller
{
    public function index(): View
    {
        $categories = BlogCategory::withCount('posts')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->paginate(20);

        return view('admin.blog.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.blog.categories.form', [
            'category' => new BlogCategory(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['title']);

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $this->storeImage($request->file('cover_image'), 'blog/categories');
        }

        BlogCategory::create($data);
        $this->triggerFrontendRevalidate();

        return redirect()
            ->route('admin.blog.categories.index')
            ->with('success', 'Blog category created successfully.');
    }

    public function edit(BlogCategory $category): View
    {
        return view('admin.blog.categories.form', compact('category'));
    }

    public function update(Request $request, BlogCategory $category): RedirectResponse
    {
        $data = $this->validatedData($request, $category->id);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['title'], $category->id);

        if ($request->hasFile('cover_image')) {
            if ($category->cover_image && !str_starts_with($category->cover_image, 'http')) {
                Storage::disk('public')->delete($category->cover_image);
            }
            $data['cover_image'] = $this->storeImage($request->file('cover_image'), 'blog/categories');
        }

        $category->update($data);
        $this->triggerFrontendRevalidate();

        return redirect()
            ->route('admin.blog.categories.index')
            ->with('success', 'Blog category updated successfully.');
    }

    public function destroy(BlogCategory $category): RedirectResponse
    {
        BlogPost::where('blog_category_id', $category->id)->update(['blog_category_id' => null]);

        if ($category->cover_image && !str_starts_with($category->cover_image, 'http')) {
            Storage::disk('public')->delete($category->cover_image);
        }

        $category->delete();
        $this->triggerFrontendRevalidate();

        return redirect()
            ->route('admin.blog.categories.index')
            ->with('success', 'Blog category deleted successfully.');
    }

    private function validatedData(Request $request, ?int $categoryId = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_categories,slug,' . ($categoryId ?? 'NULL') . ',id',
            'description' => 'nullable|string|max:2000',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
        ]);

        return [
            'title' => $data['title'],
            'slug' => $data['slug'] ?? '',
            'description' => $data['description'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
            'metadata' => array_filter([
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'meta_keywords' => $data['meta_keywords'] ?? null,
            ], fn ($value) => !is_null($value) && $value !== ''),
        ];
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'blog-category';
        $slug = $base;
        $counter = 1;

        while (BlogCategory::when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
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

    private function triggerFrontendRevalidate(): void
    {
        $frontendUrl = rtrim((string) env('FRONTEND_APP_URL', ''), '/');
        $secret = (string) env('FRONTEND_REVALIDATE_SECRET', '');

        if ($frontendUrl === '' || $secret === '') {
            return;
        }

        try {
            Http::timeout(3)->post("{$frontendUrl}/api/revalidate", [
                'secret' => $secret,
                'tags' => ['blog'],
                'paths' => ['/blog'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Blog category revalidation failed.', ['message' => $e->getMessage()]);
        }
    }
}
