<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminPermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Traits\ChecksPermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class NewsletterSectionController extends Controller
{
    use ChecksPermissions;

    private const SETTING_KEY = 'newsletter_section';

    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            if ($response = $this->authorizeHomePagePermission($request)) {
                return $response;
            }

            return $next($request);
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Index
    // ──────────────────────────────────────────────────────────────────────────

    public function show(): View
    {
        $settings = $this->getSettings();
        return view('admin.newsletter-section.index', compact('settings'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Save Settings
    // ──────────────────────────────────────────────────────────────────────────

    public function updateSettings(Request $request): JsonResponse
    {
        $request->merge([
            'is_active' => $request->boolean('is_active'),
        ]);

        $data = $request->validate([
            'is_active'         => 'boolean',
            'badge_text'        => 'nullable|string|max:120',
            'heading'           => 'nullable|string|max:255',
            'heading_highlight' => 'nullable|string|max:255',
            'subheading'        => 'nullable|string|max:500',
            'placement'         => 'required|in:after_hero,after_categories,after_featured_products,after_your_items,after_recently_viewed,after_video_stories,after_why_choose_us,after_promo_banner,after_social_proof',
            'perks'             => 'nullable|array|max:10',
            'perks.*'           => 'nullable|string|max:200',
            'form_title'        => 'nullable|string|max:200',
            'form_subtitle'     => 'nullable|string|max:300',
        ]);

        // Filter empty perk entries
        $data['perks'] = array_values(array_filter($data['perks'] ?? [], fn($p) => trim((string) $p) !== ''));

        Setting::updateOrCreate(
            ['variable' => self::SETTING_KEY],
            ['value'    => json_encode($data, JSON_UNESCAPED_UNICODE)]
        );

        $this->triggerFrontendRevalidate();

        return response()->json(['success' => true, 'message' => 'Newsletter section settings saved.']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    public static function getSettings(): array
    {
        $setting = Setting::where('variable', self::SETTING_KEY)->first();
        $value   = [];
        if ($setting) {
            $value = is_array($setting->value)
                ? $setting->value
                : (json_decode((string) $setting->value, true) ?: []);
        }

        return [
            'is_active'         => $value['is_active']         ?? true,
            'badge_text'        => $value['badge_text']        ?? 'Newsletter',
            'heading'           => $value['heading']           ?? 'Stay Updated with',
            'heading_highlight' => $value['heading_highlight'] ?? 'Packaging Trends',
            'subheading'        => $value['subheading']        ?? 'Join 5,000+ brand owners who get our weekly packaging insights, exclusive deals, and new product alerts.',
            'placement'         => $value['placement']         ?? 'after_social_proof',
            'perks'             => $value['perks']             ?? [
                'Exclusive deals & early access',
                'New product announcements',
                'Packaging tips & brand guides',
            ],
            'form_title'        => $value['form_title']        ?? 'Get packaging insights',
            'form_subtitle'     => $value['form_subtitle']     ?? 'No spam, unsubscribe any time.',
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
                'tags'   => ['newsletter-section'],
                'paths'  => ['/'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Newsletter Section revalidation failed.', ['message' => $e->getMessage()]);
        }
    }

    private function authorizeHomePagePermission(Request $request)
    {
        $permission = match ($request->route()?->getActionMethod()) {
            'show' => AdminPermissionEnum::HOME_PAGE_VIEW->value,
            'updateSettings' => AdminPermissionEnum::HOME_PAGE_EDIT->value,
            default => null,
        };

        if ($permission === null || $this->hasPermission($permission)) {
            return null;
        }

        if ($request->expectsJson() || $request->ajax()) {
            return $this->unauthorizedResponse();
        }

        abort(403, 'Unauthorized action.');
    }
}
