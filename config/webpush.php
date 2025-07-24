<?php

return [
    'VAPID' => [
        'subject' => env('MAIL_FROM_ADDRESS', 'mailto:medinacreativo8@gmail.com'),
        'publicKey' => env('VAPID_PUBLIC_KEY', env('VITE_VAPID_PUBLIC_KEY')),
        'privateKey' => env('VAPID_PRIVATE_KEY', env('VITE_VAPID_PRIVATE_KEY')),
        'expiry' => 43200, // 12 hours in seconds
    ],
    
    // Database Model for storing subscriptions
    'model' => \App\Models\PushSubscription::class,
    
    // Auto delete notification after send
    'delete_after_send' => true,
    
    // Time in seconds to retry failed notification
    'retry_after' => 3600, // 1 hour
    
    // Config for the GuzzleHttp client
    'client_options' => [
        'timeout' => 10, // seconds
        'connect_timeout' => 10, // seconds
    ],
];
