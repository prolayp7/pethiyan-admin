<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\SettingService;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Http\Request;

class OrderPaymentConfirmedMail extends Mailable
{
    public array $systemSettings = [];

    public function __construct(public Order $order)
    {
        $settingResource = app(SettingService::class)->getSettingByVariable('system');
        $this->systemSettings = $settingResource?->toArray(new Request())['value'] ?? [];
    }

    public function envelope(): Envelope
    {
        $order = $this->order->fresh() ?? $this->order;
        $date = $order->created_at?->format('Ymd') ?? now()->format('Ymd');
        $formattedNumber = 'PET' . $date . str_pad((string) $order->id, 5, '0', STR_PAD_LEFT);

        return new Envelope(
            subject: 'Payment Confirmed — Order Accepted — #' . $formattedNumber,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.payment-confirmed',
            with: ['systemSettings' => $this->systemSettings],
        );
    }
}
