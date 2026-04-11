<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(mixed $validated)
 * @method static count()
 * @method static find($id)
 * @method static updateOrCreate(array $attributes, array $values = [])
 */
class TaxRate extends Model
{
    protected $table = 'tax_rates';

    protected $fillable = [
        'title',
        'rate',
        'gst_slab',
        'cgst_rate',
        'sgst_rate',
        'igst_rate',
        'description',
        'is_gst',
        'is_active',
    ];

    protected $casts = [
        'rate'      => 'float',
        'cgst_rate' => 'float',
        'sgst_rate' => 'float',
        'igst_rate' => 'float',
        'is_gst'    => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * The tax classes that belong to the tax rate.
     */
    public function taxClasses()
    {
        return $this->belongsToMany(
            TaxClass::class,
            'tax_class_tax_rate',
            'tax_rate_id',
            'tax_class_id'
        )->withTimestamps();
    }
}
