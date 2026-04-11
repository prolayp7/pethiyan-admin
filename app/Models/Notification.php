<?php

namespace App\Models;

use App\Enums\NotificationTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @method static create(array $data)
 * @method static find($id)
 * @method static where(string $column, mixed $value)
 */
class Notification extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'admin_user_id',
        'store_id',
        'order_id',
        'type',
        'sent_to',
        'title',
        'message',
        'is_read',
        'metadata',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $notification) {
            if (empty($notification->getAttribute('id'))) {
                $notification->setAttribute('id', (string) Str::uuid());
            }
        });
    }

    public function setTypeAttribute($value): void
    {
        if ($value instanceof NotificationTypeEnum) {
            $this->attributes['type'] = $value->value;
            return;
        }

        $raw = trim((string) $value);
        $this->attributes['type'] = $this->normalizeType($raw);
    }

    public function getTypeAttribute($value): string
    {
        return $this->normalizeType((string) $value);
    }

    /**
     * Get the user that owns the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin user that owns the notification.
     */
    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }

    /**
     * Get the store associated with the notification.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the order associated with the notification.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scope to get unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to get read notifications.
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope to filter by notification type.
     */
    public function scopeOfType($query, NotificationTypeEnum $type)
    {
        return $query->where('type', $type->value);
    }

    /**
     * Scope to filter by sent_to.
     */
    public function scopeSentTo($query, string $sentTo)
    {
        return $query->where('sent_to', $sentTo);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(): bool
    {
        return $this->update(['is_read' => true]);
    }

    /**
     * Mark notification as unread.
     */
    public function markAsUnread(): bool
    {
        return $this->update(['is_read' => false]);
    }

    private function normalizeType(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return NotificationTypeEnum::GENERAL->value;
        }

        if (str_contains($value, '\\')) {
            $short = strtolower(class_basename($value));
            return match ($short) {
                'productcreated', 'productupdated', 'productstatusupdated' => NotificationTypeEnum::PRODUCT->value,
                'newordernotification', 'orderstatusupdated' => NotificationTypeEnum::ORDER->value,
                default => NotificationTypeEnum::SYSTEM->value,
            };
        }

        $normalized = strtolower($value);
        return NotificationTypeEnum::fromString($normalized)->value;
    }
}
