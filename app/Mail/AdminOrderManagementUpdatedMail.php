<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class AdminOrderManagementUpdatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $previousStatus,
        public string $previousPaymentStatus,
    ) {}

    public function envelope(): Envelope
    {
        $orderIdentifier = $this->order->order_number ?: $this->order->slug ?: $this->order->id;
        $statusChanged = $this->previousStatus !== (string) $this->order->status;
        $paymentChanged = $this->previousPaymentStatus !== (string) $this->order->payment_status;

        $subjectSuffix = $statusChanged
            ? 'delivery status is now ' . Str::headline((string) $this->order->status)
            : ($paymentChanged
                ? 'payment status is now ' . Str::headline((string) $this->order->payment_status)
                : 'details were updated');

        return new Envelope(
            subject: 'Order Update — #' . $orderIdentifier . ' ' . $subjectSuffix,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.admin-management-updated',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}