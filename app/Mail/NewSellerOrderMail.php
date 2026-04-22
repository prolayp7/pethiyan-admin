<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\SellerOrder;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewSellerOrderMail extends Mailable
{

    public function __construct(
        public Order       $order,
        public SellerOrder $sellerOrder,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Order Received — #' . $this->order->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.seller-new-order',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
