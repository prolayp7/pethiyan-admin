<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PromoBannerController extends Controller
{
    private const SETTING_KEY = 'promo_banner_section';

    // ──────────────────────────────────────────────────────────────────────────
    // Index
    // ──────────────────────────────────────────────────────────────────────────

    public function show(): View
    {
        $settings = $this->getSettings();
        return view('admin.promo-banner.index', compact('settings'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Save Settings
    // ──────────────────────────────────────────────────────────────────────────

    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'is_active'          => 'required|boolean',
            'badge_text'         => 'nullable|string|max:120',
            'heading'            => 'nullable|string|max:255',
            'subheading'         => 'nullable|string|max:500',
            'offer_primary'      => 'nullable|string|max:50',
            'offer_secondary'    => 'nullable|string|max:120',
            'button_label'       => 'nullable|string|max:120',
            'button_link'        => 'nullable|string|max:500',
        ]);

        Setting::updateOrCreate(
            ['variable' => self::SETTING_KEY],
            ['value'    => json_encode($data, JSON_UNESCAPED_UNICODE)]
        );

        $this->triggerFrontendRevalidate();

        return response()->json(['success' => true, 'message' => 'Promo Banner settings saved.']);
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
            'is_active'          => $value['is_active']       ?? true,
            'badge_text'         => $value['badge_text']      ?? 'Limited Time Offer',
            'heading'            => $value['heading']         ?? 'Custom Packaging Solutions for Your Brand',
            'subheading'         => $value['subheading']      ?? 'Get premium branded packaging with your logo and design. Minimum order from just 100 units.',
            'offer_primary'      => $value['offer_primary']   ?? '20%',
            'offer_secondary'    => $value['offer_secondary'] ?? 'OFF First Order',
            'button_label'       => $value['button_label']    ?? 'Explore Now',
            'button_link'        => $value['button_link']     ?? '/shop',
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
                'tags'   => ['promo-banner'],
                'paths'  => ['/'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Promo Banner revalidation failed.', ['message' => $e->getMessage()]);
        }
    }
}
