<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PinZone extends Model
{
    protected $fillable = [
        'code',
        'name',
        'default_delivery_time',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function pinServiceAreas(): HasMany
    {
        return $this->hasMany(PinServiceArea::class, 'zone_id');
    }

    public function shippingTariffs(): HasMany
    {
        return $this->hasMany(ShippingTariff::class, 'zone_id');
    }
}
