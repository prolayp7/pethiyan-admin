<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartSaveForLaterItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_id',
        'product_variant_id',
        'store_id',
        'quantity',
        'save_for_later',
    ];

    protected $casts = [
        'save_for_later' => 'boolean',
    ];

    public function setSaveForLaterAttribute($value): void
    {
        $this->attributes['save_for_later'] = $value ? '1' : '0';
    }

    public function getSaveForLaterAttribute($value): bool
    {
        return $value === '1';
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}

