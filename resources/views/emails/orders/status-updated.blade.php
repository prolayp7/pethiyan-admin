@extends('emails.layout')

@section('title', 'Order Update — ' . ucfirst($newStatus))
@section('header-sub', 'Order Status Update')

@php
    $badgeClass = match(strtolower($newStatus)) {
        'confirmed', 'accepted'  => 'badge-blue',
        'shipped', 'assigned'    => 'badge-orange',
        'delivered'              => 'badge-green',
        'cancelled', 'rejected'  => 'badge-red',
        default                  => 'badge-gray',
    };

    $statusMessage = match(strtolower($newStatus)) {
        'confirmed', 'accepted'  => 'Your order item has been confirmed by the seller and is being prepared.',
        'shipped'                => 'Great news! Your order item is on its way.',
        'assigned'               => 'A delivery partner has been assigned to your order.',
        'delivered'              => 'Your order has been delivered. We hope you love it!',
        'cancelled'              => 'Your order item has been cancelled. If you were charged, a refund will be processed shortly.',
        'rejected'               => 'Unfortunately, your order item was rejected by the seller.',
        default                  => 'Your order status has been updated.',
    };
@endphp

@section('content')
    <h2>Order Update</h2>
    <p>Hi <strong>{{ $orderItem->order?->user?->name ?? 'Valued Customer' }}</strong>,</p>
    <p>{{ $statusMessage }}</p>

    <p>
        <strong>Order ID:</strong> #{{ $orderItem->order_id }}<br>
        <strong>Item:</strong> {{ $orderItem->title ?? $orderItem->product?->name ?? '—' }}<br>
        <strong>New Status:</strong> <span class="status-badge {{ $badgeClass }}">{{ ucfirst($newStatus) }}</span>
    </p>

    @if($orderItem->product)
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th style="text-align:right;">Price</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $orderItem->title ?? $orderItem->product->name }}</td>
                    <td>{{ $orderItem->quantity }}</td>
                    <td style="text-align:right;">₹{{ number_format($orderItem->price * $orderItem->quantity, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <hr class="divider">
    <p style="color:#6b7280; font-size:13px;">If you have any questions about your order, please contact our support team quoting your order ID <strong>#{{ $orderItem->order_id }}</strong>.</p>
@endsection
