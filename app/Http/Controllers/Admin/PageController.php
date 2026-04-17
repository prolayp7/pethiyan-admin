<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminPermissionEnum;
use App\Http\Controllers\Controller;
use App\Traits\ChecksPermissions;
use Illuminate\Http\Request;
use App\Models\Page;

class PageController extends Controller
{
    use ChecksPermissions;

    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            if ($response = $this->authorizePagePermission($request)) {
                return $response;
            }

            return $next($request);
        });
    }

    public function index()
    {
        $pages = Page::orderBy('id')->get();
        return view('admin.pages.index', compact('pages'));
    }

    public function edit(Page $page)
    {
        return view('admin.pages.edit', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
        ]);

        $page->update($validated);

        return redirect()->route('admin.pages.index')->with('success', 'Page updated successfully.');
    }

    private function authorizePagePermission(Request $request)
    {
        $permission = match ($request->route()?->getActionMethod()) {
            'index' => AdminPermissionEnum::PAGE_VIEW->value,
            'edit', 'update' => AdminPermissionEnum::PAGE_EDIT->value,
            default => null,
        };

        if ($permission === null || $this->hasPermission($permission)) {
            return null;
        }

        abort(403, 'Unauthorized action.');
    }
}
