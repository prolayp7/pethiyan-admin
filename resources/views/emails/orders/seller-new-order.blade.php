<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order Received</title>
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

    $sellerName = $sellerOrder->seller?->user?->name ?? 'Seller';
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
    $shippingLandmark = $order->shipping_landmark ?? $order->billing_landmark ?? '';
    $customerCompanyName = data_get($order, 'shipping_company_name')
        ?: data_get($order, 'billing_company_name')
        ?: data_get($order, 'company_name')
        ?: data_get($order, 'user.company_name');
    $customerGstin = data_get($order, 'customer_gstin')
        ?: data_get($order, 'gstin')
        ?: data_get($order, 'user.gstin');
    $paymentStatus = ucfirst((string) ($order->payment_status ?? 'Pending'));

    $sellerItems = $sellerOrder->items ?? collect();
    $sellerSubtotal = (float) $sellerItems->sum(fn ($item) => (float) ($item->orderItem?->subtotal ?? 0));
    $sellerGst = (float) $sellerItems->sum(fn ($item) => (float) ($item->orderItem?->total_tax_amount ?? 0));
    $sellerOrderCount = (int) (($order->sellerOrders ?? collect())->count());

    $orderSubtotalBase = (float) ($order->total_taxable_amount ?? $order->subtotal ?? $order->sub_total ?? 0);
    $allocationBase = $orderSubtotalBase > 0 ? $orderSubtotalBase : $sellerSubtotal;
    $sellerShare = $allocationBase > 0 ? ($sellerSubtotal / $allocationBase) : 1.0;

    $subtotal = round($sellerSubtotal, 2);
    $gst = round($sellerGst, 2);

    if ($sellerOrderCount <= 1) {
        $delivery = round((float) ($order->delivery_charge ?? 0), 2);
        $discount = round((float) ($order->promo_discount ?? 0), 2);
        $grandTotal = round((float) ($order->final_total ?? $order->grand_total ?? $order->total_payable ?? 0), 2);
    } else {
        $delivery = round((float) ($order->delivery_charge ?? 0) * $sellerShare, 2);
        $discount = round((float) ($order->promo_discount ?? 0) * $sellerShare, 2);
        $grandTotal = round($subtotal + $delivery + $gst - $discount, 2);
    }

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
                        <div class="title" style="padding-top:10px;">{{ $appName }}</div>
                        <div class="sub" style="padding-top:6px;">Seller Notification</div>
                    </td>
                </tr>
                <tr>
                    <td class="mobile-pad" style="padding:24px 28px;">
                        <div class="h2">New Order Received 🎉</div>
                        <div class="p" style="padding-top:10px;">Hi <strong>{{ $sellerName }}</strong>, you have received a new order. Please review and confirm it as soon as possible.</div>

                        <table role="presentation" width="100%" class="meta" cellpadding="0" cellspacing="0" style="margin-top:16px; border-radius:8px;">
                            <tr>
                                <td class="p" style="padding:12px 14px;">
                                    <strong>Order ID:</strong> #{{ $order->order_number ?? $order->slug ?? $order->id }}<br>
                                    <strong>Order Date:</strong> {{ $order->created_at?->format('d M Y, h:i A') }}<br>
                                    <strong>Customer:</strong> {{ $customerName }}<br>
                                    <strong>Payment Status:</strong> {{ $paymentStatus }}
                                </td>
                            </tr>
                        </table>

                        @if($sellerOrder->items && $sellerOrder->items->count())
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:16px; border:1px solid #cdddf0;">
                                <tr>
                                    <td class="th" style="padding:10px 12px; border-bottom:1px solid #cdddf0;">Item</td>
                                    <td class="th" style="padding:10px 12px; border-bottom:1px solid #cdddf0;">Qty</td>
                                    <td class="th" align="right" style="padding:10px 12px; border-bottom:1px solid #cdddf0;">Price</td>
                                </tr>
                                @foreach($sellerOrder->items as $item)
                                    @php
                                        $orderItem = $item->orderItem;
                                        $lineQty = (float) ($orderItem?->quantity ?? 0);
                                        $lineTotal = (float) ($orderItem?->subtotal ?? (($orderItem?->price ?? 0) * $lineQty));
                                    @endphp
                                    <tr>
                                        <td class="td" style="padding:10px 12px; border-bottom:1px solid #cdddf0;">{{ $orderItem?->title ?? $item->product?->title ?? 'Item' }}</td>
                                        <td class="td" style="padding:10px 12px; border-bottom:1px solid #cdddf0;">{{ $orderItem?->quantity ?? '—' }}</td>
                                        <td class="td" align="right" style="padding:10px 12px; border-bottom:1px solid #cdddf0;">{{ $formatMoney($lineTotal) }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        @endif

                        <table role="presentation" width="100%" class="totals" cellpadding="0" cellspacing="0" style="margin-top:16px; border-radius:8px;">
                            <tr>
                                <td class="p" style="padding:10px 14px 4px;">Subtotal</td>
                                <td class="p" align="right" style="padding:10px 14px 4px;">{{ $formatMoney($subtotal) }}</td>
                            </tr>
                            @if($delivery > 0)
                                <tr>
                                    <td class="p" style="padding:4px 14px;">Shipping</td>
                                    <td class="p" align="right" style="padding:4px 14px;">{{ $formatMoney($delivery) }}</td>
                                </tr>
                            @endif
                            @if($discount > 0)
                                <tr>
                                    <td class="p" style="padding:4px 14px;">Discount</td>
                                    <td class="p" align="right" style="padding:4px 14px;">- {{ $formatMoney($discount) }}</td>
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
                                <td class="total" style="padding:10px 14px 12px;">Your Order Total</td>
                                <td class="total" align="right" style="padding:10px 14px 12px;">{{ $formatMoney($grandTotal) }}</td>
                            </tr>
                        </table>

                        <table role="presentation" width="100%" class="address" cellpadding="0" cellspacing="0" style="margin-top:16px; border-radius:8px;">
                            <tr>
                                <td class="p" style="padding:12px 14px;">
                                    <strong>Shipping Address</strong><br>
                                    @if($customerCompanyName)
                                        <strong>Company:</strong> {{ $customerCompanyName }}<br>
                                    @endif
                                    @if($customerGstin)
                                        <strong>GSTIN:</strong> {{ $customerGstin }}<br>
                                    @endif
                                    {{ $shippingName }}@if($shippingAddress1), {{ $shippingAddress1 }}@endif<br>
                                    @if($shippingAddress2){{ $shippingAddress2 }}<br>@endif
                                    @if($shippingLandmark)<strong>Landmark:</strong> {{ $shippingLandmark }}<br>@endif
                                    {{ $shippingCity }}@if($shippingState), {{ $shippingState }}@endif @if($shippingZip)- {{ $shippingZip }}@endif<br>
                                    @if($shippingPhone){{ $shippingPhone }}@endif
                                </td>
                            </tr>
                        </table>

                        <div class="p" style="padding-top:18px;">Please log in to your seller dashboard to confirm or manage this order.</div>
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
