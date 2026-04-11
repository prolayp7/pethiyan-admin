<?php

namespace App\Types\Settings;

use App\Interfaces\SettingInterface;
use App\Traits\SettingTrait;

class SeoAdvancedSettingType implements SettingInterface
{
    use SettingTrait;

    /**
     * Extra paths to disallow in robots.txt.
     * Each entry is a path string, e.g. "/my-page/".
     * These are merged with the frontend's hardcoded transactional disallow list.
     *
     * @var string[]
     */
    public array $robotsDisallowRules = [];

    /**
     * Admin-defined custom URLs to include in the sitemap.
     * Each entry: {url: string, priority: string, changeFreq: string}
     *
     * @var array<int, array{url: string, priority: string, changeFreq: string}>
     */
    public array $sitemapCustomUrls = [];

    /**
     * Exact URL paths (e.g. "/products/old-slug") to exclude from the sitemap.
     *
     * @var string[]
     */
    public array $sitemapExcludeUrls = [];

    protected static function getValidationRules(): array
    {
        return [
            'robotsDisallowRules'            => 'nullable|array',
            'robotsDisallowRules.*'          => 'nullable|string|max:255',
            'sitemapCustomUrls'              => 'nullable|array',
            'sitemapCustomUrls.*.url'        => 'nullable|string|max:500',
            'sitemapCustomUrls.*.priority'   => 'nullable|string|max:5',
            'sitemapCustomUrls.*.changeFreq' => 'nullable|string|max:20',
            'sitemapExcludeUrls'             => 'nullable|array',
            'sitemapExcludeUrls.*'           => 'nullable|string|max:255',
        ];
    }
}
