<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\SellerOrder;
use App\Services\SettingService;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Http\Request;

class NewSellerOrderMail extends Mailable
{
    public array $systemSettings = [];

    public function __construct(
        public Order       $order,
        public SellerOrder $sellerOrder,
    ) {
        $settingResource = app(SettingService::class)->getSettingByVariable('system');
        $this->systemSettings = $settingResource?->toArray(new Request())['value'] ?? [];
    }

    public function envelope(): Envelope
    {
        $order = $this->order->fresh() ?? $this->order;
        $formattedNumber = $order->order_number ?? ('PET' . ($order->created_at?->format('Ymd') ?? now()->format('Ymd')) . str_pad((string) $order->id, 5, '0', STR_PAD_LEFT));

        return new Envelope(
            subject: 'New Order Received — #' . $formattedNumber,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.seller-new-order',
            with: ['systemSettings' => $this->systemSettings],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
