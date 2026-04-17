<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class WhyChooseUsController extends Controller
{
    private const SETTING_KEY = 'why_choose_us_section';

    // ──────────────────────────────────────────────────────────────────────────
    // Index
    // ──────────────────────────────────────────────────────────────────────────

    public function show(): View
    {
        $settings = $this->getSettings();
        return view('admin.why-choose-us.index', compact('settings'));
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
            'is_active'  => 'boolean',
            'eyebrow'    => 'nullable|string|max:120',
            'heading'    => 'nullable|string|max:255',
            'subheading' => 'nullable|string|max:500',
            'placement'  => 'required|in:after_hero,after_categories,after_featured_products,after_your_items,after_recently_viewed,after_video_stories,after_promo_banner,after_social_proof,after_newsletter',
            'features'   => 'nullable|array',
            'features.*' => 'nullable|string|max:255',
        ]);

        // Filter out empty features and reindex
        if (isset($data['features']) && is_array($data['features'])) {
            $data['features'] = array_values(array_filter($data['features'], function ($value) {
                return !empty(trim((string)$value));
            }));
        } else {
            $data['features'] = [];
        }

        Setting::updateOrCreate(
            ['variable' => self::SETTING_KEY],
            ['value'    => json_encode($data, JSON_UNESCAPED_UNICODE)]
        );

        $this->triggerFrontendRevalidate();

        return response()->json(['success' => true, 'message' => 'Why Choose Us section settings saved.']);
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

        // Default dummy data if empty
        $defaultFeatures = [
            'Wide range of packaging products for every industry',
            'Affordable wholesale pricing for small & large businesses',
            'High-quality, food-grade materials throughout',
            'Custom packaging & printing solutions available',
            'Fast delivery across India with reliable logistics',
            'Trusted by thousands of eCommerce sellers and brands',
        ];

        return [
            'is_active'  => $value['is_active']  ?? true,
            'eyebrow'    => $value['eyebrow']    ?? 'WHY CHOOSE US',
            'heading'    => $value['heading']    ?? 'Why Buy from Pethiyan?',
            'subheading' => $value['subheading'] ?? 'From small businesses to large manufacturers — we\'re your trusted packaging partner across India.',
            'placement'  => $value['placement']  ?? 'after_video_stories',
            'features'   => $value['features']   ?? $defaultFeatures,
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
                'tags'   => ['why-choose-us'],
                'paths'  => ['/'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Why Choose Us revalidation failed.', ['message' => $e->getMessage()]);
        }
    }
}
