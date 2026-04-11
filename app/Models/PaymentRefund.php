<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRefund extends Model
{
    protected $fillable = [
        'razorpay_refund_id',
        'razorpay_payment_id',
        'order_payment_transaction_id',
        'order_id',
        'amount',
        'currency',
        'status',
        'speed',
        'notes',
        'raw_payload',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'notes'       => 'json',
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
