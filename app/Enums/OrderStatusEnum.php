<?php

namespace App\Enums;

enum OrderStatusEnum: int
{
    case PENDING = 1;
    case MENUNGGU_PEMBAYARAN = 2; // Alias for PENDING

    case MENUNGGU_KONFIRMASI = 3;
    case ON_PROGRESS = 4;
    case PACKED = 5;
    case DONE = 6;
    case CANCEL = 7;

    case CONFIRM_CANCEL = 8;
}