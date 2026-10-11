<?php

use App\Models\Order;

uses(Tests\TestCase::class);

it('generates invoice numbers without a prefix while keeping order numbers prefixed', function () {
    $order = new Order();
    $order->setAttribute('id', 229);
    $order->setAttribute('created_at', '2026-10-11 09:00:00');

    expect($order->invoice_number)->toBe('2026101100229')
        ->and($order->order_number)->toBe('PET2026101100229');
});
