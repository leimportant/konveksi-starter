<!DOCTYPE html>
<html>
<head>
    <title>New Order Created</title>
</head>
<body>
    <h1>Pesanan Baru #{{ $order->id }} Telah Dibuat</h1>
    <p>Sebuah pesanan baru telah dilakukan dengan detail sebagai berikut:</p>

    <ul>
        <li>Order ID: {{ $order->id }}</li>
        <li>Customer: {{ $order->customer->name ?? 'N/A' }}</li>
        <li>Total Pesanan: IDR. {{ number_format($order->total_amount, 2) }}</li>
        <li>Status: {{ $order->status }}</li>
    </ul>
    <h3>Order Items:</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Qty</th>
                <th>Harga</th>
                <th>Diskon</th>
                <th>Total Harga</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->orderItems as $item)
                <tr>
                    <td>{{ $item->product->name ?? 'N/A' }}</td>
                    <td>{{ $item->qty }}</td>
                    <td>{{ number_format($item->price, 2) }}</td>
                    <td>{{ number_format($item->discount, 2) }}</td>
                    <td>{{ number_format($item->price_final, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p>Terima kasih.</p>

    <p>
        Untuk melihat detail pesanan di aplikasi: <a href="{{ config('app.url') }}/order-history">Lihat Pesanan</a>
    </p>

    <p>
        Untuk mengubah status pesanan:
        <a href="{{ config('app.url') }}/api/orders/{{ $order->id }}/status/approved">Approve</a> |
        <a href="{{ config('app.url') }}/api/orders/{{ $order->id }}/status/rejected">Reject</a>
    </p>
</body>
</html>