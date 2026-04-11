<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use HasFactory;

    protected $fillable = [
        'stars',
        'quote',
        'name',
        'title',
        'avatar_image',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'stars' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->avatar_image) {
            return null;
        }

        if (str_starts_with($this->avatar_image, 'http')) {
            return $this->avatar_image;
        }

        return rtrim(env('APP_URL', 'http://localhost'), '/') . '/storage/' . $this->avatar_image;
    }
}
