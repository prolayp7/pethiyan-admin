<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\SettingService;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Http\Request;

class OrderPlacedMail extends Mailable
{
    public array $systemSettings = [];

    public function __construct(public Order $order)
    {
        $settingResource = app(SettingService::class)->getSettingByVariable('system');
        $this->systemSettings = $settingResource?->toArray(new Request())['value'] ?? [];
    }

    public function envelope(): Envelope
    {
        // Ensure we have the freshest order data (id/created_at) before building subject
        $order = $this->order->fresh() ?? $this->order;
        // Always prefer the formatted order number (consistent display)
        $date = $order->created_at?->format('Ymd') ?? now()->format('Ymd');
        $formattedNumber = 'PET' . $date . str_pad((string) $order->id, 5, '0', STR_PAD_LEFT);

        return new Envelope(
            subject: 'Order Confirmed — #' . $formattedNumber,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.placed',
            with: ['systemSettings' => $this->systemSettings],
        );
    }
}
