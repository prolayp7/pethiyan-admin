<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AnnouncementBarController extends Controller
{
    private const SETTING_KEY = 'announcement_bar_section';

    // ──────────────────────────────────────────────────────────────────────────
    // Index
    // ──────────────────────────────────────────────────────────────────────────

    public function show(): View
    {
        $settings = $this->getSettings();
        return view('admin.announcement-bar.index', compact('settings'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Save Settings
    // ──────────────────────────────────────────────────────────────────────────

    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'top_bar_active' => 'required|boolean',
            'top_bar_text'   => 'nullable|string|max:255',
            'ticker_active'  => 'required|boolean',
            'ticker_items'   => 'nullable|array',
            'ticker_items.*' => 'nullable|string|max:255',
        ]);

        if (isset($data['ticker_items']) && is_array($data['ticker_items'])) {
            $data['ticker_items'] = array_values(array_filter($data['ticker_items'], function ($value) {
                return !empty(trim((string)$value));
            }));
        } else {
            $data['ticker_items'] = [];
        }

        Setting::updateOrCreate(
            ['variable' => self::SETTING_KEY],
            ['value'    => json_encode($data, JSON_UNESCAPED_UNICODE)]
        );

        $this->triggerFrontendRevalidate();

        return response()->json(['success' => true, 'message' => 'Announcement bars settings saved.']);
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

        $defaultTicker = [
            '🚚 Free Shipping on Orders Above $50',
            '⚡ Fast Global Delivery Available',
            '📦 Premium Packaging for Modern Brands',
            '🎁 Bundle & Save — Buy 3 Get 1 Free',
            '🌿 100% Eco-Friendly Material Options',
        ];

        return [
            'top_bar_active' => $value['top_bar_active'] ?? true,
            'top_bar_text'   => $value['top_bar_text']   ?? 'The Power of Perfect Packaging — Trusted by 10,000+ Brands Worldwide',
            'ticker_active'  => $value['ticker_active']  ?? true,
            'ticker_items'   => $value['ticker_items']   ?? $defaultTicker,
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
                'tags'   => ['announcement-bar'],
                'paths'  => ['/'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Announcement Bar revalidation failed.', ['message' => $e->getMessage()]);
        }
    }
}
