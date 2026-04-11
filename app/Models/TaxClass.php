<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static create(array $array)
 * @method static count()
 * @method static find($id)
 * @method static updateOrCreate(array $attributes, array $values = [])
 */
class TaxClass extends Model
{
    use SoftDeletes;

    protected $table = 'tax_classes';

    protected $fillable = [
        'title',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * The tax rates that belong to the tax class.
     */
    public function taxRates()
    {
        return $this->belongsToMany(
            TaxRate::class,
            'tax_class_tax_rate',
            'tax_class_id',
            'tax_rate_id'
        )->withTimestamps();
    }
}
