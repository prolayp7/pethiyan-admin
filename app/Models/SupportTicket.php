<?php

namespace App\Models;

use App\Enums\SupportTicketStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SupportTicket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ticket_type_id',
        'user_id',
        'subject',
        'slug',
        'email',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => SupportTicketStatusEnum::class,
    ];

    protected static function booted(): void
    {
        static::creating(function ($ticket) {
            if (empty($ticket->slug)) {
                $ticket->slug = Str::slug($ticket->subject) . '-' . Str::random(6);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(SupportTicketType::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id')->orderBy('created_at');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [
            SupportTicketStatusEnum::OPEN,
            SupportTicketStatusEnum::IN_PROGRESS,
            SupportTicketStatusEnum::REOPEN,
            SupportTicketStatusEnum::PENDING_REVIEW,
        ]);
    }
}
