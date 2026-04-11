<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingTariff extends Model
{
    protected $fillable = [
        'delivery_partner_id',
        'zone_id',
        'upto_250',
        'upto_500',
        'every_500',
        'per_kg',
        'kg_2',
        'above_5_surface',
        'above_5_air',
        'fuel_surcharge_percent',
        'gst_percent',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'upto_250' => 'decimal:2',
        'upto_500' => 'decimal:2',
        'every_500' => 'decimal:2',
        'per_kg' => 'decimal:2',
        'kg_2' => 'decimal:2',
        'above_5_surface' => 'decimal:2',
        'above_5_air' => 'decimal:2',
        'fuel_surcharge_percent' => 'decimal:2',
        'gst_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function deliveryPartner(): BelongsTo
    {
        return $this->belongsTo(DeliveryPartner::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(PinZone::class, 'zone_id');
    }
}

