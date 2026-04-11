<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MegaMenuPanel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid', 'menu_item_id', 'label', 'href',
        'accent_color', 'image_path', 'tagline',
        'sort_order', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function ($panel) {
            if (empty($panel->uuid)) {
                $panel->uuid = (string) Str::uuid();
            }
        });
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }

    public function columns(): HasMany
    {
        return $this->hasMany(MegaMenuColumn::class, 'panel_id')->orderBy('sort_order');
    }
}
