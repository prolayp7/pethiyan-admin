<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminPermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Services\ImageWebpService;
use App\Traits\ChecksPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AuthorController extends Controller
{
    use ChecksPermissions;

    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            if ($response = $this->authorizeAuthorPermission($request)) {
                return $response;
            }

            return $next($request);
        });
    }

    public function index(): View
    {
        $authors = Author::orderBy('name')->paginate(20);

        return view('admin.authors.index', compact('authors'));
    }

    public function create(): View
    {
        return view('admin.authors.form', [
            'author' => new Author(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request->file('image'), 'authors');
        }

        Author::create($data);

        return redirect()
            ->route('admin.authors.index')
            ->with('success', 'Author created successfully.');
    }

    public function edit(Author $author): View
    {
        return view('admin.authors.form', compact('author'));
    }

    public function update(Request $request, Author $author): RedirectResponse
    {
        $data = $this->validatedData($request);

        if ($request->hasFile('image')) {
            if ($author->image && !str_starts_with($author->image, 'http')) {
                Storage::disk('public')->delete($author->image);
            }
            $data['image'] = $this->storeImage($request->file('image'), 'authors');
        }

        $author->update($data);

        return redirect()
            ->route('admin.authors.index')
            ->with('success', 'Author updated successfully.');
    }

    public function destroy(Author $author): RedirectResponse
    {
        if ($author->image && !str_starts_with($author->image, 'http')) {
            Storage::disk('public')->delete($author->image);
        }

        $author->delete();

        return redirect()
            ->route('admin.authors.index')
            ->with('success', 'Author deleted successfully.');
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:2000',
            'image' => 'nullable|mimes:jpg,jpeg,png,webp,avif|max:3072',
            'is_active' => 'nullable|boolean',
        ]);

        return [
            'name' => $data['name'],
            'role' => $data['role'] ?? null,
            'bio' => $data['bio'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ];
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

    private function authorizeAuthorPermission(Request $request)
    {
        $permission = match ($request->route()?->getActionMethod()) {
            'index' => AdminPermissionEnum::AUTHOR_VIEW->value,
            'create', 'store' => AdminPermissionEnum::AUTHOR_CREATE->value,
            'edit', 'update' => AdminPermissionEnum::AUTHOR_EDIT->value,
            'destroy' => AdminPermissionEnum::AUTHOR_DELETE->value,
            default => null,
        };

        if ($permission === null || $this->hasPermission($permission)) {
            return null;
        }

        abort(403, 'Unauthorized action.');
    }
}
