<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shipping Address {{ $order->order_number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 24px;
            font-size: 14px;
        }

        .sheet {
            border: 1px solid #d1d5db;
            border-radius: 12px;
            padding: 24px;
        }

        .title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .subtitle {
            color: #6b7280;
            margin-bottom: 20px;
        }

        .label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6b7280;
            margin-bottom: 6px;
        }

        .value {
            font-size: 16px;
            line-height: 1.7;
        }

        .block {
            margin-bottom: 20px;
        }

        .meta {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
            color: #4b5563;
            font-size: 12px;
        }
    </style>
</head>
<body>
<div class="sheet">
    <div class="title">Shipping Address</div>
    <div class="subtitle">Order {{ $order->order_number }}</div>

    <div class="block">
        <div class="label">Recipient</div>
        <div class="value">
            {{ $order->shipping_name }}<br>
            @if(!empty($order->shipping_company_name) || !empty($order->user?->company_name))
                Company: {{ $order->shipping_company_name ?: $order->user?->company_name }}<br>
            @endif
            {{ $order->shipping_address_1 }}<br>
            @if($order->shipping_address_2)
                {{ $order->shipping_address_2 }}<br>
            @endif
            @if($order->shipping_landmark)
                Landmark: {{ $order->shipping_landmark }}<br>
            @endif
            {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}<br>
            {{ $order->shipping_country }}<br>
            Phone: {{ $order->shipping_phone }}
        </div>
    </div>

    <div class="block">
        <div class="label">Customer Details</div>
        <div class="value">
            Email: {{ $order->email }}<br>
            GSTIN: {{ $order->user?->gstin ?: 'N/A' }}
        </div>
    </div>

    <div class="meta">
        Generated on {{ now()->format('M d, Y h:i A') }}
    </div>
</div>
</body>
</html>