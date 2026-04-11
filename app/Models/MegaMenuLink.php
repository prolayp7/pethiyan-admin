<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MegaMenuLink extends Model
{
    protected $fillable = ['column_id', 'label', 'href', 'target', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    public function column(): BelongsTo
    {
        return $this->belongsTo(MegaMenuColumn::class, 'column_id');
    }
}
