<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class VideoStorySlide extends Model
{
    protected $fillable = [
        'title',
        'video_path',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = ['video_url'];

    public function getVideoUrlAttribute(): ?string
    {
        if (!$this->video_path) {
            return null;
        }

        if (str_starts_with($this->video_path, 'http')) {
            return $this->video_path;
        }

        return Storage::disk('public')->url($this->video_path);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
