<?php

namespace App\Listeners\Order;

use App\Events\Order\OrderPlaced;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendOrderPlacedSms implements ShouldQueue
{
    public string $queue = 'sms';
    public bool $afterCommit = true;

    public function __construct(protected SmsService $smsService) {}

    public function handle(OrderPlaced $event): void
    {
        $order    = $event->order;
        $customer = $order->user;

        if (!$customer?->mobile) {
            return;
        }

        $countryCode = $customer->iso_2 ? $this->resolveCountryCode($customer->iso_2) : '+91';
        $mobile      = preg_replace('/\D/', '', $customer->mobile);

        $total   = number_format((float)($order->grand_total ?? $order->total ?? 0), 2);
        $message = "Hi {$customer->name}, your order #{$order->id} has been placed successfully. Total: ₹{$total}. Thank you for shopping with us!";

        try {
            $this->smsService->sendMessage($mobile, $countryCode, $message);
        } catch (\Throwable $e) {
            Log::error('[SendOrderPlacedSms] Failed: ' . $e->getMessage(), ['order_id' => $order->id]);
        }
    }

    private function resolveCountryCode(string $iso2): string
    {
        // Common country dial codes — expand as needed
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
