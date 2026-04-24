<?php

namespace App\Mail;

use App\Enums\SpatieMediaCollectionName;
use App\Models\Order;
use App\Models\SellerOrder;
use App\Services\OrderService;
use App\Services\SettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
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
        if (!OrderService::canCustomerDownloadInvoice($this->order->status, $this->systemSettings)) {
            return [];
        }

        $sellerOrders = SellerOrder::with([
            'order',
            'seller',
            'order.promoLine',
            'items.product',
            'items.orderItem.store',
            'items.variant',
            'items.orderItem',
        ])->whereHas('order', fn($q) => $q->where('id', $this->order->id))->get();

        if ($sellerOrders->isEmpty()) {
            return [];
        }

        foreach ($sellerOrders as $so) {
            if ($so->seller) {
                $so->seller->authorized_signature = $so->seller->getFirstMediaUrl(
                    SpatieMediaCollectionName::AUTHORIZED_SIGNATURE()
                ) ?? null;
            }
        }

        $order    = $sellerOrders->first()->order;
        $pdf      = Pdf::loadView('layouts.order-invoice', [
            'order'          => $order,
            'sellerOrder'    => $sellerOrders,
            'systemSettings' => $this->systemSettings,
        ])->setPaper('a4', 'portrait')
          ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        $filename = 'invoice-' . ($order->invoice_number ?? $order->order_number ?? $order->uuid ?? $order->id) . '.pdf';

        return [
            Attachment::fromData(fn() => $pdf->output(), $filename)
                ->withMime('application/pdf'),
        ];
    }
}
