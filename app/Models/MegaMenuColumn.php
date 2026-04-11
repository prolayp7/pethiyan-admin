<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MegaMenuColumn extends Model
{
    protected $fillable = ['panel_id', 'heading', 'sort_order'];

    public function panel(): BelongsTo
    {
        return $this->belongsTo(MegaMenuPanel::class, 'panel_id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(MegaMenuLink::class, 'column_id')->orderBy('sort_order');
    }
}
