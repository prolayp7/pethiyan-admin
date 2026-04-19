<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class AnnouncementBarApiController extends Controller
{
    private const SETTING_KEY = 'announcement_bar_section';

    private const DEFAULT_TICKER = [
        '🚚 Free Shipping on Orders Above $50',
        '⚡ Fast Global Delivery Available',
        '📦 Premium Packaging for Modern Brands',
        '🎁 Bundle & Save — Buy 3 Get 1 Free',
        '🌿 100% Eco-Friendly Material Options',
    ];

    public function index(): JsonResponse
    {
        $setting = Setting::where('variable', self::SETTING_KEY)->first();
        $value   = [];

        if ($setting) {
            $value = is_array($setting->value)
                ? $setting->value
                : (json_decode((string) $setting->value, true) ?: []);
        }

        return response()->json([
            'topBar' => [
                'active' => filter_var($value['top_bar_active'] ?? true, FILTER_VALIDATE_BOOL),
                'text'   => $value['top_bar_text'] ?? 'The Power of Perfect Packaging — Trusted by 10,000+ Brands Worldwide',
            ],
            'ticker' => [
                'active' => filter_var($value['ticker_active'] ?? true, FILTER_VALIDATE_BOOL),
                'items'  => $value['ticker_items'] ?? self::DEFAULT_TICKER,
            ],
        ]);
    }
}
