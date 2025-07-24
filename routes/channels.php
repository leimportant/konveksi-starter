<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('chat.{orderId}', function ($user, $orderId) {
    // You can add authorization logic here to check if the user belongs to this order
    // For now, we'll allow all authenticated users to listen to any chat channel.
    \Illuminate\Support\Facades\Log::info('Channel authorization check', [
        'user_id' => $user->id,
        'order_id' => $orderId,
        'channel' => 'chat.' . $orderId
    ]);
    return true;
});