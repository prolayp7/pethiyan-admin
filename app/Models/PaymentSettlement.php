<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSettlement extends Model
{
    protected $fillable = [
        'razorpay_settlement_id',
        'razorpay_payment_id',
        'order_payment_transaction_id',
        'order_id',
        'amount',
        'currency',
        'status',
        'event_name',
        'settlement_reference',
        'utr',
        'settled_at',
        'raw_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'settled_at' => 'datetime',
        'raw_payload' => 'json',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(OrderPaymentTransaction::class, 'order_payment_transaction_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}