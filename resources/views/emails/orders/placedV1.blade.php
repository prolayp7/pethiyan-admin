@extends('emails.layout')

@section('title', 'Order Confirmed — #' . $order->id)
@section('header-sub', 'Order Confirmation')

@section('content')
    <h2>Thank you for your order! 🎉</h2>
    <p>Hi <strong>{{ $order->user?->name ?? 'Valued Customer' }}</strong>, your order has been placed successfully and is being reviewed by our sellers.</p>

    <p>
        <strong>Order ID:</strong> #{{ $order->id }}<br>
        <strong>Order Date:</strong> {{ $order->created_at?->format('d M Y, h:i A') }}<br>
        <strong>Payment Status:</strong> <span class="status-badge badge-blue">{{ ucfirst($order->payment_status ?? 'Pending') }}</span>
    </p>

    {{-- Items table --}}
    @if($order->items && $order->items->count())
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
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->title ?? $item->product?->name ?? '—' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td style="text-align:right;">₹{{ number_format($item->price * $item->quantity, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Totals --}}
    <div class="totals">
        <div class="row"><span>Subtotal</span><span>₹{{ number_format($order->sub_total ?? 0, 2) }}</span></div>
        @if(($order->delivery_charge ?? 0) > 0)
        <div class="row"><span>Delivery</span><span>₹{{ number_format($order->delivery_charge, 2) }}</span></div>
        @endif
        @if(($order->promo_discount ?? 0) > 0)
        <div class="row"><span>Discount</span><span>- ₹{{ number_format($order->promo_discount, 2) }}</span></div>
        @endif
        @if(($order->total_gst ?? 0) > 0)
        <div class="row"><span>GST</span><span>₹{{ number_format($order->total_gst, 2) }}</span></div>
        @endif
        <div class="row grand"><span>Total Payable</span><span>₹{{ number_format($order->grand_total ?? $order->total ?? 0, 2) }}</span></div>
    </div>

    {{-- Delivery address --}}
    @if($order->address ?? null)
    <p><strong>Delivering to:</strong></p>
    <div class="address-box">
        {{ $order->address['name'] ?? '' }}<br>
        {{ $order->address['address'] ?? '' }}, {{ $order->address['city'] ?? '' }}<br>
        {{ $order->address['state'] ?? '' }} — {{ $order->address['zipcode'] ?? '' }}<br>
        {{ $order->address['mobile'] ?? '' }}
    </div>
    @endif

    <hr class="divider">
    <p style="color:#6b7280; font-size:13px;">We'll send you another email once each item ships. If you have any questions, please contact our support team.</p>
@endsection
