<?php

namespace App\Enums;

enum OrderStatusEnum: int
{
    case PENDING = 1;
    case MENUNGGU_PEMBAYARAN = 2;
    case MENUNGGU_KONFIRMASI = 3;
    case ON_PROGRESS = 4;
    case PACKED = 5;
    case DONE = 6;
    case CANCEL = 7;
    case CONFIRM_CANCEL = 8;
    case DIKIRIM = 9;

    public function label(): string
    {
        return match($this) {
            self::PENDING, self::MENUNGGU_PEMBAYARAN => 'Menunggu Pembayaran',
            self::MENUNGGU_KONFIRMASI => 'Menunggu Konfirmasi',
            self::ON_PROGRESS => 'Sedang Diproses',
            self::PACKED => 'Sedang Dikemas',
            self::DONE => 'Selesai',
            self::CANCEL => 'Dibatalkan',
            self::CONFIRM_CANCEL => 'Konfirmasi Pembatalan',
            self::DIKIRIM => 'Sedang dalam perjalanan',
        };
    }
}
