<?php

namespace App\Listeners\Order;

use App\Enums\SettingTypeEnum;
use App\Events\Order\OrderPaymentConfirmed;
use App\Mail\OrderPaymentConfirmedMail;
use App\Services\EmailService;
use App\Services\SettingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendOrderPaymentConfirmedEmail implements ShouldQueue
{
    public string $queue = 'emails';

    public function __construct(protected EmailService $emailService) {}

    public function handle(OrderPaymentConfirmed $event): void
    {
        $order = $event->order->load(['user', 'items.product']);

        $customer = $order->user;
        if ($customer?->email) {
            try {
                $this->emailService->send(new OrderPaymentConfirmedMail($order), $customer->email, $customer->name);
            } catch (\Throwable $e) {
                Log::error('[SendOrderPaymentConfirmedEmail] Customer email failed: ' . $e->getMessage(), [
                    'order_id' => $order->id,
                ]);
            }
        }

        $systemSettings     = app(SettingService::class)->getSettingByVariable(SettingTypeEnum::SYSTEM())?->value ?? [];
        $sellerSupportEmail = trim($systemSettings['sellerSupportEmail'] ?? '');

        if ($sellerSupportEmail) {
            try {
                $this->emailService->send(new OrderPaymentConfirmedMail($order), $sellerSupportEmail, 'Seller Support');
            } catch (\Throwable $e) {
                Log::error('[SendOrderPaymentConfirmedEmail] Seller support email failed: ' . $e->getMessage(), [
                    'order_id' => $order->id,
                ]);
            }
        }
    }
}
