<?php

namespace App\Listeners\Order;

use App\Events\Order\OrderStatusUpdated;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendOrderStatusSms implements ShouldQueue
{
    public string $queue = 'sms';

    public function __construct(protected SmsService $smsService) {}

    public function handle(OrderStatusUpdated $event): void
    {
        $orderItem = $event->orderItem->loadMissing(['order.user']);
        $customer  = $orderItem->order?->user;

        if (!$customer?->mobile) {
            return;
        }

        $message = $this->buildMessage($customer->name, $orderItem->order_id, $event->newStatus);
        if (!$message) {
            return; // skip non-notable statuses
        }

        $countryCode = $customer->iso_2 ? $this->resolveCountryCode($customer->iso_2) : '+91';
        $mobile      = preg_replace('/\D/', '', $customer->mobile);

        try {
            $this->smsService->sendMessage($mobile, $countryCode, $message);
        } catch (\Throwable $e) {
            Log::error('[SendOrderStatusSms] Failed: ' . $e->getMessage(), [
                'order_item_id' => $orderItem->id,
                'status'        => $event->newStatus,
            ]);
        }
    }

    private function buildMessage(string $name, int $orderId, string $status): ?string
    {
        return match (strtolower($status)) {
            'confirmed', 'accepted'
                => "Hi {$name}, your order item from order #{$orderId} has been confirmed by the seller and is being prepared.",
            'shipped'
                => "Hi {$name}, great news! Your order #{$orderId} has been shipped and is on its way.",
            'assigned'
                => "Hi {$name}, a delivery partner has been assigned to your order #{$orderId}.",
            'delivered'
                => "Hi {$name}, your order #{$orderId} has been delivered. We hope you love it! Thank you for shopping with us.",
            'cancelled'
                => "Hi {$name}, your order item from order #{$orderId} has been cancelled. Any charges will be refunded within 5-7 business days.",
            default => null, // don't SMS for every minor status change
        };
    }

    private function resolveCountryCode(string $iso2): string
    {
        return match (strtoupper($iso2)) {
            'IN' => '+91',
            'US' => '+1',
            'GB' => '+44',
            'AU' => '+61',
            'CA' => '+1',
            'SG' => '+65',
            'AE' => '+971',
            default => '+91',
        };
    }
}
