<?php

namespace App\Enums;

enum OrderStatusEnum: string
{
    case PENDING = 'pending';
    case PACKED = 'di kemas';
    case ON_PROGRESS = 'on progress';
    case DONE = 'done';
    case CANCEL = 'cancel';
}