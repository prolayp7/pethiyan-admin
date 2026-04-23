<?php

namespace App\Listeners\Order;

use App\Events\Order\OrderPlaced;
use App\Mail\NewSellerOrderMail;
use App\Mail\OrderPlacedMail;
use App\Services\EmailService;
use Illuminate\Support\Facades\Log;

class SendOrderPlacedEmail
{
    public function __construct(protected EmailService $emailService) {}

    public function handle(OrderPlaced $event): void
    {
        $order = $event->order->load([
            'user',
            'items.product',
            'sellerOrders.seller.user',
            'sellerOrders.items.orderItem.product',
        ]);

        // Email customer
        $customer = $order->user;
        if ($customer?->email) {
            try {
                $this->emailService->send(new OrderPlacedMail($order), $customer->email, $customer->name);
            } catch (\Throwable $e) {
                Log::error('[SendOrderPlacedEmail] Customer email failed: ' . $e->getMessage());
            }
        }

        // Email each seller
        foreach ($order->sellerOrders ?? [] as $sellerOrder) {
            $sellerUser = $sellerOrder->seller?->user;
            if ($sellerUser?->email) {
                try {
                    $this->emailService->send(
                        new NewSellerOrderMail($order, $sellerOrder),
                        $sellerUser->email,
                        $sellerUser->name
                    );
                } catch (\Throwable $e) {
                    Log::error('[SendOrderPlacedEmail] Seller email failed: ' . $e->getMessage());
                }
            }
        }
    }
}
