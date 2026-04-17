@php use App\Enums\PoliciesEnum; @endphp
<form id="web-support-settings-form" action="{{ route('admin.settings.store') }}" class="form-submit" method="post" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="type" value="web">
    <input type="hidden" name="_section" value="support">
</form>

<form id="web-seo-settings-form" action="{{ route('admin.settings.store') }}" class="form-submit" method="post" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="type" value="web">
    <input type="hidden" name="_section" value="seo">
</form>

<form id="web-footer-seo-settings-form" action="{{ route('admin.settings.store') }}" class="form-submit" method="post" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="type" value="web">
    <input type="hidden" name="_section" value="footer_seo">
</form>

<form action="{{route('admin.settings.store')}}" class="form-submit" method="post"
        enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="type" value="web">
    {{-- Web General has been merged into System General section --}}

    <!-- Default Location Section -->
    <div class="card mb-4" id="pills-web-default-location">
        <div class="card-header">
            <h4 class="card-title">{{ __('labels.default_location') }}</h4>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label
                    class="form-label required">{{ __('labels.default_location') }}</label>
                <div class="position-relative">
                    <!-- Search field will be positioned inside the map -->
                    <div id="place-autocomplete-card"
                            style="position: absolute; top: 10px; left: 10px; z-index: 1000; ">
                        <!-- This will be populated by JavaScript -->
                    </div>
                    <div id="default-location-map"
                            style="height: 400px; width: 100%;"></div>
                </div>
            </div>
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label
                            class="form-label required">{{ __('labels.latitude') }}</label>
                        <input type="number" class="form-control" id="default-latitude"
                                name="defaultLatitude" step="any"
                                placeholder="{{ __('labels.latitude_placeholder') }}"
                                value="{{ $settings['defaultLatitude'] ?? '' }}" required/>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label
                            class="form-label required">{{ __('labels.longitude') }}</label>
                        <input type="number" class="form-control" id="default-longitude"
                                name="defaultLongitude" step="any"
                                placeholder="{{ __('labels.longitude_placeholder') }}"
                                value="{{ $settings['defaultLongitude'] ?? '' }}" required/>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Country Validation Section (temporarily hidden) --}}
    {{-- <div class="card mb-4" id="pills-web-country-validation">
        <div class="card-header">
            <h4 class="card-title">{{ __('labels.country_validation') }}</h4>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="row">
                    <span class="col">{{ __('labels.enable_country_validation') }}</span>
                    <span class="col-auto">
                            <label class="form-check form-check-single form-switch">
                                <input class="form-check-input" type="checkbox"
                                        name="enableCountryValidation" value="1"
                                        {{ isset($settings['enableCountryValidation']) && $settings['enableCountryValidation'] ? 'checked' : '' }} />
                            </label>
                        </span>
                </label>
            </div>
            <div class="mb-3">
                <label
                    class="form-label required">{{ __('labels.allowed_countries') }}</label>
                <select class="form-select" id="select-countries" name="allowedCountries[]"
                        multiple placeholder="{{ __('labels.select_countries') }}">
                </select>
                <input type="hidden" id="selected-country"
                        value='@json($settings['allowedCountries'] ?? "")'>
            </div>
        </div>
    </div> --}}

    <div class="card mb-4" id="pills-web-support">
        <div class="card-header">
            <h4 class="card-title">{{ __('labels.support_information') }}</h4>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label
                    class="form-label required">{{ __('labels.support_email') }}</label>
                <input type="email" class="form-control" name="supportEmail" form="web-support-settings-form"
                        placeholder="{{ __('labels.support_email_placeholder') }}"
                        value="{{ $settings['supportEmail'] ?? '' }}" maxlength="255"
                        required/>
            </div>
            <div class="mb-3">
                <label
                    class="form-label required">{{ __('labels.support_number') }}</label>
                <input type="tel" class="form-control" name="supportNumber" form="web-support-settings-form"
                        placeholder="{{ __('labels.support_number_placeholder') }}"
                        value="{{ $settings['supportNumber'] ?? '' }}" maxlength="20"
                        required/>
            </div>
            <div class="mb-3">
                <label
                    class="form-label">{{ __('labels.google_map_key') }}</label>
                <input type="text" class="form-control" name="googleMapKey" form="web-support-settings-form"
                        placeholder="{{ __('labels.google_map_key_placeholder') }}"
                        value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['googleMapKey'] ?? '****'), '****', 3, 8) : ($settings['googleMapKey'] ?? '') }}" maxlength="255"/>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('labels.map_iframe') }}</label>
                <textarea class="form-control" name="mapIframe" form="web-support-settings-form"
                            placeholder="{{ __('labels.map_iframe_placeholder') }}">{{ $settings['mapIframe'] ?? '' }}</textarea>
            </div>
        </div>
        <div class="card-footer text-end">
            @can('updateSetting', [\App\Models\Setting::class, 'web'])
                <button type="submit" class="btn btn-primary" form="web-support-settings-form">Save Support Information</button>
            @endcan
        </div>
    </div>
    <div class="card mb-4" id="pills-web-seo">
        <div class="card-header">
            <h4 class="card-title">{{ __('labels.seo_settings') }}</h4>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Meta Title (Homepage)</label>
                <input type="text" class="form-control" name="metaTitle" form="web-seo-settings-form"
                        placeholder="Enter homepage meta title"
                        value="{{ $settings['metaTitle'] ?? '' }}" maxlength="255"/>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('labels.meta_keywords') }}</label>
                <input type="text" class="form-control" name="metaKeywords" form="web-seo-settings-form"
                        placeholder="{{ __('labels.meta_keywords_placeholder') }}"
                        value="{{ $settings['metaKeywords'] ?? '' }}" maxlength="255"/>
            </div>
            <div class="mb-3">
                <label
                    class="form-label">{{ __('labels.meta_description') }}</label>
                <textarea class="form-control" name="metaDescription" form="web-seo-settings-form"
                            placeholder="{{ __('labels.meta_description_placeholder') }}"
                            maxlength="500">{{ $settings['metaDescription'] ?? '' }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Canonical URL (Homepage)</label>
                <input type="url" class="form-control" name="metaCanonicalUrl" form="web-seo-settings-form"
                        placeholder="https://example.com/"
                        value="{{ $settings['metaCanonicalUrl'] ?? '' }}" maxlength="500"/>
            </div>
            <div class="mb-3">
                <label class="form-label">Meta Robots</label>
                <select class="form-select" name="metaRobots" form="web-seo-settings-form">
                    @php $robotsValue = $settings['metaRobots'] ?? 'index,follow'; @endphp
                    <option value="index,follow" {{ $robotsValue === 'index,follow' ? 'selected' : '' }}>index,follow</option>
                    <option value="noindex,follow" {{ $robotsValue === 'noindex,follow' ? 'selected' : '' }}>noindex,follow</option>
                    <option value="index,nofollow" {{ $robotsValue === 'index,nofollow' ? 'selected' : '' }}>index,nofollow</option>
                    <option value="noindex,nofollow" {{ $robotsValue === 'noindex,nofollow' ? 'selected' : '' }}>noindex,nofollow</option>
                </select>
            </div>
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Meta Author</label>
                        <input type="text" class="form-control" name="metaAuthor" form="web-seo-settings-form"
                                placeholder="Enter meta author"
                                value="{{ $settings['metaAuthor'] ?? '' }}" maxlength="255"/>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Meta Publisher</label>
                        <input type="text" class="form-control" name="metaPublisher" form="web-seo-settings-form"
                                placeholder="Enter meta publisher"
                                value="{{ $settings['metaPublisher'] ?? '' }}" maxlength="255"/>
                    </div>
                </div>
            </div>
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Google Site Verification</label>
                        <input type="text" class="form-control" name="googleSiteVerification" form="web-seo-settings-form"
                                placeholder="Paste Google verification token"
                                value="{{ $settings['googleSiteVerification'] ?? '' }}" maxlength="255"/>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Bing Site Verification</label>
                        <input type="text" class="form-control" name="bingSiteVerification" form="web-seo-settings-form"
                                placeholder="Paste Bing verification token"
                                value="{{ $settings['bingSiteVerification'] ?? '' }}" maxlength="255"/>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <h5 class="mb-3">Open Graph</h5>
            <div class="mb-3">
                <label class="form-label">OG Title</label>
                <input type="text" class="form-control" name="ogTitle" form="web-seo-settings-form"
                        placeholder="Enter Open Graph title"
                        value="{{ $settings['ogTitle'] ?? '' }}" maxlength="255"/>
            </div>
            <div class="mb-3">
                <label class="form-label">OG Description</label>
                <textarea class="form-control" name="ogDescription" form="web-seo-settings-form"
                            placeholder="Enter Open Graph description"
                            maxlength="500">{{ $settings['ogDescription'] ?? '' }}</textarea>
            </div>
            <div class="mb-3">
                <div class="form-label">OG Image</div>
                <input type="file" name="seoOgImage" form="web-seo-settings-form"
                        data-image-url="{{ $settings['ogImage'] ?? '' }}"/>
                <small class="form-hint">Recommended: 1200x630 PNG/JPG/WEBP.</small>
            </div>
            <hr class="my-4">
            <h5 class="mb-3">X (Twitter)</h5>
            <div class="mb-3">
                <label class="form-label">X Card (twitter:card)</label>
                @php $twitterCard = $settings['twitterCard'] ?? 'summary_large_image'; @endphp
                <select class="form-select" name="twitterCard" form="web-seo-settings-form">
                    <option value="summary" {{ $twitterCard === 'summary' ? 'selected' : '' }}>summary</option>
                    <option value="summary_large_image" {{ $twitterCard === 'summary_large_image' ? 'selected' : '' }}>summary_large_image</option>
                    <option value="app" {{ $twitterCard === 'app' ? 'selected' : '' }}>app</option>
                    <option value="player" {{ $twitterCard === 'player' ? 'selected' : '' }}>player</option>
                </select>
            </div>
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">X Site Handle (twitter:site)</label>
                        <input type="text" class="form-control" name="twitterSite" form="web-seo-settings-form"
                                placeholder="pethiyan"
                                value="{{ $settings['twitterSite'] ?? '' }}" maxlength="100"/>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">X Creator Handle (twitter:creator)</label>
                        <input type="text" class="form-control" name="twitterCreator" form="web-seo-settings-form"
                                placeholder="pethiyan"
                                value="{{ $settings['twitterCreator'] ?? '' }}" maxlength="100"/>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">X Title (twitter:title)</label>
                <input type="text" class="form-control" name="twitterTitle" form="web-seo-settings-form"
                    placeholder="Enter X title"
                    value="{{ $settings['twitterTitle'] ?? '' }}" maxlength="250"/>
            </div>
            <div class="mb-3">
                <label class="form-label">X Description (twitter:description)</label>
                <textarea class="form-control" name="twitterDescription" form="web-seo-settings-form"
                            placeholder="Enter X description"
                            maxlength="500">{{ $settings['twitterDescription'] ?? '' }}</textarea>
            </div>
            <div class="mb-3">
                <div class="form-label">X Image (twitter:image)</div>
                <input type="file" name="seoTwitterImage" form="web-seo-settings-form"
                        data-image-url="{{ $settings['twitterImage'] ?? '' }}"/>
                <small class="form-hint">Recommended: 1200x630 PNG/JPG/WEBP.</small>
            </div>
            <hr class="my-4">
            <div class="mb-3">
                <label class="form-label">Schema JSON-LD (Homepage)</label>
                <textarea class="form-control" name="seoSchemaJson" form="web-seo-settings-form" rows="5"
                            placeholder='Paste valid JSON-LD schema for homepage'>{{ $settings['seoSchemaJson'] ?? '' }}</textarea>
            </div>
            <hr class="my-4">
            <div class="mb-3">
                <label class="form-label">Google Analytics 4 Measurement ID</label>
                <input type="text" class="form-control" name="googleAnalyticsId" form="web-seo-settings-form"
                        placeholder="e.g. G-XXXXXXXXXX"
                        value="{{ $settings['googleAnalyticsId'] ?? '' }}" maxlength="50"/>
                <small class="form-hint">Found in Google Analytics → Admin → Data Streams → your stream → Measurement ID.</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Google Tag Manager Container ID</label>
                <input type="text" class="form-control" name="googleTagManagerId" form="web-seo-settings-form"
                        placeholder="e.g. GTM-XXXXXXX"
                        value="{{ $settings['googleTagManagerId'] ?? '' }}" maxlength="50"/>
                <small class="form-hint">Found in Google Tag Manager → your container → Container ID (top-right).</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Facebook / Meta Pixel ID</label>
                <input type="text" class="form-control" name="facebookPixelId" form="web-seo-settings-form"
                        placeholder="e.g. 1234567890123456"
                        value="{{ $settings['facebookPixelId'] ?? '' }}" maxlength="50"/>
                <small class="form-hint">Found in Meta Events Manager → your pixel → Pixel ID.</small>
            </div>
        </div>
        <div class="card-footer text-end">
            @can('updateSetting', [\App\Models\Setting::class, 'web'])
                <button type="submit" class="btn btn-primary" form="web-seo-settings-form">Save SEO Settings</button>
            @endcan
        </div>
    </div>
    
    <!-- Footer SEO Settings Section -->
    <div class="card mb-4" id="pills-web-footer-seo">
        <div class="card-header">
            <h4 class="card-title">Footer SEO Content</h4>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Enable Footer SEO Section</label>
                <label class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="footerSeoEnabled" form="web-footer-seo-settings-form" value="1" {{ !isset($settings['footerSeoEnabled']) || $settings['footerSeoEnabled'] ? 'checked' : '' }}>
                    <span class="form-check-label">Show the SEO text section at the bottom of the website</span>
                </label>
            </div>
            <div class="mb-3">
                <label class="form-label">Display Scope</label>
                <label class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="footerSeoHomepageOnly" form="web-footer-seo-settings-form" value="1" {{ !empty($settings['footerSeoHomepageOnly']) ? 'checked' : '' }}>
                    <span class="form-check-label">Show only on the Home page (uncheck to show on every page)</span>
                </label>
            </div>
            <div class="mb-3">
                <label class="form-label">Introductory Paragraph</label>
                <textarea class="hugerte-mytextarea form-control" name="footerSeoIntro" form="web-footer-seo-settings-form" rows="4">{{ $settings['footerSeoIntro'] ?? '' }}</textarea>
            </div>
        </div>
        <div class="card-footer text-end">
            @can('updateSetting', [\App\Models\Setting::class, 'web'])
                <button type="submit" class="btn btn-primary" form="web-footer-seo-settings-form">Save Footer SEO Content</button>
            @endcan
        </div>
    </div>

    {{-- Social Media card removed here to avoid duplicate with the unified
            "Social Media Links" section in system settings page --}}
    {{-- App Download Section (temporarily hidden) --}}
    {{--<div class="card mb-4" id="pills-web-app">
        <div class="card-header">
            <h4 class="card-title">{{ __('labels.app_download_section') }}</h4>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="row">
                        <span
                            class="col">{{ __('labels.app_download_section') }}</span>
                    <span class="col-auto">
                            <label class="form-check form-check-single form-switch">
                                <input class="form-check-input" type="checkbox"
                                        name="appDownloadSection" value="1" {{ isset($settings['appDownloadSection']) && $settings['appDownloadSection'] ? 'checked' : '' }} />
                            </label>
                        </span>
                </label>
            </div>
            <div class="mb-3">
                <label
                    class="form-label">{{ __('labels.app_section_title') }}</label>
                <input type="text" class="form-control" name="appSectionTitle"
                        placeholder="{{ __('labels.app_section_title_placeholder') }}"
                        value="{{ $settings['appSectionTitle'] ?? '' }}"
                        maxlength="255"/>
            </div>
            <div class="mb-3">
                <label
                    class="form-label">{{ __('labels.app_section_tagline') }}</label>
                <input type="text" class="form-control" name="appSectionTagline"
                        placeholder="{{ __('labels.app_section_tagline_placeholder') }}"
                        value="{{ $settings['appSectionTagline'] ?? '' }}"
                        maxlength="255"/>
            </div>
            <div class="mb-3">
                <label
                    class="form-label">{{ __('labels.app_section_playstore_link') }}</label>
                <input type="url" class="form-control" name="appSectionPlaystoreLink"
                        placeholder="{{ __('labels.app_section_playstore_link_placeholder') }}"
                        value="{{ $settings['appSectionPlaystoreLink'] ?? '' }}"
                        maxlength="255"/>
            </div>
            <div class="mb-3">
                <label
                    class="form-label">{{ __('labels.app_section_appstore_link') }}</label>
                <input type="url" class="form-control" name="appSectionAppstoreLink"
                        placeholder="{{ __('labels.app_section_appstore_link_placeholder') }}"
                        value="{{ $settings['appSectionAppstoreLink'] ?? '' }}"
                        maxlength="255"/>
            </div>
            <div class="mb-3">
                <label
                    class="form-label">{{ __('labels.app_section_short_description') }}</label>
                <textarea class="form-control" name="appSectionShortDescription"
                            placeholder="{{ __('labels.app_section_short_description_placeholder') }}"
                            maxlength="500">{{ $settings['appSectionShortDescription'] ?? '' }}</textarea>
            </div>
        </div>
    </div>--}}
    {{--<div class="card mb-4" id="pills-web-features">
        <div class="card-header">
            <h4 class="card-title">{{ __('labels.feature_sections') }}</h4>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label
                    class="form-label">{{ __('labels.shipping_feature_section') }}</label>
                <input type="text" class="form-control" name="shippingFeatureSection"
                        placeholder="{{ __('labels.shipping_feature_section_placeholder') }}"
                        value="{{ $settings['shippingFeatureSection'] ?? '' }}"
                        maxlength="255"/>
            </div>
            <div class="mb-3">
                <label
                    class="form-label">{{ __('labels.shipping_feature_section_title') }}</label>
                <input type="text" class="form-control"
                        name="shippingFeatureSectionTitle"
                        placeholder="{{ __('labels.shipping_feature_section_title_placeholder') }}"
                        value="{{ $settings['shippingFeatureSectionTitle'] ?? '' }}"
                        maxlength="255"/>
            </div>
            <div class="mb-3">
                <label
                    class="form-label">{{ __('labels.shipping_feature_section_description') }}</label>
                <textarea class="form-control" name="shippingFeatureSectionDescription"
                            placeholder="{{ __('labels.shipping_feature_section_description_placeholder') }}"
                            maxlength="500">{{ $settings['shippingFeatureSectionDescription'] ?? '' }}</textarea>
            </div>
            <div class="mb-3">
                <label
                    class="form-label">{{ __('labels.return_feature_section') }}</label>
                <input type="text" class="form-control" name="returnFeatureSection"
                        placeholder="{{ __('labels.return_feature_section_placeholder') }}"
                        value="{{ $settings['returnFeatureSection'] ?? '' }}"
                        maxlength="255"/>
            </div>
            <div class="mb-3">
                <label
                    class="form-label">{{ __('labels.return_feature_section_title') }}</label>
                <input type="text" class="form-control" name="returnFeatureSectionTitle"
                        placeholder="{{ __('labels.return_feature_section_title_placeholder') }}"
                        value="{{ $settings['returnFeatureSectionTitle'] ?? '' }}"
                        maxlength="255"/>
            </div>
            <div class="mb-3">
                <label
                    class="form-label">{{ __('labels.return_feature_section_description') }}</label>
                <textarea class="form-control" name="returnFeatureSectionDescription"
                            placeholder="{{ __('labels.return_feature_section_description_placeholder') }}"
                            maxlength="500">{{ $settings['returnFeatureSectionDescription'] ?? '' }}</textarea>
            </div>
            <div class="mb-3">
                <label
                    class="form-label">{{ __('labels.safety_security_feature_section') }}</label>
                <input type="text" class="form-control"
                        name="safetySecurityFeatureSection"
                        placeholder="{{ __('labels.safety_security_feature_section_placeholder') }}"
                        value="{{ $settings['safetySecurityFeatureSection'] ?? '' }}"
                        maxlength="255"/>
            </div>
            <div class="mb-3">
                <label
                    class="form-label">{{ __('labels.safety_security_feature_section_title') }}</label>
                <input type="text" class="form-control"
                        name="safetySecurityFeatureSectionTitle"
                        placeholder="{{ __('labels.safety_security_feature_section_title_placeholder') }}"
                        value="{{ $settings['safetySecurityFeatureSectionTitle'] ?? '' }}"
                        maxlength="255"/>
            </div>
            <div class="mb-3">
                <label
                    class="form-label">{{ __('labels.safety_security_feature_section_description') }}</label>
                <textarea class="form-control"
                            name="safetySecurityFeatureSectionDescription"
                            placeholder="{{ __('labels.safety_security_feature_section_description_placeholder') }}"
                            maxlength="500">{{ $settings['safetySecurityFeatureSectionDescription'] ?? '' }}</textarea>
            </div>
            <div class="mb-3">
                <label
                    class="form-label">{{ __('labels.support_feature_section') }}</label>
                <input type="text" class="form-control" name="supportFeatureSection"
                        placeholder="{{ __('labels.support_feature_section_placeholder') }}"
                        value="{{ $settings['supportFeatureSection'] ?? '' }}"
                        maxlength="255"/>
            </div>
            <div class="mb-3">
                <label
                    class="form-label">{{ __('labels.support_feature_section_title') }}</label>
                <input type="text" class="form-control"
                        name="supportFeatureSectionTitle"
                        placeholder="{{ __('labels.support_feature_section_title_placeholder') }}"
                        value="{{ $settings['supportFeatureSectionTitle'] ?? '' }}"
                        maxlength="255"/>
            </div>
            <div class="mb-3">
                <label
                    class="form-label">{{ __('labels.support_feature_section_description') }}</label>
                <textarea class="form-control" name="supportFeatureSectionDescription"
                            placeholder="{{ __('labels.support_feature_section_description_placeholder') }}"
                            maxlength="500">{{ $settings['supportFeatureSectionDescription'] ?? '' }}</textarea>
            </div>
        </div>
    </div>--}}

    <!-- Policy Settings Section -->
    <div class="card mb-4" id="pills-web-policies">
        <div class="card-header">
            <h4 class="card-title">{{ __('labels.policy_settings') }}</h4>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">{{ __('labels.return_refund_policy') }}
                    <a href="{{ route('policies.show', PoliciesEnum::REFUND_AND_RETURN()) }}"
                        target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-eye">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/>
                            <path
                                d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/>
                        </svg>
                    </a>
                </label>
                <textarea class="hugerte-mytextarea" name="returnRefundPolicy" rows="8"
                            placeholder="{{ __('labels.return_refund_policy_placeholder') }}">{{ $settings['returnRefundPolicy'] ?? '' }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('labels.shipping_policy') }}
                    <a href="{{ route('policies.show', PoliciesEnum::SHIPPING()) }}"
                        target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-eye">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/>
                            <path
                                d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/>
                        </svg>
                    </a></label>
                <textarea class="hugerte-mytextarea" name="shippingPolicy" rows="8"
                            placeholder="{{ __('labels.shipping_policy_placeholder') }}">{{ $settings['shippingPolicy'] ?? '' }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('labels.privacy_policy') }}
                    <a href="{{ route('policies.show', PoliciesEnum::PRIVACY()) }}"
                        target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-eye">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/>
                            <path
                                d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/>
                        </svg>
                    </a></label>
                <textarea class="hugerte-mytextarea" name="privacyPolicy" rows="8"
                            placeholder="{{ __('labels.privacy_policy_placeholder') }}">{{ $settings['privacyPolicy'] ?? '' }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('labels.terms_condition') }}
                    <a href="{{ route('policies.show', PoliciesEnum::TERMS()) }}"
                        target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-eye">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/>
                            <path
                                d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/>
                        </svg>
                    </a></label>
                <textarea class="hugerte-mytextarea" name="termsCondition" rows="8"
                            placeholder="{{ __('labels.terms_condition_placeholder') }}">{{ $settings['termsCondition'] ?? '' }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('labels.about_us') }}</label>
                <textarea class="hugerte-mytextarea" name="aboutUs" rows="8"
                            placeholder="{{ __('labels.about_us_placeholder') }}">{{ $settings['aboutUs'] ?? '' }}</textarea>
            </div>
        </div>
    </div>

    <!-- PWA Manifest Settings Section -->
    {{--<div class="card mb-4" id="pills-web-pwa-manifest">
        <div class="card-header">
            <h4 class="card-title">{{ __('labels.pwa_manifest_settings') }}</h4>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label required">{{ __('labels.pwa_name') }}</label>
                <input type="text" class="form-control" name="pwaName"
                        placeholder="{{ __('labels.pwa_name_placeholder') }}"
                        value="{{ $settings['pwaName'] ?? '' }}" maxlength="255" required/>
            </div>
            <div class="mb-3">
                <label
                    class="form-label required">{{ __('labels.pwa_description') }}</label>
                <textarea class="form-control" name="pwaDescription"
                            placeholder="{{ __('labels.pwa_description_placeholder') }}"
                            maxlength="500"
                            required>{{ $settings['pwaDescription'] ?? '' }}</textarea>
            </div>
            <div class="row g-2">
                <div class="col-md-4">
                    <div class="mb-3">
                        <div
                            class="form-label required">{{ __('labels.pwa_logo_192x192') }}</div>
                        <x-filepond_image name="pwaLogo192x192"
                                            imageUrl="{{ $settings['pwaLogo192x192'] ?? '' }}"
                                            data-accepted-file-types="image/png,image/jpeg,image/webp"
                                            data-max-file-size="2MB"
                                            data-image-crop-aspect-ratio="1:1"
                                            data-image-resize-target-width="192"
                                            data-image-resize-target-height="192"/>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <div
                            class="form-label required">{{ __('labels.pwa_logo_512x512') }}</div>
                        <x-filepond_image name="pwaLogo512x512"
                                            imageUrl="{{ $settings['pwaLogo512x512'] ?? '' }}"
                                            data-accepted-file-types="image/png,image/jpeg,image/webp"
                                            data-max-file-size="2MB"
                                            data-image-crop-aspect-ratio="1:1"
                                            data-image-resize-target-width="512"
                                            data-image-resize-target-height="512"/>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <div
                            class="form-label required">{{ __('labels.pwa_logo_144x144') }}</div>
                        <x-filepond_image name="pwaLogo144x144"
                                            imageUrl="{{ $settings['pwaLogo144x144'] ?? '' }}"
                                            data-accepted-file-types="image/png,image/jpeg,image/webp"
                                            data-max-file-size="2MB"
                                            data-image-crop-aspect-ratio="1:1"
                                            data-image-resize-target-width="144"
                                            data-image-resize-target-height="144"/>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4" id="pills-web-scripts">
        <div class="card-header">
            <h4 class="card-title">{{ __('labels.scripts') }}</h4>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">{{ __('labels.header_script') }}</label>
                <textarea class="form-control" name="headerScript"
                            placeholder="{{ __('labels.header_script_placeholder') }}">{{ $settings['headerScript'] ?? '' }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('labels.footer_script') }}</label>
                <textarea class="form-control" name="footerScript"
                            placeholder="{{ __('labels.footer_script_placeholder') }}">{{ $settings['footerScript'] ?? '' }}</textarea>
            </div>
        </div>
    </div>--}}
    <div class="card-footer text-end">
        <div class="d-flex">
            @can('updateSetting', [\App\Models\Setting::class, 'web'])
                <button type="submit"
                        class="btn btn-primary ms-auto">{{ __('labels.submit') }}</button>
            @endcan
        </div>
    </div>
</form>
