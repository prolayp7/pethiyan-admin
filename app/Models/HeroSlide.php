<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class HeroSlide extends Model
{
    protected $fillable = [
        'image',
        'eyebrow',
        'heading',
        'description',
        'primary_cta_label',
        'primary_cta_href',
        'secondary_cta_label',
        'secondary_cta_href',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }
        // Absolute URLs (existing banners already hosted) returned as-is
        if (str_starts_with($this->image, 'http')) {
            return $this->image;
        }
        return Storage::disk('public')->url($this->image);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
