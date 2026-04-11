<?php

namespace App\Http\Resources\Setting;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeoAdvancedSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'variable' => $this->variable,
            'value' => [
                'robotsDisallowRules' => $this->value['robotsDisallowRules'] ?? [],
                'sitemapCustomUrls'   => $this->value['sitemapCustomUrls']   ?? [],
                'sitemapExcludeUrls'  => $this->value['sitemapExcludeUrls']  ?? [],
            ],
        ];
    }
}
