<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PinCity extends Model
{
    protected $table = 'cities';

    protected $fillable = [
        'state_id',
        'district_id',
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

    public function district(): BelongsTo
    {
        return $this->belongsTo(PinDistrict::class, 'district_id');
    }

    public function pinServiceAreas(): HasMany
    {
        return $this->hasMany(PinServiceArea::class, 'city_id');
    }
}
