@extends('emails.layout')

@section('title', 'New Order Received — #' . $order->id)
@section('header-sub', 'Seller Notification')

@section('content')
    <h2>New Order Received 🎉</h2>
    <p>Hi <strong>{{ $sellerOrder->seller?->user?->name ?? 'Seller' }}</strong>, you have received a new order. Please review and confirm it as soon as possible.</p>

    <p>
        <strong>Order ID:</strong> #{{ $order->id }}<br>
        <strong>Order Date:</strong> {{ $order->created_at?->format('d M Y, h:i A') }}<br>
        <strong>Customer:</strong> {{ $order->user?->name ?? '—' }}
    </p>

    {{-- Items for this seller only --}}
    @if($sellerOrder->items && $sellerOrder->items->count())
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
                @foreach($sellerOrder->items as $item)
                <tr>
                    <td>{{ $item->orderItem?->title ?? $item->product?->name ?? '—' }}</td>
                    <td>{{ $item->orderItem?->quantity ?? '—' }}</td>
                    <td style="text-align:right;">₹{{ number_format(($item->orderItem?->price ?? 0) * ($item->orderItem?->quantity ?? 1), 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Seller payout total --}}
    <div class="totals">
        <div class="row grand">
            <span>Your Order Total</span>
            <span>₹{{ number_format($sellerOrder->price ?? 0, 2) }}</span>
        </div>
    </div>

    {{-- Delivery address --}}
    @if($order->address ?? null)
    <p><strong>Ship to:</strong></p>
    <div class="address-box">
        {{ $order->address['name'] ?? '' }}<br>
        {{ $order->address['address'] ?? '' }}, {{ $order->address['city'] ?? '' }}<br>
        {{ $order->address['state'] ?? '' }} — {{ $order->address['zipcode'] ?? '' }}<br>
        {{ $order->address['mobile'] ?? '' }}
    </div>
    @endif

    <hr class="divider">
    <p style="color:#6b7280; font-size:13px;">Please log in to your seller dashboard to confirm or manage this order.</p>
@endsection
