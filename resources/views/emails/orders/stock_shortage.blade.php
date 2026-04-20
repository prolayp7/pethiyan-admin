<html>
<body>
<p>Admin,</p>
<p>The following items in Order @if($order->order_number) #{{ $order->order_number }} @else {{ $order->id }} @endif exceeded available stock at the time of ordering:</p>
<ul>
@foreach($shortages as $s)
    <li>
        Product: {{ $s['product_title'] ?? 'N/A' }} — Ordered: {{ $s['ordered_qty'] }} — In stock: {{ $s['stock_at_purchase'] }} — Shortage: {{ $s['stock_shortage'] }}
    </li>
@endforeach
</ul>
<p>Please take necessary action.</p>
</body>
</html>
