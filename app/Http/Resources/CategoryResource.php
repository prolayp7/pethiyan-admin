<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'sort_order' => (int) ($this->sort_order ?? 0),
            'title' => $this->title,
            'slug' => $this->slug,
            'image' => $this->image ?? '',
            'banner' => $this->banner ?? '',
            'icon' => $this->icon ?? '',
            'active_icon' => $this->active_icon ?? '',
            'background_type' => $this->background_type?->value ?? null,
            'background_color' => $this->background_color ?? '',
            'background_image' => $this->background_image ?? '',
            'font_color' => $this->font_color ?? '',
            'parent_id' => $this->parent_id,
            'parent_slug' => $this->parent->slug ?? null,
            'description' => $this->description,
            'status' => $this->status,
            'requires_approval' => $this->requires_approval,
            'metadata' => $this->metadata,
            'is_indexable' => $this->is_indexable ?? true,
            'seo_title' => $this->metadata['seo_title'] ?? null,
            'seo_description' => $this->metadata['seo_description'] ?? null,
            'seo_keywords' => $this->metadata['seo_keywords'] ?? null,
            'og_title' => $this->metadata['og_title'] ?? null,
            'og_description' => $this->metadata['og_description'] ?? null,
            'og_image' => $this->resolveMetadataImageUrl('og_image'),
            'twitter_title' => $this->metadata['twitter_title'] ?? null,
            'twitter_description' => $this->metadata['twitter_description'] ?? null,
            'twitter_card' => $this->metadata['twitter_card'] ?? null,
            'twitter_image' => $this->resolveMetadataImageUrl('twitter_image'),
            'schema_mode' => $this->metadata['schema_mode'] ?? 'auto',
            'schema_json_ld' => $this->metadata['schema_json_ld'] ?? null,
            'subcategory_count' => $this->children_count ?? 0,
            'product_count' => $this->products_count ?? 0,
        ];
    }

    private function resolveMetadataImageUrl(string $key): ?string
    {
        $path = $this->metadata[$key] ?? null;

        return !empty($path) ? url('storage/' . ltrim($path, '/')) : null;
    }
}
