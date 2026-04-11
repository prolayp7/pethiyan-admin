<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class GiftCard extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'seller_id',
        'title',
        'barcode',
        'start_time',
        'end_time',
        'minimum_order_amount',
        'discount',
        'used',
    ];

    protected $casts = [
        'minimum_order_amount' => 'decimal:2',
        'discount'             => 'decimal:2',
        'start_time'           => 'datetime',
        'end_time'             => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($card) {
            if (empty($card->barcode)) {
                $card->barcode = strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));
            }
        });
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function isActive(): bool
    {
        $now = now();
        return !$this->used
            && ($this->start_time === null || $this->start_time->lte($now))
            && ($this->end_time   === null || $this->end_time->gte($now));
    }

    public function isUsed(): bool
    {
        return (bool) $this->used;
    }
}
