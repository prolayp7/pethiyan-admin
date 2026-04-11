<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class BlogCategory extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'cover_image',
        'sort_order',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected $appends = ['cover_image_url'];

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class)->latest('published_at');
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        if (!$this->cover_image) {
            return null;
        }

        if (str_starts_with($this->cover_image, 'http')) {
            return $this->cover_image;
        }

        return Storage::disk('public')->url($this->cover_image);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
