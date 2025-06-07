<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushController extends Controller
{
    protected function getWebPush()
    {
        return new WebPush([
            'VAPID' => [
                'subject' => env('APP_URL'),
                'publicKey' => env('VAPID_PUBLIC_KEY'),
                'privateKey' => env('VAPID_PRIVATE_KEY'),
                'pemFile' => null,
                'pem' => null,
            ]
        ]);
    }

    public function subscribe(Request $request)
    {
        $user = $request->user();
        
        $user->updatePushSubscription(
            $request->input('endpoint'),
            $request->input('keys.p256dh'),
            $request->input('keys.auth')
        );

        return response()->json(['message' => 'Successfully subscribed']);
    }

    public function send(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user->pushSubscription) {
                return response()->json(['message' => 'Not subscribed'], 400);
            }

            $subscription = Subscription::create([
                'endpoint' => $user->pushSubscription->endpoint,
                'publicKey' => $user->pushSubscription->p256dh_key,
                'authToken' => $user->pushSubscription->auth_token,
            ]);

            $payload = json_encode([
                'title' => 'Test Notification',
                'body' => 'This is a test notification',
                'icon' => '/icon.png',
                'badge' => '/badge.png'
            ]);

            $webPush = $this->getWebPush();
            $report = $webPush->sendOneNotification($subscription, $payload);

            if ($report->isSuccess()) {
                return response()->json(['message' => 'Notification sent successfully']);
            }

            return response()->json(['message' => 'Failed: ' . $report->getReason()], 500);

        } catch (\Exception $e) {
            \Log::error('Push notification error: ' . $e->getMessage());
            return response()->json(['message' => 'Error sending notification'], 500);
        }
    }
}