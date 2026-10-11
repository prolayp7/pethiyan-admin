<?php

use App\Http\Controllers\OrderController;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;

uses(Tests\TestCase::class);

it('formats one dated CA CSV row per item with apportioned discounts and recalculated GST', function () {
    $order = new Order();
    $order->setAttribute('id', 229);
    $order->setAttribute('created_at', '2026-10-11 09:00:00');
    $order->setAttribute('shipping_name', 'Buyer');
    $order->setAttribute('shipping_state', 'Maharashtra');
    $order->setAttribute('shipping_country_code', 'IN');
    $order->setAttribute('supply_type', 'intra');
    $order->setAttribute('promo_code', 'SAVE10');
    $order->setAttribute('promo_discount', 10);
    $order->setAttribute('gift_card_discount', 5);
    $order->setAttribute('total_payable', 293.47);
    $order->setRelation('user', (new User())
        ->setAttribute('gstin', 'GSTIN123')
        ->setAttribute('company_name', 'Buyer Co'));
    $order->setRelation('promoLine', null);
    $order->setRelation('items', collect([
        new OrderItem([
            'title' => 'Corrugated Box',
            'variant_title' => '120 g',
            'hsn_code' => '4819',
            'quantity' => 2,
            'price' => 100,
            'taxable_amount' => 200,
            'gst_rate' => 12,
            'gst_type' => 'intra',
            'cgst_amount' => 12,
            'sgst_amount' => 12,
            'igst_amount' => 0,
            'total_tax_amount' => 24,
        ]),
        new OrderItem([
            'title' => 'Protective Wrap',
            'variant_title' => '100 kg',
            'hsn_code' => '3923',
            'quantity' => 1,
            'price' => 50,
            'taxable_amount' => 50,
            'gst_rate' => 18,
            'gst_type' => 'inter',
            'cgst_amount' => 0,
            'sgst_amount' => 0,
            'igst_amount' => 9,
            'total_tax_amount' => 9,
        ]),
    ]));

    $controller = (new ReflectionClass(OrderController::class))->newInstanceWithoutConstructor();
    $formatRows = new ReflectionMethod(OrderController::class, 'formatCaCsvOrderRows');
    $rows = $formatRows->invoke($controller, $order, ['IN' => 'India']);
    $headers = (new ReflectionClass(OrderController::class))->getConstant('CA_CSV_HEADERS');

    expect(count($headers))->toBe(46)
        ->and(count($rows))->toBe(2)
        ->and(count($rows[0]))->toBe(46)
        ->and($rows[0][0])->toBe('2026101100229')
        ->and($rows[0][1])->toBe('PET2026101100229')
        ->and($rows[0][2])->toBe('2026-10-11')
        ->and($rows[0][11])->toBe('Corrugated Box')
        ->and($rows[0][12])->toBe('120 g')
        ->and($rows[1][11])->toBe('Protective Wrap')
        ->and($rows[1][12])->toBe('100 kg')
        ->and($rows[0][14])->toBe('PCS')
        ->and($rows[0][16])->toBe(100)
        ->and($rows[0][17])->toBe(192.0)
        ->and($rows[1][17])->toBe(48.0)
        ->and($rows[0][18])->toBe(12.0)
        ->and($rows[1][18])->toBe(18.0)
        ->and($rows[0][19])->toBe(0.0)
        ->and($rows[1][19])->toBe(8.64)
        ->and($rows[0][20])->toBe(6)
        ->and($rows[1][20])->toBe(0.0)
        ->and($rows[0][21])->toBe(11.52)
        ->and($rows[1][21])->toBe(0.0)
        ->and($rows[0][22])->toBe(6)
        ->and($rows[1][22])->toBe(0.0)
        ->and($rows[0][23])->toBe(11.52)
        ->and($rows[1][23])->toBe(0.0)
        ->and($rows[0][24])->toBe(0.0)
        ->and($rows[0][25])->toBe(23.04)
        ->and($rows[1][25])->toBe(8.64)
        ->and($rows[0][26])->toBe(215.04)
        ->and($rows[1][26])->toBe(56.64)
        ->and($rows[0][27])->toBe(240.0)
        ->and($rows[0][30])->toBe('')
        ->and($rows[0][31])->toBe('')
        ->and($rows[0][32])->toBe(31.68)
        ->and($rows[0][33])->toBe('SAVE10')
        ->and($rows[0][34])->toBe(8.0)
        ->and($rows[1][34])->toBe(2.0)
        ->and($rows[0][35])->toBe(5)
        ->and($rows[0][36])->toBe(293.47)
        ->and($rows[0][39])->toBe('Maharashtra')
        ->and($rows[0][40])->toBe('Maharashtra')
        ->and($rows[0][41])->toBe('India')
        ->and($rows[0][44])->toBe('No')
        ->and($rows[0][45])->toBe(3.0)
        ->and($rows[1][45])->toBe(3.0);
});
