@extends('emails.layout')

@php
    $orderIdentifier = $order->order_number ?: $order->slug ?: $order->id;
@endphp

@section('title', 'Stock Shortage Alert — Order #' . $orderIdentifier)
@section('header-sub', 'Inventory Alert')

@section('content')
    <h2>Stock Shortage Alert</h2>
    <p>Admin,</p>
    <p>The following items in order <strong>#{{ $orderIdentifier }}</strong> exceeded available stock at the time of ordering.</p>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Ordered</th>
                    <th>In Stock</th>
                    <th>Shortage</th>
                </tr>
            </thead>
            <tbody>
                @foreach($shortages as $s)
                    <tr>
                        <td>{{ $s['product_title'] ?? 'N/A' }}</td>
                        <td>{{ $s['ordered_qty'] ?? 0 }}</td>
                        <td>{{ $s['stock_at_purchase'] ?? 0 }}</td>
                        <td>{{ $s['stock_shortage'] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="address-box">
        Please take the necessary action for this order and review stock levels for the affected variants.
    </div>
@endsection
