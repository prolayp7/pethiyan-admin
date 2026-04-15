<?php

namespace App\Types\Settings;

use App\Interfaces\SettingInterface;
use App\Traits\SettingTrait;

class WebSettingType implements SettingInterface
{
    use SettingTrait;

    public string $siteName = '';
    public string $siteCopyright = '';

    public string $supportNumber = "";
    public string $supportEmail = "";

    public string $address = '';
    public string $shortDescription = '';
    public string $siteHeaderLogo = '';
    public string $siteHeaderDarkLogo = '';
    public string $siteFooterLogo = '';
    public string $siteFavicon = '';
    public string $headerScript = '';
    public string $footerScript = '';
    public string $googleMapKey = '';
    public string $mapIframe = '';
    public bool $appDownloadSection = false;
    public string $appSectionTitle = '';
    public string $appSectionTagline = '';
    public string $appSectionPlaystoreLink = '';
    public string $appSectionAppstoreLink = '';
    public string $appSectionShortDescription = '';
    public string $facebookLink = '';
    public string $instagramLink = '';
    public string $xLink = '';
    public string $youtubeLink = '';
    public string $shippingFeatureSection = '';
    public string $shippingFeatureSectionTitle = '';
    public string $shippingFeatureSectionDescription = '';
    public string $returnFeatureSection = '';
    public string $returnFeatureSectionTitle = '';
    public string $returnFeatureSectionDescription = '';
    public string $safetySecurityFeatureSection = '';
    public string $safetySecurityFeatureSectionTitle = '';
    public string $safetySecurityFeatureSectionDescription = '';
    public string $supportFeatureSection = '';
    public string $supportFeatureSectionTitle = '';
    public string $supportFeatureSectionDescription = '';
    public string $metaTitle = '';
    public string $metaKeywords = '';
    public string $metaDescription = '';
    public string $metaCanonicalUrl = '';
    public string $metaRobots = 'index,follow';
    public string $metaAuthor = '';
    public string $metaPublisher = '';
    public string $googleSiteVerification = '';
    public string $bingSiteVerification = '';
    public string $ogTitle = '';
    public string $ogDescription = '';
    public string $ogImage = '';
    public string $twitterCard = 'summary_large_image';
    public string $twitterSite = '';
    public string $twitterCreator = '';
    public string $twitterTitle = '';
    public string $twitterDescription = '';
    public string $twitterImage = '';
    public string $seoSchemaJson = '';
    public string $googleAnalyticsId = '';
    public string $googleTagManagerId = '';
    public string $facebookPixelId = '';
    public bool $footerSeoEnabled = true;
    public bool $footerSeoHomepageOnly = false;
    public string $footerSeoTitle = '';
    public string $footerSeoIntro = '';
    public string $footerSeoSectionsJson = '';
    public array $allowedCountries = [];
    public bool $enableCountryValidation = false;
    public string $defaultLongitude = '';
    public string $defaultLatitude = '';

    // Policy Settings
    public string $returnRefundPolicy = '';
    public string $shippingPolicy = '';
    public string $privacyPolicy = '';
    public string $termsCondition = '';
    public string $aboutUs = '';
    public string $pwaName = '';
    public string $pwaDescription = '';
    public string $pwaLogo192x192 = '';
    public string $pwaLogo512x512 = '';
    public string $pwaLogo144x144 = '';

    protected static function getValidationRules(): array
    {
        return [
            'siteName' => 'required|string|max:255',
            'siteCopyright' => 'required|string|max:255',
            'supportNumber' => 'required|string|max:20',
            'supportEmail' => 'required|email|max:255',
            'address' => 'required|string|max:255',
            'shortDescription' => 'required|string|max:500',
            'siteHeaderLogo' => 'required|image|mimes:png,webp',
            'siteHeaderDarkLogo' => 'required|image|mimes:png,webp',
            'siteFooterLogo' => 'required|image|mimes:png,webp',
            'siteFavicon' => 'required|mimes:png,jpg,jpeg,webp,ico',
            'headerScript' => 'nullable|string',
            'footerScript' => 'nullable|string',
            'googleMapKey' => 'nullable|string|max:255',
            'mapIframe' => 'nullable|string',
            'appDownloadSection' => 'nullable|boolean',
            'appSectionTitle' => 'nullable|string|max:255',
            'appSectionTagline' => 'nullable|string|max:255',
            'appSectionPlaystoreLink' => 'nullable|string|max:255',
            'appSectionAppstoreLink' => 'nullable|string|max:255',
            'appSectionShortDescription' => 'nullable|string|max:500',
            'facebookLink' => 'nullable|string|max:255',
            'instagramLink' => 'nullable|string|max:255',
            'xLink' => 'nullable|string|max:255',
            'youtubeLink' => 'nullable|string|max:255',
            'shippingFeatureSection' => 'nullable|string|max:255',
            'shippingFeatureSectionTitle' => 'nullable|string|max:255',
            'shippingFeatureSectionDescription' => 'nullable|string|max:500',
            'returnFeatureSection' => 'nullable|string|max:255',
            'returnFeatureSectionTitle' => 'nullable|string|max:255',
            'returnFeatureSectionDescription' => 'nullable|string|max:500',
            'safetySecurityFeatureSection' => 'nullable|string|max:255',
            'safetySecurityFeatureSectionTitle' => 'nullable|string|max:255',
            'safetySecurityFeatureSectionDescription' => 'nullable|string|max:500',
            'supportFeatureSection' => 'nullable|string|max:255',
            'supportFeatureSectionTitle' => 'nullable|string|max:255',
            'supportFeatureSectionDescription' => 'nullable|string|max:500',
            'metaTitle' => 'nullable|string|max:255',
            'metaKeywords' => 'nullable|string|max:255',
            'metaDescription' => 'nullable|string|max:500',
            'metaCanonicalUrl' => 'nullable|url|max:500',
            'metaRobots' => 'nullable|string|max:100',
            'metaAuthor' => 'nullable|string|max:255',
            'metaPublisher' => 'nullable|string|max:255',
            'googleSiteVerification' => 'nullable|string|max:255',
            'bingSiteVerification' => 'nullable|string|max:255',
            'ogTitle' => 'nullable|string|max:255',
            'ogDescription' => 'nullable|string|max:500',
            'ogImage' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
            'twitterCard' => 'nullable|in:summary,summary_large_image,app,player',
            'twitterSite' => 'nullable|string|max:100',
            'twitterCreator' => 'nullable|string|max:100',
            'twitterTitle' => 'nullable|string|max:255',
            'twitterDescription' => 'nullable|string|max:500',
            'twitterImage' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
            'seoSchemaJson' => 'nullable|string',
            'googleAnalyticsId' => 'nullable|string|max:50',
            'googleTagManagerId' => 'nullable|string|max:50',
            'facebookPixelId' => 'nullable|string|max:50',
            'footerSeoEnabled' => 'nullable|boolean',
            'footerSeoHomepageOnly' => 'nullable|boolean',
            'footerSeoTitle' => 'nullable|string|max:255',
            'footerSeoIntro' => 'nullable|string',
            'footerSeoSectionsJson' => 'nullable|string',
            'defaultLatitude' => 'nullable|numeric|between:-90,90',
            'defaultLongitude' => 'nullable|numeric|between:-180,180',
            'enableCountryValidation' => 'nullable|boolean',
            'allowedCountries' => 'required_if:enableCountryValidation,true|array|nullable',
            'returnRefundPolicy' => 'nullable|string',
            'shippingPolicy' => 'nullable|string',
            'privacyPolicy' => 'nullable|string',
            'termsCondition' => 'nullable|string',
            'aboutUs' => 'nullable|string',
            'pwaName' => 'required|string|max:255',
            'pwaDescription' => 'required|string|max:500',
            'pwaLogo192x192' => 'nullable|image|mimes:png,jpeg,webp|max:2048',
            'pwaLogo512x512' => 'nullable|image|mimes:png,jpeg,webp|max:2048',
            'pwaLogo144x144' => 'nullable|image|mimes:png,jpeg,webp|max:2048',
        ];
    }
}

//    |dimensions:width=192,height=192
//    |dimensions:width=512,height=512
//    |dimensions:width=144,height=144
