<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Update</title>
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
        .note-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; }
        .tracking-box { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; }
        .footer { background: #f4f8ff; border-top: 1px solid #cdddf0; font-family: Arial, sans-serif; font-size: 12px; color: #7a8fa8; }
        .badge-blue    { background:#dbeafe; color:#1e40af; padding:3px 10px; border-radius:12px; font-size:12px; font-weight:700; }
        .badge-green   { background:#dcfce7; color:#166534; padding:3px 10px; border-radius:12px; font-size:12px; font-weight:700; }
        .badge-orange  { background:#ffedd5; color:#9a3412; padding:3px 10px; border-radius:12px; font-size:12px; font-weight:700; }
        .badge-red     { background:#fee2e2; color:#991b1b; padding:3px 10px; border-radius:12px; font-size:12px; font-weight:700; }
        .badge-gray    { background:#f1f5f9; color:#475569; padding:3px 10px; border-radius:12px; font-size:12px; font-weight:700; }
        .badge-paid    { background:#dcfce7; color:#166534; padding:3px 10px; border-radius:12px; font-size:12px; font-weight:700; }
        .badge-pending { background:#fef9c3; color:#854d0e; padding:3px 10px; border-radius:12px; font-size:12px; font-weight:700; }
        @media only screen and (max-width: 700px) {
            .container { width: 100% !important; }
            .mobile-pad { padding-left: 16px !important; padding-right: 16px !important; }
        }
    </style>
</head>
<body>
@php
    $symbol = $order->currency_symbol ?? ($systemSettings['currencySymbol'] ?? '₹');
    $formatMoney = fn($v) => $symbol . number_format((float)($v ?? 0), 2);

    $customerName = $order->user?->name ?? $order->shipping_name ?? $order->billing_name ?? 'Valued Customer';
    $orderIdentifier = $order->order_number ?: $order->slug ?: $order->id;

    $statusLabels = [
        'awaiting_store_response' => 'Awaiting Store Response',
        'accepted_by_seller'      => 'Order Accepted',
        'preparing'               => 'Order Start Packing',
        'ready_for_pickup'        => 'Order Packing Done',
        'assigned'                => 'Order Ready for Pickup',
        'collected'               => 'Order Collected',
        'out_for_delivery'        => 'Out for Delivery',
        'shipped'                 => 'Shipped',
        'delivered'               => 'Dispatched',
        'cancelled'               => 'Order Cancelled',
        'failed'                  => 'Order Failed',
        'rejected_by_seller'      => 'Rejected',
    ];

    $statusValue = strtolower((string) $order->status);
    $statusDisplayLabel = $statusLabels[$statusValue] ?? \Illuminate\Support\Str::headline((string) $order->status);
    $orderStatusBadge = match(true) {
        in_array($statusValue, ['confirmed', 'accepted', 'accepted_by_seller']) => 'badge-blue',
        in_array($statusValue, ['preparing', 'ready_for_pickup', 'assigned', 'collected', 'out_for_delivery', 'shipped']) => 'badge-orange',
        in_array($statusValue, ['delivered', 'completed']) => 'badge-green',
        in_array($statusValue, ['cancelled', 'rejected', 'failed']) => 'badge-red',
        default => 'badge-gray',
    };

    $paymentStatusValue = strtolower((string) $order->payment_status);
    $paymentStatusBadge = match($paymentStatusValue) {
        'paid', 'success', 'completed' => 'badge-paid',
        'pending', 'cod' => 'badge-pending',
        'failed', 'cancelled' => 'badge-red',
        default => 'badge-gray',
    };

    $trackingCode = trim((string)($order->tracking_code ?? ''));
    $adminNote = trim((string)($order->admin_note ?? ''));

    $subtotal   = $order->total_taxable_amount ?? $order->subtotal ?? $order->sub_total ?? 0;
    $delivery   = $order->delivery_charge ?? 0;
    $discount   = $order->promo_discount ?? 0;
    $gst        = $order->total_gst ?? 0;
    $grandTotal = $order->final_total ?? $order->grand_total ?? $order->total_payable ?? $order->total ?? 0;

    $shippingName    = $order->shipping_name ?? $order->billing_name ?? $customerName;
    $shippingAddr1   = $order->shipping_address_1 ?? '';
    $shippingAddr2   = $order->shipping_address_2 ?? '';
    $shippingCity    = $order->shipping_city ?? '';
    $shippingState   = $order->shipping_state ?? '';
    $shippingZip     = $order->shipping_zip ?? '';
    $shippingPhone   = $order->shipping_phone ?? $order->billing_phone ?? '';

    $appName = $systemSettings['appName'] ?? config('app.name', 'Pethiyan');
    $logoUrl = !empty($systemSettings['logo']) ? $systemSettings['logo'] : asset('logos/hyper-local-logo.png');
    if (!str_starts_with($logoUrl, 'http://') && !str_starts_with($logoUrl, 'https://') && !str_starts_with($logoUrl, 'data:')) {
        $logoUrl = url(ltrim($logoUrl, '/'));
    }
    $logoPath = public_path('logos/hyper-local-logo.png');
    if (!empty($systemSettings['logo'])) {
        $parsedPath = parse_url($systemSettings['logo'], PHP_URL_PATH);
        if (is_string($parsedPath) && str_starts_with($parsedPath, '/storage/')) {
            $candidate = storage_path('app/public/' . ltrim(substr($parsedPath, strlen('/storage/')), '/'));
            if (is_file($candidate)) { $logoPath = $candidate; }
        } elseif (is_string($systemSettings['logo']) && str_starts_with($systemSettings['logo'], '/')) {
            $candidate = public_path(ltrim($systemSettings['logo'], '/'));
            if (is_file($candidate)) { $logoPath = $candidate; }
        }
    }
@endphp
<table role="presentation" class="outer" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" style="padding:24px 10px;">
            <table role="presentation" class="container" width="680" cellpadding="0" cellspacing="0" style="border-radius:12px; overflow:hidden;">

                {{-- Header --}}
                <tr>
                    <td class="header mobile-pad" align="center" style="padding:24px 28px;">
                        <img src="{{ (isset($message) && is_file($logoPath)) ? $message->embed($logoPath) : $logoUrl }}"
                             alt="{{ $appName }}" width="84" height="84"
                             style="display:block; border:0; background:#ffffff; border-radius:12px; padding:6px; object-fit:contain;">
                        <div class="title" style="padding-top:10px;">Order Update</div>
                        <div class="sub" style="padding-top:6px;">Your order details have been updated by our team.</div>
                    </td>
                </tr>

                <tr>
                    <td class="mobile-pad" style="padding:24px 28px;">

                        <div class="h2">Hi, {{ $customerName }}</div>
                        <div class="p" style="padding-top:8px;">
                            Your order <strong>#{{ $orderIdentifier }}</strong> has been updated.
                            Please review the latest details below.
                        </div>

                        {{-- Order Summary --}}
                        <table role="presentation" width="100%" class="meta" cellpadding="0" cellspacing="0"
                               style="margin-top:16px; border-radius:8px;">
                            <tr>
                                <td class="p" style="padding:14px 16px; line-height:28px;">
                                    <strong>Order Number</strong>: #{{ $orderIdentifier }}<br>
                                    <strong>Order Date</strong>: {{ $order->created_at?->format('d M Y, h:i A') }}<br>
                                    <strong>Order Status</strong>:&nbsp;
                                        <span class="{{ $orderStatusBadge }}">
                                            {{ $statusDisplayLabel }}
                                        </span><br>
                                    <strong>Payment Status</strong>:&nbsp;
                                        <span class="{{ $paymentStatusBadge }}">
                                            {{ \Illuminate\Support\Str::headline((string) $order->payment_status) }}
                                        </span><br>
                                    <strong>Payment Method</strong>: {{ \Illuminate\Support\Str::headline((string)($order->payment_method ?? 'N/A')) }}
                                </td>
                            </tr>
                        </table>

                        {{-- Tracking Code --}}
                        @if($trackingCode)
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                               style="margin-top:12px;">
                            <tr>
                                <td class="tracking-box p" style="padding:12px 16px;">
                                    📦 <strong>Tracking Code:</strong>&nbsp;{{ $trackingCode }}
                                </td>
                            </tr>
                        </table>
                        @endif

                        {{-- Admin Note --}}
                        @if($adminNote)
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                               style="margin-top:12px;">
                            <tr>
                                <td class="note-box p" style="padding:12px 16px;">
                                    📝 <strong>Note from our team:</strong><br>
                                    <span style="margin-top:4px; display:block;">{{ $adminNote }}</span>
                                </td>
                            </tr>
                        </table>
                        @endif

                        {{-- Order Items --}}
                        @if($order->items && $order->items->count())
                        <div class="p" style="margin-top:20px; margin-bottom:6px; font-weight:700; color:#0f2f5f;">
                            Order Items
                        </div>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                               style="border:1px solid #cdddf0; border-radius:8px; overflow:hidden;">
                            <tr>
                                <td class="th" style="padding:10px 12px; border-bottom:1px solid #cdddf0;">Item</td>
                                <td class="th" style="padding:10px 12px; border-bottom:1px solid #cdddf0;" align="center">Qty</td>
                                <td class="th" style="padding:10px 12px; border-bottom:1px solid #cdddf0;" align="right">Price</td>
                            </tr>
                            @foreach($order->items as $item)
                            <tr>
                                <td class="td" style="padding:10px 12px; border-bottom:1px solid #e8f0fb;">
                                    {{ $item->title ?? $item->product?->title ?? 'Item' }}
                                    @if(!empty($item->variant_title))
                                        <br><span style="font-size:11px; color:#6b8aad;">{{ $item->variant_title }}</span>
                                    @endif
                                </td>
                                <td class="td" style="padding:10px 12px; border-bottom:1px solid #e8f0fb;" align="center">
                                    {{ $item->quantity }}
                                </td>
                                <td class="td" style="padding:10px 12px; border-bottom:1px solid #e8f0fb;" align="right">
                                    {{ $formatMoney(($item->price ?? 0) * ($item->quantity ?? 0)) }}
                                </td>
                            </tr>
                            @endforeach
                        </table>
                        @endif

                        {{-- Order Totals --}}
                        <table role="presentation" width="100%" class="totals" cellpadding="0" cellspacing="0"
                               style="margin-top:12px; border-radius:8px;">
                            <tr>
                                <td class="p" style="padding:10px 14px 4px;">Subtotal</td>
                                <td class="p" align="right" style="padding:10px 14px 4px;">{{ $formatMoney($subtotal) }}</td>
                            </tr>
                            <tr>
                                <td class="p" style="padding:4px 14px;">Shipping</td>
                                <td class="p" align="right" style="padding:4px 14px;">
                                    {{ $delivery > 0 ? $formatMoney($delivery) : 'Free' }}
                                </td>
                            </tr>
                            @if($discount > 0)
                            <tr>
                                <td class="p" style="padding:4px 14px;">Discount</td>
                                <td class="p" align="right" style="padding:4px 14px;">− {{ $formatMoney($discount) }}</td>
                            </tr>
                            @endif
                            @if($gst > 0)
                            <tr>
                                <td class="p" style="padding:4px 14px;">GST</td>
                                <td class="p" align="right" style="padding:4px 14px;">{{ $formatMoney($gst) }}</td>
                            </tr>
                            @endif
                            <tr><td colspan="2" style="border-top:1px solid #c3e6d0; padding:0;"></td></tr>
                            <tr>
                                <td class="total" style="padding:10px 14px 12px;">Grand Total</td>
                                <td class="total" align="right" style="padding:10px 14px 12px;">{{ $formatMoney($grandTotal) }}</td>
                            </tr>
                        </table>

                        {{-- Shipping Address --}}
                        <table role="presentation" width="100%" class="address" cellpadding="0" cellspacing="0"
                               style="margin-top:12px; border-radius:8px;">
                            <tr>
                                <td class="p" style="padding:12px 16px; line-height:22px;">
                                    <strong>Delivery Address</strong><br>
                                    {{ $shippingName }}@if($shippingAddr1), {{ $shippingAddr1 }}@endif<br>
                                    @if($shippingAddr2){{ $shippingAddr2 }}<br>@endif
                                    {{ $shippingCity }}@if($shippingState), {{ $shippingState }}@endif
                                    @if($shippingZip) — {{ $shippingZip }}@endif<br>
                                    @if($shippingPhone)📞 {{ $shippingPhone }}@endif
                                </td>
                            </tr>
                        </table>

                        <div class="p" style="margin-top:20px; color:#6b8aad; font-size:13px;">
                            For any queries, please contact our support team and quote your order number
                            <strong>#{{ $orderIdentifier }}</strong>.
                        </div>

                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td class="footer" align="center" style="padding:16px;">
                        Copyright {{ date('Y') }} {{ $appName }}. All rights reserved.
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
