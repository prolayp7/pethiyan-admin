<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PinServiceArea extends Model
{
    protected $fillable = [
        'pincode',
        'state',
        'state_id',
        'district',
        'district_id',
        'city',
        'city_id',
        'zone',
        'zone_id',
        'zone1',
        'region_id',
        'delivery_time',
        'is_serviceable',
    ];

    protected $casts = [
        'is_serviceable' => 'boolean',
    ];

    /**
     * Check if a pincode is serviceable and return its details.
     */
    public static function check(string $pincode): ?self
    {
        return static::where('pincode', $pincode)
            ->where('is_serviceable', true)
            ->first();
    }

    public function zoneRef(): BelongsTo
    {
        return $this->belongsTo(PinZone::class, 'zone_id');
    }

    public function regionRef(): BelongsTo
    {
        return $this->belongsTo(PinRegion::class, 'region_id');
    }

    public function stateRef(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function districtRef(): BelongsTo
    {
        return $this->belongsTo(PinDistrict::class, 'district_id');
    }

    public function cityRef(): BelongsTo
    {
        return $this->belongsTo(PinCity::class, 'city_id');
    }
}
