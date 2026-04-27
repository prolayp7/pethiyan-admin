<?php

namespace App\Listeners\Order;

use App\Enums\SettingTypeEnum;
use App\Events\Order\OrderPlaced;
use App\Mail\NewSellerOrderMail;
use App\Mail\OrderPlacedMail;
use App\Services\EmailService;
use App\Services\SettingService;
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

        $systemSettings    = app(SettingService::class)->getSettingByVariable(SettingTypeEnum::SYSTEM())?->value ?? [];
        $sellerSupportEmail = trim($systemSettings['sellerSupportEmail'] ?? '');

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

        // Email seller support address (configured in system settings)
        if ($sellerSupportEmail) {
            try {
                $this->emailService->send(new OrderPlacedMail($order), $sellerSupportEmail, 'Seller Support');
            } catch (\Throwable $e) {
                Log::error('[SendOrderPlacedEmail] Seller support email failed: ' . $e->getMessage());
            }
        }
    }
}
