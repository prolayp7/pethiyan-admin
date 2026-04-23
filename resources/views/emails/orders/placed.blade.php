<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed</title>
    <style>
        body { margin: 0; padding: 0; background: #f0f4f8; }
        table { border-collapse: collapse; }
        .outer { width: 100%; background: #f0f4f8; }
        .container { width: 680px; max-width: 680px; background: #ffffff; border: 1px solid #cdddf0; }
        .header { background: linear-gradient(135deg, #0f2f5f 0%, #1f4f8a 45%, #27a567 100%); }
        .title { font-family: Arial, sans-serif; font-size: 24px; font-weight: 700; color: #ffffff; }
        .sub { font-family: Arial, sans-serif; font-size: 14px; color: rgba(255,255,255,0.80); }
        .h2 { font-family: Arial, sans-serif; font-size: 22px; color: #0f2f5f; font-weight: 700; }
        .p { font-family: Arial, sans-serif; font-size: 14px; color: #3e5677; line-height: 22px; }
        .meta { background: #f4f8ff; border: 1px solid #cdddf0; }
        .badge { font-family: Arial, sans-serif; font-size: 12px; color: #1a6b3a; background: #dff5eb; padding: 4px 10px; border-radius: 12px; font-weight: 700; }
        .th { background: #eef4fb; color: #445e80; font-family: Arial, sans-serif; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; }
        .td { font-family: Arial, sans-serif; font-size: 13px; color: #1f3d60; }
        .totals { background: #f6fbf8; border: 1px solid #c3e6d0; }
        .total { font-family: Arial, sans-serif; font-size: 18px; color: #0f2f5f; font-weight: 700; }
        .address { background: #f4fbf7; border: 1px solid #c3e6d0; }
        .footer { background: #f4f8ff; border-top: 1px solid #cdddf0; font-family: Arial, sans-serif; font-size: 12px; color: #7a8fa8; }
        @media only screen and (max-width: 700px) {
            .container { width: 100% !important; }
            .mobile-pad { padding-left: 16px !important; padding-right: 16px !important; }
        }
    </style>
</head>
<body>
@php
    $symbol = $order->currency_symbol ?? ($systemSettings['currencySymbol'] ?? 'Rs ');
    $formatMoney = function ($value) use ($symbol) {
        return $symbol . number_format((float)($value ?? 0), 2);
    };

    $customerName = $order->user?->name
        ?? $order->shipping_name
        ?? $order->billing_name
        ?? 'Valued Customer';

    $shippingName = $order->shipping_name ?? $order->billing_name ?? $customerName;
    $shippingAddress1 = $order->shipping_address_1 ?? '';
    $shippingAddress2 = $order->shipping_address_2 ?? '';
    $shippingCity = $order->shipping_city ?? '';
    $shippingState = $order->shipping_state ?? '';
    $shippingZip = $order->shipping_zip ?? '';
    $shippingPhone = $order->shipping_phone ?? $order->billing_phone ?? '';

    $subtotal = $order->total_taxable_amount ?? $order->subtotal ?? $order->sub_total ?? 0;
    $delivery = $order->delivery_charge ?? 0;
    $promoDiscount = $order->promo_discount ?? 0;
    $gst = $order->total_gst ?? 0;
    $grandTotal = $order->final_total ?? $order->grand_total ?? $order->total_payable ?? $order->total ?? 0;

    $paymentStatus = ucfirst((string)($order->payment_status ?? 'Pending'));
    $appName = $systemSettings['appName'] ?? config('app.name', 'Pethiyan');
    $logoUrl = !empty($systemSettings['logo']) ? $systemSettings['logo'] : asset('logos/hyper-local-logo.png');
    if (!str_starts_with($logoUrl, 'http://') && !str_starts_with($logoUrl, 'https://') && !str_starts_with($logoUrl, 'data:')) {
        $logoUrl = url(ltrim($logoUrl, '/'));
    }

    $logoPath = public_path('logos/hyper-local-logo.png');
    if (!empty($systemSettings['logo'])) {
        $parsedPath = parse_url($systemSettings['logo'], PHP_URL_PATH);
        if (is_string($parsedPath) && str_starts_with($parsedPath, '/storage/')) {
            $candidatePath = storage_path('app/public/' . ltrim(substr($parsedPath, strlen('/storage/')), '/'));
            if (is_file($candidatePath)) {
                $logoPath = $candidatePath;
            }
        } elseif (is_string($systemSettings['logo']) && str_starts_with($systemSettings['logo'], '/')) {
            $candidatePath = public_path(ltrim($systemSettings['logo'], '/'));
            if (is_file($candidatePath)) {
                $logoPath = $candidatePath;
            }
        }
    }
@endphp
<table role="presentation" class="outer" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" style="padding:24px 10px;">
            <table role="presentation" class="container" width="680" cellpadding="0" cellspacing="0" style="border-radius:12px; overflow:hidden;">
                <tr>
                    <td class="header mobile-pad" align="center" style="padding:24px 28px;">
                        <img src="{{ (isset($message) && is_file($logoPath)) ? $message->embed($logoPath) : $logoUrl }}" alt="{{ $appName }}" width="84" height="84" style="display:block; border:0; background:#ffffff; border-radius:12px; padding:6px; object-fit:contain;">
                        <div class="title" style="padding-top:10px;">Order Confirmed</div>
                        <div class="sub" style="padding-top:6px;">Your purchase request has been successfully recorded.</div>
                    </td>
                </tr>
                <tr>
                    <td class="mobile-pad" style="padding:24px 28px;">
                        <div class="h2">Thank you, {{ $customerName }}</div>
                        <div class="p" style="padding-top:10px;">Your order has been placed and is now under processing. You will receive separate updates for shipment and delivery milestones.</div>

                        <table role="presentation" width="100%" class="meta" cellpadding="0" cellspacing="0" style="margin-top:16px; border-radius:8px;">
                            <tr>
                                <td class="p" style="padding:12px 14px;">
                                    <strong>Order ID:</strong> #{{ $order->order_number ?? $order->slug ?? $order->id }}<br>
                                    <strong>Order Date:</strong> {{ $order->created_at?->format('d M Y, h:i A') }}<br>
                                    <strong>Payment Status:</strong> <span class="badge">{{ $paymentStatus }}</span>
                                </td>
                            </tr>
                        </table>

                        @if($order->items && $order->items->count())
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:16px; border:1px solid #cdddf0;">
                                <tr>
                                    <td class="th" style="padding:10px 12px; border-bottom:1px solid #cdddf0;">Item</td>
                                    <td class="th" style="padding:10px 12px; border-bottom:1px solid #cdddf0;">Qty</td>
                                    <td class="th" align="right" style="padding:10px 12px; border-bottom:1px solid #cdddf0;">Price</td>
                                </tr>
                                @foreach($order->items as $item)
                                    <tr>
                                        <td class="td" style="padding:10px 12px; border-bottom:1px solid #cdddf0;">{{ $item->title ?? $item->product?->title ?? 'Item' }}</td>
                                        <td class="td" style="padding:10px 12px; border-bottom:1px solid #cdddf0;">{{ $item->quantity }}</td>
                                        <td class="td" align="right" style="padding:10px 12px; border-bottom:1px solid #cdddf0;">{{ $formatMoney(($item->price ?? 0) * ($item->quantity ?? 0)) }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        @endif

                        <table role="presentation" width="100%" class="totals" cellpadding="0" cellspacing="0" style="margin-top:16px; border-radius:8px;">
                            <tr>
                                <td class="p" style="padding:10px 14px 4px;">Subtotal</td>
                                <td class="p" align="right" style="padding:10px 14px 4px;">{{ $formatMoney($subtotal) }}</td>
                            </tr>
                            <tr>
                                <td class="p" style="padding:4px 14px;">Shipping</td>
                                <td class="p" align="right" style="padding:4px 14px;">{{ $delivery > 0 ? $formatMoney($delivery) : 'Free' }}</td>
                            </tr>
                            @if($promoDiscount > 0)
                                <tr>
                                    <td class="p" style="padding:4px 14px;">Discount</td>
                                    <td class="p" align="right" style="padding:4px 14px;">- {{ $formatMoney($promoDiscount) }}</td>
                                </tr>
                            @endif
                            @if($gst > 0)
                                <tr>
                                    <td class="p" style="padding:4px 14px;">GST</td>
                                    <td class="p" align="right" style="padding:4px 14px;">{{ $formatMoney($gst) }}</td>
                                </tr>
                            @endif
                            <tr><td colspan="2" style="border-top:1px solid #c3e6d0;"></td></tr>
                            <tr>
                                <td class="total" style="padding:10px 14px 12px;">Total Payable</td>
                                <td class="total" align="right" style="padding:10px 14px 12px;">{{ $formatMoney($grandTotal) }}</td>
                            </tr>
                        </table>

                        <table role="presentation" width="100%" class="address" cellpadding="0" cellspacing="0" style="margin-top:16px; border-radius:8px;">
                            <tr>
                                <td class="p" style="padding:12px 14px;">
                                    <strong>Delivery Address</strong><br>
                                    {{ $shippingName }}@if($shippingAddress1), {{ $shippingAddress1 }}@endif<br>
                                    @if($shippingAddress2){{ $shippingAddress2 }}<br>@endif
                                    {{ $shippingCity }}@if($shippingState), {{ $shippingState }}@endif @if($shippingZip)- {{ $shippingZip }}@endif<br>
                                    @if($shippingPhone){{ $shippingPhone }}@endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="footer" align="center" style="padding:16px;">
                        Copyright {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
