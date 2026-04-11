<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Menu extends Model
{
    use SoftDeletes;

    protected $fillable = ['uuid', 'name', 'slug', 'location', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function ($menu) {
            if (empty($menu->uuid)) {
                $menu->uuid = (string) Str::uuid();
            }
            if (empty($menu->slug)) {
                $menu->slug = Str::slug($menu->name);
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->whereNull('parent_id')->orderBy('sort_order');
    }

    public function allItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order');
    }
}
