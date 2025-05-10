<?php

return [
    'VAPID' => [
        'subject' => 'mailto:you@example.com',
        'publicKey' => env('VAPID_PUBLIC_KEY'),
        'privateKey' => env('VAPID_PRIVATE_KEY'),
    ],
];
