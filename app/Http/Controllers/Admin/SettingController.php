<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SettingTypeEnum;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\FrontendRevalidateService;
use App\Services\ImageConversionService;
use App\Services\SettingService;
use App\Services\TotpService;
use App\Types\Api\ApiResponseType;
use App\Types\Settings\AppSettingType;
use App\Types\Settings\AuthenticationSettingType;
use App\Types\Settings\HomeGeneralSettingType;
use App\Types\Settings\DeliveryBoySettingType;
use App\Types\Settings\GstSettingType;
use App\Types\Settings\SmsSettingType;
use App\Types\Settings\EmailSettingType;
use App\Types\Settings\NotificationSettingType;
use App\Types\Settings\PaymentSettingType;
use App\Types\Settings\StorageSettingType;
use App\Types\Settings\SystemSettingType;
use App\Types\Settings\WebSettingType;
use App\Types\Settings\SeoAdvancedSettingType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class SettingController extends Controller
{
    use AuthorizesRequests;

    private const PAYMENT_UNLOCK_SESSION_KEY = 'admin_payment_settings_unlocked_at';
    private const PAYMENT_UNLOCK_TTL_MINUTES = 10;
    private const AUTHENTICATION_UNLOCK_SESSION_KEY = 'admin_authentication_settings_unlocked_at';
    private const AUTHENTICATION_UNLOCK_TTL_MINUTES = 10;

    /** Fields belonging to each system-settings section for partial saves */
    private const SYSTEM_SECTION_FIELDS = [
        'general'  => ['appName', 'systemTimezone', 'copyrightDetails', 'currency', 'currencySymbol', 'logo', 'favicon', 'companyAddress', 'adminSignature'],
        'support'  => ['sellerSupportEmail', 'sellerSupportNumber'],
        'cart'     => ['checkoutType', 'minimumCartAmount', 'maximumItemsAllowedInCart', 'lowStockLimit'],
        'order'    => ['customerInvoiceDownloadEnabled', 'customerInvoiceDownloadMinStatus'],
        'demomode'     => ['demoMode', 'adminDemoModeMessage', 'sellerDemoModeMessage', 'customerDemoModeMessage', 'customerLocationDemoModeMessage', 'deliveryBoyDemoModeMessage'],
        'social'       => ['socialLinks'],
        'product_grid' => ['showVariantColorsInGrid', 'showGstInGrid', 'showCategoryNameInGrid', 'showMinQtyInGrid'],
    ];

    /** Fields belonging to each web-settings section for partial saves */
    private const WEB_SECTION_FIELDS = [
        'support' => ['supportEmail', 'supportNumber', 'googleMapKey', 'mapIframe'],
        'seo' => [
            'metaTitle',
            'metaKeywords',
            'metaDescription',
            'metaCanonicalUrl',
            'metaRobots',
            'metaAuthor',
            'metaPublisher',
            'googleSiteVerification',
            'bingSiteVerification',
            'ogTitle',
            'ogDescription',
            'twitterCard',
            'twitterSite',
            'twitterCreator',
            'twitterTitle',
            'twitterDescription',
            'seoSchemaJson',
            'googleAnalyticsId',
            'googleTagManagerId',
            'facebookPixelId',
        ],
        'footer_seo' => ['footerSeoEnabled', 'footerSeoHomepageOnly', 'footerSeoIntro'],
    ];

    private const WEB_MAIN_FORM_FIELDS = [
        'defaultLatitude',
        'defaultLongitude',
        'returnRefundPolicy',
        'shippingPolicy',
        'privacyPolicy',
        'termsCondition',
        'aboutUs',
    ];

    private const WEB_BOOLEAN_FIELDS = ['footerSeoEnabled', 'footerSeoHomepageOnly'];

    protected SettingService $settingService;
    protected TotpService $totpService;

    public function __construct(SettingService $settingService, TotpService $totpService)
    {
        $this->settingService = $settingService;
        $this->totpService = $totpService;
    }


    public function index(): View
    {
        try {
            $this->authorize('viewAny', Setting::class);
        } catch (AuthorizationException $e) {
            abort(403, __('labels.unauthorized_access'));
        }
        return view('admin.settings.all_settings');
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'type' => ['required', new Enum(SettingTypeEnum::class)],
            ]);

            $type = (string) $request->input('type');

            if ($type === SettingTypeEnum::PAYMENT() && !$this->isPaymentSettingsUnlocked($request)) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('Please verify admin password and authenticator code to edit payment settings.'),
                    data: []
                );
            }

            if ($type === SettingTypeEnum::AUTHENTICATION() && !$this->isAuthenticationSettingsUnlocked($request)) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('Please verify admin password and authenticator code to edit authentication settings.'),
                    data: []
                );
            }

            // Map setting type to the corresponding class
            $method = match ($type) {
                SettingTypeEnum::SYSTEM() => SystemSettingType::class,
                SettingTypeEnum::STORAGE() => StorageSettingType::class,
                SettingTypeEnum::EMAIL() => EmailSettingType::class,
                SettingTypeEnum::PAYMENT() => PaymentSettingType::class,
                SettingTypeEnum::AUTHENTICATION() => AuthenticationSettingType::class,
                SettingTypeEnum::NOTIFICATION() => NotificationSettingType::class,
                SettingTypeEnum::WEB() => WebSettingType::class,
                SettingTypeEnum::APP() => AppSettingType::class,
                SettingTypeEnum::DELIVERY_BOY() => DeliveryBoySettingType::class,
                SettingTypeEnum::HOME_GENERAL_SETTINGS() => HomeGeneralSettingType::class,
                SettingTypeEnum::SMS() => SmsSettingType::class,
                SettingTypeEnum::GST() => GstSettingType::class,
                SettingTypeEnum::SEO_ADVANCED() => SeoAdvancedSettingType::class,
                default => null,
            };

            if (!$method) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.invalid_type'),
                    data: []
                );
            }

            $setting = Setting::find($type);
            $existingValues = is_array($setting?->value) ? $setting->value : [];
            $payload = $request->all();

            // Some file widgets submit existing media URLs/paths as plain strings.
            // Strip those non-file values before validation and preserve existing value later.
            if ($type === SettingTypeEnum::SYSTEM()) {
                foreach (['logo', 'favicon', 'adminSignature'] as $mediaField) {
                    if (!$request->hasFile($mediaField)) {
                        unset($payload[$mediaField]);
                    }
                }
            }

            // For PAYMENT settings, merge existing DB values as defaults so that
            // fields absent from the form (wallet, bank transfer, other gateways)
            // are preserved rather than reset to empty on every save.
            if ($type === SettingTypeEnum::PAYMENT() && !empty($existingValues)) {
                $payload = array_merge($existingValues, $payload);
            }

            if ($type === SettingTypeEnum::PAYMENT()) {
                $payload = $this->ensureRazorpayWebhookSecretForDomain($request, $payload);
            }

            if ($type === SettingTypeEnum::WEB()) {
                $payload = $this->buildWebSettingsPayload($request, $payload, $existingValues, $method);
            }

            // Partial section save: merge submitted fields with existing values so
            // saving one section does not reset all other sections to defaults.
            $section = $request->input('_section');
            if ($type === SettingTypeEnum::SYSTEM() && $section && isset(self::SYSTEM_SECTION_FIELDS[$section])) {
                $sectionKeys = self::SYSTEM_SECTION_FIELDS[$section];
                // Seed with class defaults so required fields from other sections
                // always have valid values even when no DB row exists yet.
                $defaults = get_object_vars(new $method());
                $merged = array_merge($defaults, $existingValues);
                foreach ($sectionKeys as $field) {
                    // Submitted value takes priority; missing = unchecked checkbox → null
                    $merged[$field] = array_key_exists($field, $payload) ? $payload[$field] : null;
                }
                $payload = $merged;

                // The merge re-introduces logo/favicon/adminSignature as stored path strings.
                // Strip them again so the image validation rules only run against actual uploads.
                foreach (['logo', 'favicon', 'adminSignature'] as $mediaField) {
                    if (!$request->hasFile($mediaField)) {
                        unset($payload[$mediaField]);
                    }
                }
            }

            // Initialize settings object from request data
            $settings = $type === SettingTypeEnum::WEB()
                ? $this->hydrateSettingsObject($method, $payload)
                : $method::fromArray($payload);

            if ($type === SettingTypeEnum::SYSTEM()) {
                foreach (['logo', 'favicon', 'adminSignature'] as $mediaField) {
                    if (!$request->hasFile($mediaField) && !empty($existingValues[$mediaField])) {
                        $settings->{$mediaField} = $existingValues[$mediaField];
                    }
                }
            }

            // Handle media uploads
            $this->handleMediaUploads($request, $settings);

            // Prepare data for storage
            $data = [
                'variable' => $type,
                'value' => $settings->toJson(),
            ];

            // Authorize the module-wise update action
            try {
                $this->authorize('updateSetting', [Setting::class, $type]);
            } catch (AuthorizationException $e) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.unauthorized_access'),
                    data: []
                );
            }

            $shouldSyncWeb = $type === SettingTypeEnum::SYSTEM()
                && (!$section || $section === 'general');

            // Contact page syncs when General (companyAddress) or Support (email/phone) section is saved.
            $shouldSyncContact = $type === SettingTypeEnum::SYSTEM()
                && in_array($section, ['general', 'support'], true);

            // Determine which frontend cache tags to bust after save
            $revalidateTags = match ($type) {
                SettingTypeEnum::SYSTEM() => ['site-settings'],
                SettingTypeEnum::WEB()    => ['site-settings', 'web-settings'],
                default                   => [],
            };

            // Update or create setting
            if ($setting) {
                $setting->update($data);
                if ($shouldSyncWeb) {
                    $this->syncMergedWebGeneralSettings($request);
                }
                if ($shouldSyncContact) {
                    PageController::syncSystemSettingsToContact(
                        is_array($setting->fresh()?->value) ? $setting->fresh()->value : []
                    );
                    FrontendRevalidateService::revalidate(tags: ['contact-page'], paths: ['/contact']);
                }
                if ($revalidateTags) {
                    FrontendRevalidateService::revalidate(tags: $revalidateTags, paths: ['/']);
                }
                return ApiResponseType::sendJsonResponse(
                    success: true,
                    message: __('labels.setting_updated_successfully', ['type' => ucfirst(Str::replace('_', ' ', $type))]),
                    data: $setting
                );
            }

            $res = Setting::create($data);
            if ($shouldSyncWeb) {
                $this->syncMergedWebGeneralSettings($request);
            }
            if ($shouldSyncContact) {
                PageController::syncSystemSettingsToContact(
                    is_array($res->fresh()?->value) ? $res->fresh()->value : []
                );
                FrontendRevalidateService::revalidate(tags: ['contact-page'], paths: ['/contact']);
            }
            if ($revalidateTags) {
                FrontendRevalidateService::revalidate(tags: $revalidateTags, paths: ['/']);
            }
            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.setting_created_successfully', ['type' => $type]),
                data: $res
            );
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.validation_failed') . ': ' . $firstError,
                data: $e->errors()
            );
        }
    }

    /**
     * Handle media file uploads and assign paths to the settings object.
     *
     * Raster images are converted to WebP (quality 90) before storage.
     * PWA logo files must remain PNG for manifest compatibility.
     * The service-account JSON file is stored as-is on the local disk.
     *
     * @param Request $request
     * @param mixed $settings
     * @return void
     */
    private function handleMediaUploads(Request $request, $settings): void
    {
        // Fields that should be converted to WebP: baseName (no extension) => directory.
        $webpFields = [
            'logo'               => ['base' => fn() => 'logo-'                    . time(), 'path' => 'settings'],
            'favicon'            => ['base' => fn() => 'favicon-'                 . time(), 'path' => 'settings'],
            'siteHeaderDarkLogo' => ['base' => fn() => 'site-header-dark-logo-'   . time(), 'path' => 'settings'],
            'siteHeaderLogo'     => ['base' => fn() => 'site-header-logo-'        . time(), 'path' => 'settings'],
            'siteFooterLogo'     => ['base' => fn() => 'site-footer-logo-'        . time(), 'path' => 'settings'],
            'siteFavicon'        => ['base' => fn() => 'site-favicon-'            . time(), 'path' => 'settings'],
            'backgroundImage'    => ['base' => fn() => 'background-'              . uniqid(), 'path' => 'settings'],
            'icon'               => ['base' => fn() => 'icon-'                    . uniqid(), 'path' => 'settings'],
            'activeIcon'         => ['base' => fn() => 'active-icon-'             . uniqid(), 'path' => 'settings'],
            'adminSignature'     => ['base' => fn() => 'admin-signature-'         . time(), 'path' => 'settings'],
        ];

        foreach ($webpFields as $field => $config) {
            if ($request->hasFile($field)) {
                $file     = $request->file($field);
                $baseName = $config['base']();
                $settings->$field = ImageConversionService::storeAsWebP($file, $config['path'], $baseName);
            }
        }

        // PWA logos must stay PNG — browsers require it for web-app manifests.
        $pwaFields = [
            'pwaLogo192x192' => ['name' => 'pwa-logo-192x192-' . time() . '.png', 'path' => 'pwa_logos'],
            'pwaLogo512x512' => ['name' => 'pwa-logo-512x512-' . time() . '.png', 'path' => 'pwa_logos'],
            'pwaLogo144x144' => ['name' => 'pwa-logo-144x144-' . time() . '.png', 'path' => 'pwa_logos'],
        ];

        foreach ($pwaFields as $field => $config) {
            if ($request->hasFile($field)) {
                $settings->$field = $request->file($field)->storeAs($config['path'], $config['name'], 'public');
            }
        }

        if ($request->hasFile('seoOgImage')) {
            $settings->ogImage = ImageConversionService::storeAsWebP(
                $request->file('seoOgImage'), 'settings', 'seo-og-image-' . time()
            );
        }

        if ($request->hasFile('seoTwitterImage')) {
            $settings->twitterImage = ImageConversionService::storeAsWebP(
                $request->file('seoTwitterImage'), 'settings', 'seo-twitter-image-' . time()
            );
        }

        // Service-account JSON — not an image, store as-is on the local (non-public) disk.
        if ($request->hasFile('serviceAccountFile')) {
            $settings->serviceAccountFile = $request->file('serviceAccountFile')
                ->storeAs('settings', 'service-account-file.json', 'local');
        }
    }

    public function show($variable): \Illuminate\Http\RedirectResponse|View
    {
        try {

            $setting_variable = SettingTypeEnum::values();
            if (!in_array($variable, $setting_variable)) {
                abort(404, __('labels.invalid_type'));
            }

            if ($variable === SettingTypeEnum::WEB()) {
                return redirect()->route('admin.settings.show', ['setting' => SettingTypeEnum::SYSTEM()]);
            }

            if ($variable === SettingTypeEnum::PAYMENT()) {
                $this->ensureRazorpayWebhookSecretForDomain(request(), persist: true);
            }

            $transformedSetting = $this->settingService->getSettingByVariable($variable);

            if (!$transformedSetting) {
                abort(404, __('labels.setting_not_found'));
            }
            // Authorize module-wise view access
            $this->authorize('viewSetting', [Setting::class, $variable]);
            $settings = $transformedSetting->toArray(request())['value'] ?? [];
            $webSettings = [];
            if ($variable === SettingTypeEnum::SYSTEM()) {
                $webTransformedSetting = $this->settingService->getSettingByVariable(SettingTypeEnum::WEB());
                $webSettings = $webTransformedSetting?->toArray(request())['value'] ?? [];
            }

            $setting = Setting::find(SettingTypeEnum::AUTHENTICATION());
            $googleApiKey = $setting?->value['googleApiKey'] ?? null;
            return view('admin.settings.' . $variable, [
                'settings' => $settings,
                'webSettings' => $webSettings,
                'googleApiKey' => $googleApiKey,
                'paymentSettingsUnlocked' => $variable === SettingTypeEnum::PAYMENT()
                    ? $this->isPaymentSettingsUnlocked(request())
                    : false,
                'paymentUnlockTtlMinutes' => self::PAYMENT_UNLOCK_TTL_MINUTES,
                'authenticationSettingsUnlocked' => $variable === SettingTypeEnum::AUTHENTICATION()
                    ? $this->isAuthenticationSettingsUnlocked(request())
                    : false,
                'authenticationUnlockTtlMinutes' => self::AUTHENTICATION_UNLOCK_TTL_MINUTES,
            ]);
        } catch (AuthorizationException $e) {
            abort(403, __('labels.unauthorized_access'));
        } catch (\Throwable $e) {
            if ($e instanceof HttpExceptionInterface) {
                throw $e;
            }
            abort(500, __('labels.something_went_wrong'));
        }
    }

    public function unlockPaymentSettings(Request $request): JsonResponse
    {
        try {
            $this->authorize('updateSetting', [Setting::class, SettingTypeEnum::PAYMENT()]);
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.unauthorized_access'),
                data: [],
                status: 403
            );
        }

        $validated = $request->validate([
            'password' => 'required|string',
            'totp_code' => 'required|string|size:6',
        ]);

        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return ApiResponseType::sendJsonResponse(false, __('labels.unauthorized_access'), [], 401);
        }

        if (!Hash::check($validated['password'], $admin->password)) {
            return ApiResponseType::sendJsonResponse(false, __('Invalid password.'), [], 422);
        }

        $totpEnabled = $admin instanceof \App\Models\AdminUser && method_exists($admin, 'isTotpEnabled')
            ? $admin->isTotpEnabled()
            : (!empty($admin->totp_secret) && !empty($admin->totp_enabled_at));

        if (!$totpEnabled) {
            return ApiResponseType::sendJsonResponse(false, __('Admin TOTP is not enabled.'), [], 422);
        }

        if (!$this->totpService->verifyCode($admin->totp_secret, trim($validated['totp_code']))) {
            return ApiResponseType::sendJsonResponse(false, __('Invalid authenticator code.'), [], 422);
        }

        $request->session()->put(self::PAYMENT_UNLOCK_SESSION_KEY, now()->timestamp);

        return ApiResponseType::sendJsonResponse(true, __('Payment settings unlocked.'), [
            'unlocked' => true,
            'expires_in_seconds' => self::PAYMENT_UNLOCK_TTL_MINUTES * 60,
        ]);
    }

    public function lockPaymentSettings(Request $request): JsonResponse
    {
        $request->session()->forget(self::PAYMENT_UNLOCK_SESSION_KEY);

        return ApiResponseType::sendJsonResponse(true, __('Payment settings locked.'), [
            'unlocked' => false,
        ]);
    }

    private function isPaymentSettingsUnlocked(Request $request): bool
    {
        $unlockedAt = $request->session()->get(self::PAYMENT_UNLOCK_SESSION_KEY);
        if (empty($unlockedAt)) {
            return false;
        }

        $isValid = now()->timestamp <= ((int) $unlockedAt + (self::PAYMENT_UNLOCK_TTL_MINUTES * 60));

        if (!$isValid) {
            $request->session()->forget(self::PAYMENT_UNLOCK_SESSION_KEY);
        }

        return $isValid;
    }

    private function ensureRazorpayWebhookSecretForDomain(Request $request, array $paymentValues = [], bool $persist = false): array
    {
        $defaults = get_object_vars(new PaymentSettingType());
        $setting = Setting::find(SettingTypeEnum::PAYMENT());
        $storedValues = is_array($setting?->value) ? $setting->value : [];
        $values = array_merge($defaults, $storedValues, $paymentValues);

        $currentDomain = $this->resolveWebhookSecretDomain($request);
        $storedSecret = trim((string) ($values['razorpayWebhookSecret'] ?? ''));
        $storedDomain = strtolower(trim((string) ($values['razorpayWebhookSecretDomain'] ?? '')));

        $shouldGenerateSecret = $storedSecret === '' || ($currentDomain !== '' && $storedDomain !== $currentDomain);
        $shouldPersist = false;

        if ($shouldGenerateSecret) {
            $values['razorpayWebhookSecret'] = $this->generateRazorpayWebhookSecret();
            $values['razorpayWebhookSecretDomain'] = $currentDomain;
            $shouldPersist = true;
        } elseif ($currentDomain !== '' && $storedDomain !== $currentDomain) {
            $values['razorpayWebhookSecretDomain'] = $currentDomain;
            $shouldPersist = true;
        }

        if ($persist && $shouldPersist) {
            Setting::updateOrCreate(
                ['variable' => SettingTypeEnum::PAYMENT()],
                ['value' => json_encode($values)]
            );
        }

        return $values;
    }

    private function resolveWebhookSecretDomain(Request $request): string
    {
        $candidateUrls = [
            (string) config('app.frontendUrl', ''),
            (string) config('app.url', ''),
        ];

        foreach ($candidateUrls as $candidateUrl) {
            $host = parse_url($candidateUrl, PHP_URL_HOST);
            if (!empty($host)) {
                return strtolower((string) $host);
            }
        }

        return strtolower((string) $request->getHost());
    }

    private function generateRazorpayWebhookSecret(): string
    {
        return 'rzpwhsec_' . strtolower(bin2hex(random_bytes(24)));
    }

    public function unlockAuthenticationSettings(Request $request): JsonResponse
    {
        try {
            $this->authorize('updateSetting', [Setting::class, SettingTypeEnum::AUTHENTICATION()]);
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.unauthorized_access'),
                data: [],
                status: 403
            );
        }

        $validated = $request->validate([
            'password' => 'required|string',
            'totp_code' => 'required|string|size:6',
        ]);

        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return ApiResponseType::sendJsonResponse(false, __('labels.unauthorized_access'), [], 401);
        }

        if (!Hash::check($validated['password'], $admin->password)) {
            return ApiResponseType::sendJsonResponse(false, __('Invalid password.'), [], 422);
        }

        $totpEnabled = $admin instanceof \App\Models\AdminUser && method_exists($admin, 'isTotpEnabled')
            ? $admin->isTotpEnabled()
            : (!empty($admin->totp_secret) && !empty($admin->totp_enabled_at));

        if (!$totpEnabled) {
            return ApiResponseType::sendJsonResponse(false, __('Admin TOTP is not enabled.'), [], 422);
        }

        if (!$this->totpService->verifyCode($admin->totp_secret, trim($validated['totp_code']))) {
            return ApiResponseType::sendJsonResponse(false, __('Invalid authenticator code.'), [], 422);
        }

        $request->session()->put(self::AUTHENTICATION_UNLOCK_SESSION_KEY, now()->timestamp);

        return ApiResponseType::sendJsonResponse(true, __('Authentication settings unlocked.'), [
            'unlocked' => true,
            'expires_in_seconds' => self::AUTHENTICATION_UNLOCK_TTL_MINUTES * 60,
        ]);
    }

    public function lockAuthenticationSettings(Request $request): JsonResponse
    {
        $request->session()->forget(self::AUTHENTICATION_UNLOCK_SESSION_KEY);

        return ApiResponseType::sendJsonResponse(true, __('Authentication settings locked.'), [
            'unlocked' => false,
        ]);
    }

    private function isAuthenticationSettingsUnlocked(Request $request): bool
    {
        $unlockedAt = $request->session()->get(self::AUTHENTICATION_UNLOCK_SESSION_KEY);
        if (empty($unlockedAt)) {
            return false;
        }

        $isValid = now()->timestamp <= ((int) $unlockedAt + (self::AUTHENTICATION_UNLOCK_TTL_MINUTES * 60));
        if (!$isValid) {
            $request->session()->forget(self::AUTHENTICATION_UNLOCK_SESSION_KEY);
        }

        return $isValid;
    }

    /**
     * Keep merged "Website Branding & Basics" fields synced to WEB settings
     * even though they are edited from the SYSTEM form.
     */
    private function syncMergedWebGeneralSettings(Request $request): void
    {
        $webSetting = Setting::find(SettingTypeEnum::WEB());
        $webValues = is_array($webSetting?->value) ? $webSetting->value : [];

        // Merge app-level fields into website general fields for consistency.
        $webValues['siteName'] = $request->input('appName', $webValues['siteName'] ?? '');
        $webValues['siteCopyright'] = $request->input('copyrightDetails', $webValues['siteCopyright'] ?? '');
        $webValues['address'] = $request->input('address', $webValues['address'] ?? '');
        $webValues['shortDescription'] = $request->input('shortDescription', $webValues['shortDescription'] ?? '');
        $webValues['metaTitle'] = $request->input('metaTitle', $webValues['metaTitle'] ?? '');
        $webValues['metaKeywords'] = $request->input('metaKeywords', $webValues['metaKeywords'] ?? '');
        $webValues['metaDescription'] = $request->input('metaDescription', $webValues['metaDescription'] ?? '');
        $webValues['metaCanonicalUrl'] = $request->input('metaCanonicalUrl', $webValues['metaCanonicalUrl'] ?? '');
        $webValues['metaRobots'] = $request->input('metaRobots', $webValues['metaRobots'] ?? 'index,follow');
        $webValues['metaAuthor'] = $request->input('metaAuthor', $webValues['metaAuthor'] ?? '');
        $webValues['metaPublisher'] = $request->input('metaPublisher', $webValues['metaPublisher'] ?? '');
        $webValues['googleSiteVerification'] = $request->input('googleSiteVerification', $webValues['googleSiteVerification'] ?? '');
        $webValues['bingSiteVerification'] = $request->input('bingSiteVerification', $webValues['bingSiteVerification'] ?? '');
        $webValues['ogTitle'] = $request->input('ogTitle', $webValues['ogTitle'] ?? '');
        $webValues['ogDescription'] = $request->input('ogDescription', $webValues['ogDescription'] ?? '');
        $webValues['twitterCard'] = $request->input('twitterCard', $webValues['twitterCard'] ?? 'summary_large_image');
        $webValues['twitterSite'] = $request->input('twitterSite', $webValues['twitterSite'] ?? '');
        $webValues['twitterCreator'] = $request->input('twitterCreator', $webValues['twitterCreator'] ?? '');
        $webValues['twitterTitle'] = $request->input('twitterTitle', $webValues['twitterTitle'] ?? '');
        $webValues['twitterDescription'] = $request->input('twitterDescription', $webValues['twitterDescription'] ?? '');
        $webValues['seoSchemaJson'] = $request->input('seoSchemaJson', $webValues['seoSchemaJson'] ?? '');
        $webValues['googleAnalyticsId'] = $request->input('googleAnalyticsId', $webValues['googleAnalyticsId'] ?? '');
        $webValues['googleTagManagerId'] = $request->input('googleTagManagerId', $webValues['googleTagManagerId'] ?? '');
        $webValues['facebookPixelId'] = $request->input('facebookPixelId', $webValues['facebookPixelId'] ?? '');

        if ($request->hasFile('seoOgImage')) {
            $webValues['ogImage'] = ImageConversionService::storeAsWebP(
                $request->file('seoOgImage'), 'settings', 'seo-og-image-' . time()
            );
        }

        if ($request->hasFile('seoTwitterImage')) {
            $webValues['twitterImage'] = ImageConversionService::storeAsWebP(
                $request->file('seoTwitterImage'), 'settings', 'seo-twitter-image-' . time()
            );
        }

        Setting::updateOrCreate(
            ['variable' => SettingTypeEnum::WEB()],
            ['value' => json_encode($webValues, JSON_UNESCAPED_UNICODE)]
        );
    }

    private function buildWebSettingsPayload(Request $request, array $payload, array $existingValues, string $method): array
    {
        $section = $request->input('_section');
        $fields = self::WEB_SECTION_FIELDS[$section] ?? self::WEB_MAIN_FORM_FIELDS;
        $rules = $method::validationRules($fields);

        if ($section === 'seo') {
            $rules['seoOgImage'] = 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096';
            $rules['seoTwitterImage'] = 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096';
        }

        $validated = validator($payload, $rules)->validate();
        $merged = array_merge(get_object_vars(new $method()), $existingValues);

        foreach ($fields as $field) {
            if (array_key_exists($field, $validated)) {
                $merged[$field] = $validated[$field];
                continue;
            }

            if (in_array($field, self::WEB_BOOLEAN_FIELDS, true)) {
                $merged[$field] = false;
            }
        }

        return $merged;
    }

    private function hydrateSettingsObject(string $method, array $payload): object
    {
        $settings = new $method();

        $reflection = new \ReflectionClass($settings);

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $field = $property->getName();

            if (!array_key_exists($field, $payload)) {
                continue;
            }

            $value = $payload[$field];
            $type = $property->getType();

            if ($value === null && $type && !$type->allowsNull()) {
                $value = match ($type->getName()) {
                    'array' => [],
                    'bool' => false,
                    'int' => 0,
                    'float' => 0.0,
                    default => '',
                };
            }

            $settings->{$field} = $value;
        }

        return $settings;
    }

}
