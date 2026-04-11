<?php

namespace App\Models;

use App\Enums\Menu\MenuItemTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MenuItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid', 'menu_id', 'parent_id', 'label', 'href', 'target',
        'type', 'icon', 'description', 'accent_color', 'badge',
        'sort_order', 'is_active', 'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata'  => 'array',
        'type'      => MenuItemTypeEnum::class,
    ];

    protected static function booted(): void
    {
        static::creating(function ($item) {
            if (empty($item->uuid)) {
                $item->uuid = (string) Str::uuid();
            }
        });
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort_order');
    }

    public function megaMenuPanels(): HasMany
    {
        return $this->hasMany(MegaMenuPanel::class)->orderBy('sort_order');
    }
}
