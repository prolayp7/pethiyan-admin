<?php

namespace App\Listeners\Order;

use App\Events\Order\OrderStatusUpdated;
use App\Mail\OrderStatusUpdatedMail;
use App\Services\EmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendOrderStatusEmail implements ShouldQueue
{
    public string $queue = 'emails';

    public function __construct(protected EmailService $emailService) {}

    public function handle(OrderStatusUpdated $event): void
    {
        $orderItem = $event->orderItem->load(['order.user', 'product']);
        $customer  = $orderItem->order?->user;

        if (!$customer?->email) {
            return;
        }

        try {
            $this->emailService->send(
                new OrderStatusUpdatedMail($orderItem, $event->newStatus),
                $customer->email,
                $customer->name
            );
        } catch (\Throwable $e) {
            Log::error('[SendOrderStatusEmail] Failed: ' . $e->getMessage(), [
                'order_item_id' => $orderItem->id,
                'new_status'    => $event->newStatus,
            ]);
        }
    }
}
