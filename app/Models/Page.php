<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Page extends Model implements HasMedia
{
    use InteractsWithMedia;
    protected $fillable = [
        'slug',
        'title',
        'content',
        'content_blocks',
        'meta_title',
        'meta_description',
        'status',
        'system_page',
    ];

    protected $casts = [
        'content_blocks' => 'array',
    ];
}
