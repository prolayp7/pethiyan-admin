<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminStockShortageMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public array $shortages)
    {
    }

    public function envelope(): Envelope
    {
        $orderIdentifier = $this->order->order_number ?: $this->order->slug ?: $this->order->id;
        return new Envelope(subject: 'Stock Shortage Alert — Order #' . $orderIdentifier);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.orders.stock_shortage');
    }

    public function attachments(): array
    {
        return [];
    }
}
