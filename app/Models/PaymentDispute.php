<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentDispute extends Model
{
    protected $fillable = [
        'razorpay_dispute_id',
        'razorpay_payment_id',
        'order_payment_transaction_id',
        'order_id',
        'amount',
        'currency',
        'status',
        'reason_code',
        'reason_description',
        'respond_by',
        'raw_payload',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'respond_by'  => 'datetime',
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
