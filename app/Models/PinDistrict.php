<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PinDistrict extends Model
{
    protected $table = 'districts';

    protected $fillable = [
        'state_id',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function cities(): HasMany
    {
        return $this->hasMany(PinCity::class, 'district_id');
    }

    public function pinServiceAreas(): HasMany
    {
        return $this->hasMany(PinServiceArea::class, 'district_id');
    }
}
