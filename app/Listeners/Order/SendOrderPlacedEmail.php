<?php

namespace App\Listeners\Order;

use App\Events\Order\OrderPlaced;
use App\Mail\NewSellerOrderMail;
use App\Mail\OrderPlacedMail;
use App\Services\EmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendOrderPlacedEmail implements ShouldQueue
{
    public string $queue = 'emails';

    public function __construct(protected EmailService $emailService) {}

    public function handle(OrderPlaced $event): void
    {
        $order = $event->order->load([
            'user',
            'items.product',
            'sellerOrders.seller.user',
            'sellerOrders.items.orderItem.product',
        ]);

        Log::info('[SendOrderPlacedEmail] Listener started', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'customer_email' => $order->user?->email,
            'seller_order_count' => $order->sellerOrders?->count() ?? 0,
        ]);

        // Email customer
        $customer = $order->user;
        if ($customer?->email) {
            try {
                Log::info('[SendOrderPlacedEmail] Attempting customer email', [
                    'order_id' => $order->id,
                    'email' => $customer->email,
                    'name' => $customer->name,
                ]);

                $sent = $this->emailService->send(new OrderPlacedMail($order), $customer->email, $customer->name);

                Log::info('[SendOrderPlacedEmail] Customer email result', [
                    'order_id' => $order->id,
                    'email' => $customer->email,
                    'sent' => $sent,
                ]);
            } catch (\Throwable $e) {
                Log::error('[SendOrderPlacedEmail] Customer email failed: ' . $e->getMessage(), [
                    'order_id' => $order->id,
                    'email' => $customer->email,
                ]);
            }
        } else {
            Log::warning('[SendOrderPlacedEmail] Skipping customer email: missing customer email', [
                'order_id' => $order->id,
                'user_id' => $customer?->id,
            ]);
        }

        // Email each seller
        foreach ($order->sellerOrders ?? [] as $sellerOrder) {
            $sellerUser = $sellerOrder->seller?->user;
            if ($sellerUser?->email) {
                try {
                    Log::info('[SendOrderPlacedEmail] Attempting seller email', [
                        'order_id' => $order->id,
                        'seller_order_id' => $sellerOrder->id,
                        'seller_id' => $sellerOrder->seller?->id,
                        'seller_user_id' => $sellerUser->id,
                        'email' => $sellerUser->email,
                        'item_count' => $sellerOrder->items?->count() ?? 0,
                    ]);

                    $sent = $this->emailService->send(
                        new NewSellerOrderMail($order, $sellerOrder),
                        $sellerUser->email,
                        $sellerUser->name
                    );

                    Log::info('[SendOrderPlacedEmail] Seller email result', [
                        'order_id' => $order->id,
                        'seller_order_id' => $sellerOrder->id,
                        'email' => $sellerUser->email,
                        'sent' => $sent,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('[SendOrderPlacedEmail] Seller email failed: ' . $e->getMessage(), [
                        'order_id' => $order->id,
                        'seller_order_id' => $sellerOrder->id,
                        'email' => $sellerUser->email,
                    ]);
                }
            } else {
                Log::warning('[SendOrderPlacedEmail] Skipping seller email: missing seller user email', [
                    'order_id' => $order->id,
                    'seller_order_id' => $sellerOrder->id,
                    'seller_id' => $sellerOrder->seller?->id,
                    'seller_user_id' => $sellerUser?->id,
                ]);
            }
        }

        Log::info('[SendOrderPlacedEmail] Listener finished', [
            'order_id' => $order->id,
        ]);
    }
}
