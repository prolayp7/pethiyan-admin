@extends('emails.layout')

@section('title', 'Order Update — ' . \Illuminate\Support\Str::headline((string) $order->status))
@section('header-sub', 'Order Management Update')

@php
    $statusValue = strtolower((string) $order->status);
    $deliveryStatusChanged = (string) $previousStatus !== (string) $order->status;
    $paymentStatusChanged = (string) $previousPaymentStatus !== (string) $order->payment_status;
    $badgeClass = match($statusValue) {
        'confirmed', 'accepted' => 'badge-blue',
        'shipped', 'assigned', 'out_for_delivery' => 'badge-orange',
        'delivered', 'completed' => 'badge-green',
        'cancelled', 'rejected', 'failed' => 'badge-red',
        default => 'badge-gray',
    };

    $orderIdentifier = $order->order_number ?: $order->slug ?: $order->id;
@endphp

@section('content')
    <h2>Order Update</h2>
    <p>Hi <strong>{{ $order->user?->name ?? 'Valued Customer' }}</strong>,</p>
    <p>Your order has been updated by our team. Please find the latest order management details below.</p>

    <div class="totals">
        <div class="row">
            <span>Order Number</span>
            <strong>#{{ $orderIdentifier }}</strong>
        </div>
        <div class="row">
            <span>Previous Delivery Status</span>
            <strong>{{ \Illuminate\Support\Str::headline((string) $previousStatus) }}</strong>
        </div>
        <div class="row">
            <span>Current Delivery Status</span>
            <strong><span class="status-badge {{ $badgeClass }}">{{ \Illuminate\Support\Str::headline((string) $order->status) }}</span></strong>
        </div>
        <div class="row">
            <span>Previous Payment Status</span>
            <strong>{{ \Illuminate\Support\Str::headline((string) $previousPaymentStatus) }}</strong>
        </div>
        <div class="row">
            <span>Payment Status</span>
            <strong>{{ \Illuminate\Support\Str::headline((string) $order->payment_status) }}</strong>
        </div>
        <div class="row">
            <span>Tracking Code</span>
            <strong>{{ $order->tracking_code ?: 'Not provided' }}</strong>
        </div>
    </div>

    @if($deliveryStatusChanged || $paymentStatusChanged)
        <p>
            @if($deliveryStatusChanged)
                Delivery status changed to <strong>{{ \Illuminate\Support\Str::headline((string) $order->status) }}</strong>.
            @endif
            @if($deliveryStatusChanged && $paymentStatusChanged)
                <br>
            @endif
            @if($paymentStatusChanged)
                Payment status changed to <strong>{{ \Illuminate\Support\Str::headline((string) $order->payment_status) }}</strong>.
            @endif
        </p>
    @endif

    <div class="address-box">
        <strong>Admin Comment</strong><br>
        {{ $order->admin_note ?: 'No additional comment was added.' }}
    </div>

    <p style="color:#6b7280; font-size:13px;">If you need help, please contact support and mention your order number <strong>#{{ $orderIdentifier }}</strong>.</p>
@endsection