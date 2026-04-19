<?php

namespace App\Mail;

use App\Enums\SpatieMediaCollectionName;
use App\Models\Order;
use App\Models\SellerOrder;
use App\Services\SettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;

class OrderPlacedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        $orderIdentifier = $this->order->order_number ?: ($this->order->slug ?: $this->order->id);

        return new Envelope(
            subject: 'Order Confirmed — #' . $orderIdentifier,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.placed',
        );
    }

    public function attachments(): array
    {
        $sellerOrders = SellerOrder::with(
            'order',
            'seller',
            'order.promoLine',
            'items.product',
            'items.orderItem.store',
            'items.variant',
            'items.orderItem'
        )
            ->whereHas('order', fn($q) => $q->where('id', $this->order->id))
            ->get();

        if ($sellerOrders->isEmpty()) {
            return [];
        }

        foreach ($sellerOrders as $sellerOrder) {
            if ($sellerOrder->seller) {
                $sellerOrder->seller->authorized_signature = $sellerOrder->seller->getFirstMediaUrl(
                    SpatieMediaCollectionName::AUTHORIZED_SIGNATURE()
                ) ?? null;
            }
        }

        $order = $sellerOrders->first()->order;
        $systemSettingResource = app(SettingService::class)->getSettingByVariable('system');
        $systemSettings = $systemSettingResource?->toArray(new Request())['value'] ?? [];

        $pdf = Pdf::loadView('layouts.order-invoice', [
            'order'          => $order,
            'sellerOrder'    => $sellerOrders,
            'systemSettings' => $systemSettings,
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        $filename = 'invoice-' . ($order->invoice_number ?? $order->order_number ?? $order->uuid ?? $order->id) . '.pdf';

        return [
            Attachment::fromData(
                fn() => $pdf->output(),
                $filename
            )->withMime('application/pdf'),
        ];
    }
}
