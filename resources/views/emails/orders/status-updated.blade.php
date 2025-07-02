<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Status Pesanan Diperbarui</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; background-color: #f9f9f9; padding: 20px;">
    <div style="background-color: #ffffff; padding: 20px; border-radius: 8px; max-width: 600px; margin: auto;">
        <h2 style="color: #333333;">Status Pesanan #{{ $order->id }} Telah Diperbarui</h2>

        <p>Halo {{ $order->customer->name ?? 'Pelanggan' }},</p>

        @php
            try {
                $statusLabel = \App\Enums\OrderStatusEnum::from($order->status)->label();
            } catch (\ValueError $e) {
                $statusLabel = 'Tidak diketahui';
            }
        @endphp

        <p>Status terbaru pesanan Anda adalah: <strong>{{ $statusLabel }}</strong>.</p>

        <p>Silakan masuk ke akun Anda untuk melihat detail pesanan secara lengkap.</p>

        <p>Terima kasih telah berbelanja di <strong>Aninka Fashion</strong>!</p>

        <p>Salam hangat,<br>Tim Aninka Fashion</p>
    </div>
</body>
</html>
