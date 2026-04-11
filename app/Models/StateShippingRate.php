<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $data)
 * @method static find(int $id)
 * @method static where(string $col, mixed $val)
 * @method static updateOrCreate(array $conditions, array $values)
 */
class StateShippingRate extends Model
{
    protected $fillable = [
        'state_name',
        'state_code',
        'base_rate',
        'per_kg_rate',
        'free_shipping_above',
        'estimated_days_min',
        'estimated_days_max',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'base_rate'           => 'decimal:2',
        'per_kg_rate'         => 'decimal:2',
        'free_shipping_above' => 'decimal:2',
        'estimated_days_min'  => 'integer',
        'estimated_days_max'  => 'integer',
        'is_active'           => 'boolean',
    ];

    /**
     * Calculate shipping fee for the given order total and weight.
     */
    public function calculateFee(float $orderTotal, float $weightKg = 0): float
    {
        if ($this->free_shipping_above !== null && $orderTotal >= (float)$this->free_shipping_above) {
            return 0.0;
        }

        return (float)$this->base_rate + ((float)$this->per_kg_rate * $weightKg);
    }

    /**
     * Find the active rate for a state code. Returns null if not found.
     */
    public static function findForState(string $stateCode): ?static
    {
        return static::where('state_code', strtoupper($stateCode))
            ->where('is_active', true)
            ->first();
    }
}
