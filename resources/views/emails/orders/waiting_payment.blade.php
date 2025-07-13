<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Menunggu Pembayaran #{{ $order->id }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #212529;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 700px;
            margin: auto;
            background: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #343a40;
        }

        p {
            font-size: 16px;
            line-height: 1.6;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table th,
        table td {
            padding: 12px 15px;
            text-align: left;
            border: 1px solid #dee2e6;
        }

        table th {
            background-color: #f1f3f5;
            color: #495057;
            font-weight: 600;
        }

        .section-title {
            font-size: 18px;
            margin-top: 25px;
            margin-bottom: 10px;
            font-weight: 600;
            color: #212529;
            border-bottom: 2px solid #0d6efd;
            display: inline-block;
            padding-bottom: 4px;
        }

        @media only screen and (max-width: 600px) {

            body,
            .container {
                padding: 15px;
            }

            table th,
            table td {
                padding: 10px;
                font-size: 14px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Halo,</h1>
        <p>Pesanan Anda (ID: <strong>{{ $order->id }}</strong>) telah berhasil dibuat dan sedang menunggu pembayaran.
        </p>
        <p>Silakan lakukan pembayaran sesegera mungkin. Setelah melakukan pembayaran, mohon unggah bukti pembayaran
            melalui aplikasi kami.</p>

        <div class="section-title">Ringkasan Pembayaran</div>
        <table>
            <tr>
                <th>Total Harga</th>
                <td>Rp. {{ number_format($order->total_amount, 2, ',', '.') }}</td>
            </tr>
        </table>


        <div class="section-title">Ringkasan Pesanan</div>

        @foreach ($order->orderItems as $item)
            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                style="border: 1px solid #ddd; border-radius: 6px; margin-bottom: 20px; background-color: #fefefe;">
                <tr>
                    <td width="100" style="padding: 10px; text-align: center; vertical-align: top;">
                        <img src="{{ asset($item->product->image_path) }}" alt="{{ $item->product->name }}"
                            style="width: 80px; height: auto; border-radius: 4px; display: block; object-fit: cover;">
                    </td>
                    <td
                        style="padding: 10px; vertical-align: top; font-family: Arial, sans-serif; font-size: 12px; color: #333;">
                        <strong>{{ $item->product->name }}</strong><br>
                        Ukuran: {{ $item->size_id }} / {{ $item->uom_id }}<br>
                        Jumlah: {{ $item->qty }}<br>
                        Total Harga: Rp {{  number_format($item->price_final * $item->qty, 2, ',', '.') }}<br>
                        Diskon: Rp {{ number_format($item->discount * $item->qty, 2, ',', '.') }}
                    </td>
                </tr>
            </table>
        @endforeach



        <p style="margin-top: 30px;">Terima kasih atas pesanan Anda!</p>
    </div>
</body>

</html>