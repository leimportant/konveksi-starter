<!DOCTYPE html>
<html>
<head>
    <title>Order Status Updated</title>
</head>
<body>
    <h1>Order #{{ $order->id }} Status Updated</h1>
    <p>Dear {{ $order->customer->name ?? 'Customer' }},</p>
    <p>Your order status has been updated to: <strong>{{ $order->status }}</strong>.</p>
    <p>You can view your order details by logging into your account.</p>
    <p>Thank you for your business!</p>
</body>
</html>