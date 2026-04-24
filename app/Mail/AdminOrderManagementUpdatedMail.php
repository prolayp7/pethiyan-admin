<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Str;

class AdminOrderManagementUpdatedMail extends Mailable
{
    public array $systemSettings = [];

    public function __construct(
        public Order $order,
        public string $previousStatus,
        public string $previousPaymentStatus,
    ) {
        $settingResource = app(SettingService::class)->getSettingByVariable('system');
        $this->systemSettings = $settingResource?->toArray(new Request())['value'] ?? [];

        $this->order->loadMissing(['user', 'items']);
    }

    public function envelope(): Envelope
    {
        $orderIdentifier = $this->order->order_number ?: $this->order->slug ?: $this->order->id;
        $statusChanged = $this->previousStatus !== (string) $this->order->status;
        $paymentChanged = $this->previousPaymentStatus !== (string) $this->order->payment_status;

        $subjectSuffix = $statusChanged
            ? 'status is now ' . Str::headline((string) $this->order->status)
            : ($paymentChanged
                ? 'payment is now ' . Str::headline((string) $this->order->payment_status)
                : 'has been updated');

        return new Envelope(
            subject: 'Order Update — #' . $orderIdentifier . ' ' . $subjectSuffix,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.admin-management-updated',
            with: ['systemSettings' => $this->systemSettings],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
