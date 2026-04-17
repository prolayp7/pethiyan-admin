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

class HighlightTickerController extends Controller
{
    use ChecksPermissions;

    private const SETTING_KEY = 'highlight_ticker_section';

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
        return view('admin.highlight-ticker.index', compact('settings'));
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
            'highlights'        => 'nullable|array',
            'highlights.*'      => 'nullable|string|max:120',
            'texts'             => 'nullable|array',
            'texts.*'           => 'nullable|string|max:255',
        ]);

        $items = [];
        $highlights = $data['highlights'] ?? [];
        $texts      = $data['texts'] ?? [];

        foreach ($highlights as $index => $highlight) {
            $text = $texts[$index] ?? '';
            if (trim((string)$highlight) !== '' || trim((string)$text) !== '') {
                $items[] = [
                    'highlight' => trim((string)$highlight),
                    'text'      => trim((string)$text),
                ];
            }
        }

        $saveData = [
            'is_active' => $data['is_active'],
            'items'     => $items,
        ];

        Setting::updateOrCreate(
            ['variable' => self::SETTING_KEY],
            ['value'    => json_encode($saveData, JSON_UNESCAPED_UNICODE)]
        );

        $this->triggerFrontendRevalidate();

        return response()->json(['success' => true, 'message' => 'Highlight Ticker settings saved.']);
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

        $defaultItems = [
            ['highlight' => '+PACKAGING', 'text' => 'READY IN 7 BUSINESS DAYS'],
            ['highlight' => '+BULK DISCOUNTS', 'text' => 'UP TO 30% OFF ON WHOLESALE ORDERS'],
            ['highlight' => '+ECO-FRIENDLY', 'text' => 'MATERIALS ACROSS ALL PRODUCT LINES'],
            ['highlight' => '+NEW ARRIVALS', 'text' => 'BIODEGRADABLE STANDUP POUCHES'],
            ['highlight' => '+DESIGN SUPPORT', 'text' => 'FREE ARTWORK REVIEW WITH EVERY ORDER'],
        ];

        return [
            'is_active' => $value['is_active'] ?? true,
            'items'     => $value['items']     ?? $defaultItems,
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
                'tags'   => ['highlight-ticker'],
                'paths'  => ['/'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Highlight Ticker revalidation failed.', ['message' => $e->getMessage()]);
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
