@include('emails.orders.placed', ['order' => $order, 'systemSettings' => $systemSettings ?? []])
