<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderManagementHistory extends Model
{
    protected $fillable = [
        'order_id',
        'admin_user_id',
        'previous_status',
        'new_status',
        'previous_payment_status',
        'new_payment_status',
        'tracking_code',
        'admin_note',
        'changed_fields',
    ];

    protected $casts = [
        'changed_fields' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
