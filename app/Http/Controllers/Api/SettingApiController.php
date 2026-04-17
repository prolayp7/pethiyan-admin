<?php

namespace App\Http\Controllers\Api;

use App\Enums\SettingTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Setting;
use App\Services\SettingService;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

#[Group('Settings')]
class SettingApiController extends Controller
{
    use AuthorizesRequests;

    private const HIGHLIGHT_TICKER_SETTING_KEY = 'highlight_ticker_section';

    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function index(): JsonResponse
    {
        $transformedSettings = $this->settingService->getAllSettings();

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.settings_fetched_successfully',
            data: $transformedSettings
        );
    }

    public function show($variable): JsonResponse
    {
        $setting_variable = SettingTypeEnum::values();
        if (!in_array($variable, $setting_variable)) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.invalid_type',
                data: []
            );
        }

        $transformedSetting = $this->settingService->getSettingByVariable($variable);

        if (!$transformedSetting) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.setting_not_found',
                data: []
            );
        }

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.setting_fetched_successfully',
            data: $transformedSetting
        );
    }

    public function settingVariables(): JsonResponse
    {
        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.setting_variables_fetched_successfully',
            data: SettingTypeEnum::values()
        );
    }

    public function firebaseConfig(): JsonResponse
    {
        $firebase = $this->settingService->getSettingByVariable(SettingTypeEnum::AUTHENTICATION());
        $notification = $this->settingService->getSettingByVariable(SettingTypeEnum::NOTIFICATION());

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.firebase_config_fetched_successfully',
            data: [
                'apiKey' => $firebase->value['fireBaseApiKey'] ?? "",
                'authDomain' => $firebase->value['fireBaseAuthDomain'] ?? "",
                'projectId' => $firebase->value['fireBaseProjectId'] ?? "",
                'storageBucket' => $firebase->value['fireBaseStorageBucket'] ?? "",
                'messagingSenderId' => $firebase->value['fireBaseMessagingSenderId'] ?? "",
                'appId' => $firebase->value['fireBaseAppId'] ?? "",
                'vapidKey' => $notification->value['vapIdKey'] ?? ""
            ]
        );
    }

    public function seoAdvanced(): JsonResponse
    {
        $setting = $this->settingService->getSettingByVariable(SettingTypeEnum::SEO_ADVANCED());
        $value   = $setting ? ($setting->toArray(request())['value'] ?? []) : [];

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.setting_fetched_successfully',
            data: [
                'robotsDisallowRules' => $value['robotsDisallowRules'] ?? [],
                'sitemapCustomUrls'   => $value['sitemapCustomUrls']   ?? [],
                'sitemapExcludeUrls'  => $value['sitemapExcludeUrls']  ?? [],
            ]
        );
    }

    public function seo(): JsonResponse
    {
        $web = $this->settingService->getSettingByVariable(SettingTypeEnum::WEB());
        $value = $web ? ($web->toArray(request())['value'] ?? []) : [];

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.setting_fetched_successfully',
            data: [
                'metaTitle' => $value['metaTitle'] ?? '',
                'metaKeywords' => $value['metaKeywords'] ?? '',
                'metaDescription' => $value['metaDescription'] ?? '',
                'metaCanonicalUrl' => $value['metaCanonicalUrl'] ?? '',
                'metaRobots' => $value['metaRobots'] ?? 'index,follow',
                'metaAuthor' => $value['metaAuthor'] ?? '',
                'metaPublisher' => $value['metaPublisher'] ?? '',
                'googleSiteVerification' => $value['googleSiteVerification'] ?? '',
                'bingSiteVerification' => $value['bingSiteVerification'] ?? '',
                'ogTitle' => $value['ogTitle'] ?? '',
                'ogDescription' => $value['ogDescription'] ?? '',
                'ogImage' => $value['ogImage'] ?? '',
                'twitterCard' => $value['twitterCard'] ?? 'summary_large_image',
                'twitterSite' => $value['twitterSite'] ?? '',
                'twitterCreator' => $value['twitterCreator'] ?? '',
                'twitterTitle' => $value['twitterTitle'] ?? '',
                'twitterDescription' => $value['twitterDescription'] ?? '',
                'twitterImage' => $value['twitterImage'] ?? '',
                'seoSchemaJson' => $value['seoSchemaJson'] ?? '',
            ]
        );
    }

    public function footer(): JsonResponse
    {
        $systemSetting = Setting::query()->find(SettingTypeEnum::SYSTEM());
        $systemRaw = is_array($systemSetting?->value) ? $systemSetting->value : [];

        $system = $this->settingService->getSettingByVariable(SettingTypeEnum::SYSTEM());
        $web = $this->settingService->getSettingByVariable(SettingTypeEnum::WEB());
        $highlightTickerSetting = Setting::query()->where('variable', self::HIGHLIGHT_TICKER_SETTING_KEY)->first();

        $systemValue = $system ? ($system->toArray(request())['value'] ?? []) : [];
        $webValue = $web ? ($web->toArray(request())['value'] ?? []) : [];
        $highlightTickerValue = is_array($highlightTickerSetting?->value)
            ? $highlightTickerSetting->value
            : (json_decode((string) $highlightTickerSetting?->value, true) ?: []);

        $footerMenus = Menu::query()
            ->where('is_active', true)
            ->where('location', 'footer')
            ->with([
                'items' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
            ])
            ->orderBy('id')
            ->get();

        $legalMenu = $footerMenus->firstWhere('slug', 'footer_legal');

        $navigationMenus = $footerMenus
            ->reject(fn (Menu $menu) => $menu->slug === 'footer_legal')
            ->map(fn (Menu $menu) => $this->formatFooterMenu($menu))
            ->values()
            ->all();

        $socialLinks = collect($systemRaw['socialLinks'] ?? [])
            ->map(function ($link, $platform) {
                $url = is_array($link) ? trim((string) ($link['url'] ?? '')) : '';
                $active = is_array($link) ? (bool) ($link['active'] ?? false) : false;

                if (!$active || $url === '') {
                    return null;
                }

                return [
                    'platform' => (string) $platform,
                    'label' => Str::of((string) $platform)->replace(['_', '-'], ' ')->title()->toString(),
                    'url' => $url,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $defaultHighlightTickerItems = [
            ['highlight' => '+PACKAGING', 'text' => 'READY IN 7 BUSINESS DAYS'],
            ['highlight' => '+BULK DISCOUNTS', 'text' => 'UP TO 30% OFF ON WHOLESALE ORDERS'],
            ['highlight' => '+ECO-FRIENDLY', 'text' => 'MATERIALS ACROSS ALL PRODUCT LINES'],
            ['highlight' => '+NEW ARRIVALS', 'text' => 'BIODEGRADABLE STANDUP POUCHES'],
            ['highlight' => '+DESIGN SUPPORT', 'text' => 'FREE ARTWORK REVIEW WITH EVERY ORDER'],
        ];

        $highlightTickerItems = collect($highlightTickerValue['items'] ?? $defaultHighlightTickerItems)
            ->map(fn ($item) => [
                'highlight' => trim((string) ($item['highlight'] ?? '')),
                'text' => trim((string) ($item['text'] ?? '')),
            ])
            ->filter(fn ($item) => $item['highlight'] !== '' || $item['text'] !== '')
            ->values()
            ->all();

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.setting_fetched_successfully',
            data: [
                'brand' => [
                    'appName' => $systemValue['appName'] ?? config('app.name'),
                    'logo' => $systemValue['logo'] ?? '',
                    'footerLogo' => $webValue['siteFooterLogo'] ?: ($systemValue['logo'] ?? ''),
                    'copyrightText' => trim((string) ($webValue['siteCopyright'] ?: ($systemValue['copyrightDetails'] ?? ''))),
                    'address' => $webValue['address'] ?: ($systemValue['companyAddress'] ?? ''),
                    'supportEmail' => $webValue['supportEmail'] ?: ($systemValue['sellerSupportEmail'] ?? ''),
                    'supportNumber' => $webValue['supportNumber'] ?: ($systemValue['sellerSupportNumber'] ?? ''),
                    'socialLinks' => $socialLinks,
                ],
                'menus' => [
                    'navigation' => $navigationMenus,
                    'legal' => $legalMenu instanceof Menu ? $this->formatFooterMenu($legalMenu) : null,
                ],
                'footerSeo' => [
                    'enabled' => (bool) ($webValue['footerSeoEnabled'] ?? true),
                    'homepageOnly' => (bool) ($webValue['footerSeoHomepageOnly'] ?? false),
                    'introHtml' => (string) ($webValue['footerSeoIntro'] ?? ''),
                ],
                'highlightTicker' => [
                    'homepageOnly' => (bool) ($highlightTickerValue['is_active'] ?? true),
                    'items' => $highlightTickerItems,
                ],
            ]
        );
    }

    private function formatFooterMenu(Menu $menu): array
    {
        return [
            'id' => $menu->id,
            'name' => $menu->name,
            'slug' => $menu->slug,
            'title' => Str::of($menu->slug)->after('footer_')->replace('_', ' ')->title()->toString(),
            'links' => $menu->items->map(fn ($item) => [
                'id' => $item->id,
                'label' => $item->label,
                'href' => $item->href,
                'target' => $item->target ?? '_self',
            ])->values()->all(),
        ];
    }
}
