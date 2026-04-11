<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class State extends Model
{
    protected $fillable = [
        'country_id',
        'name',
        'state_code',
        'gst_code',
        'is_ut',
    ];

    protected $casts = [
        'is_ut' => 'boolean',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
