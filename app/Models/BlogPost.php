<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BlogPost extends Model
{
    protected $fillable = [
        'blog_category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'author_name',
        'author_role',
        'author_bio',
        'author_avatar',
        'tags',
        'metadata',
        'reading_time',
        'is_featured',
        'is_active',
        'published_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
        'reading_time' => 'integer',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected $appends = ['featured_image_url', 'author_avatar_url'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->resolveStorageUrl($this->featured_image);
    }

    public function getAuthorAvatarUrlAttribute(): ?string
    {
        return $this->resolveStorageUrl($this->author_avatar);
    }

    public function scopePublished($query)
    {
        return $query
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    private function resolveStorageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
