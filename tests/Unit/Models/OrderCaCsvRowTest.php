<?php

use App\Http\Controllers\OrderController;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;

uses(Tests\TestCase::class);

it('formats CA CSV rows with corrected invoice numbers and stored intra/inter-state GST', function () {
    $order = new Order();
    $order->setAttribute('id', 229);
    $order->setAttribute('created_at', '2026-10-11 09:00:00');
    $order->setAttribute('shipping_name', 'Buyer');
    $order->setAttribute('shipping_country_code', 'IN');
    $order->setAttribute('supply_type', 'intra');
    $order->setAttribute('promo_code', 'SAVE10');
    $order->setAttribute('promo_discount', 10);
    $order->setAttribute('total_payable', 293);
    $order->setRelation('user', (new User())->setAttribute('gstin', 'GSTIN123'));
    $order->setRelation('promoLine', null);
    $order->setRelation('items', collect([
        new OrderItem([
            'title' => "Corrugated Box\n120 g",
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
            'title' => "Protective Wrap\n100 kg",
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
    $formatRow = new ReflectionMethod(OrderController::class, 'formatCaCsvOrderRow');
    $row = $formatRow->invoke($controller, $order, ['IN' => 'India']);
    $headers = (new ReflectionClass(OrderController::class))->getConstant('CA_CSV_HEADERS');

    expect(count($headers))->toBe(39)
        ->and(count($row))->toBe(39)
        ->and($row[0])->toBe('2026101100229')
        ->and($row[1])->toBe('PET2026101100229')
        ->and($row[11])->toBe('Corrugated Box; Protective Wrap')
        ->and($row[13])->toBe('PCS; PCS')
        ->and($row[17])->toBe('12; 18')
        ->and($row[18])->toBe('0; 9')
        ->and($row[19])->toBe('6; 0')
        ->and($row[20])->toBe('12; 0')
        ->and($row[21])->toBe('24; 9')
        ->and($row[22])->toBe('224; 59')
        ->and($row[27])->toBe('SAVE10')
        ->and($row[34])->toBe('India')
        ->and($row[37])->toBe('No')
        ->and($row[38])->toBe(3.0);
});
