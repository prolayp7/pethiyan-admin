<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentWebhookLog extends Model
{
    protected $fillable = [
        'gateway',
        'event_name',
        'delivery_id',
        'order_payment_transaction_id',
        'order_id',
        'status',
        'signature_valid',
        'http_status',
        'message',
        'request_headers',
        'raw_payload',
        'processed_at',
    ];

    protected $casts = [
        'signature_valid' => 'boolean',
        'http_status' => 'integer',
        'request_headers' => 'json',
        'raw_payload' => 'json',
        'processed_at' => 'datetime',
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