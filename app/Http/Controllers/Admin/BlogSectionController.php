<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminPermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Traits\ChecksPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BlogSectionController extends Controller
{
    use ChecksPermissions;

    private const SETTING_KEY = 'blog_section';

    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            if ($response = $this->authorizeBlogPermission($request)) {
                return $response;
            }

            return $next($request);
        });
    }

    public function show(): View
    {
        $settings = self::getSettings();

        return view('admin.blog.settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'is_active' => 'required|boolean',
            'eyebrow' => 'nullable|string|max:120',
            'heading' => 'nullable|string|max:255',
            'subheading' => 'nullable|string|max:500',
            'featured_section_title' => 'nullable|string|max:255',
            'latest_section_title' => 'nullable|string|max:255',
            'categories_section_title' => 'nullable|string|max:255',
            'newsletter_title' => 'nullable|string|max:255',
            'newsletter_subtitle' => 'nullable|string|max:500',
            'posts_per_page' => 'required|integer|min:3|max:30',
        ]);

        Setting::updateOrCreate(
            ['variable' => self::SETTING_KEY],
            ['value' => json_encode($data, JSON_UNESCAPED_UNICODE)]
        );

        $this->triggerFrontendRevalidate();

        return redirect()
            ->route('admin.blog.settings.show')
            ->with('success', 'Blog landing settings saved successfully.');
    }

    public static function getSettings(): array
    {
        $setting = Setting::where('variable', self::SETTING_KEY)->first();
        $value = [];

        if ($setting) {
            $value = is_array($setting->value)
                ? $setting->value
                : (json_decode((string) $setting->value, true) ?: []);
        }

        return [
            'is_active' => $value['is_active'] ?? true,
            'eyebrow' => $value['eyebrow'] ?? 'PACKAGING INSIGHTS',
            'heading' => $value['heading'] ?? 'The Pethiyan Blog',
            'subheading' => $value['subheading'] ?? 'Packaging strategy, product tips, and stories to help modern brands ship smarter.',
            'featured_section_title' => $value['featured_section_title'] ?? 'Featured Articles',
            'latest_section_title' => $value['latest_section_title'] ?? 'Latest Posts',
            'categories_section_title' => $value['categories_section_title'] ?? 'Explore by Topic',
            'newsletter_title' => $value['newsletter_title'] ?? 'Stay ahead on packaging trends',
            'newsletter_subtitle' => $value['newsletter_subtitle'] ?? 'Get editorial updates, shipping ideas, and product launch tips delivered to your inbox.',
            'posts_per_page' => (int) ($value['posts_per_page'] ?? 9),
        ];
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
            Log::warning('Blog revalidation failed.', ['message' => $e->getMessage()]);
        }
    }

    private function authorizeBlogPermission(Request $request)
    {
        $permission = match ($request->route()?->getActionMethod()) {
            'show' => AdminPermissionEnum::BLOG_VIEW->value,
            'update' => AdminPermissionEnum::BLOG_EDIT->value,
            default => null,
        };

        if ($permission === null || $this->hasPermission($permission)) {
            return null;
        }

        abort(403, 'Unauthorized action.');
    }
}
