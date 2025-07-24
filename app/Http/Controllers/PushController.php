<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Notifications\PushNotification;
use Illuminate\Support\Facades\Log;
use NotificationChannels\WebPush\Models\PushSubscription;

class PushController extends Controller
{


    public function subscribe(Request $request)
    {
        $user = $request->user(); // User sudah login dengan Sanctum

        $data = $request->validate([
            'endpoint' => 'required|string',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'contentEncoding' => 'nullable|string',
        ]);

        $existing = $user->pushSubscriptions()->where('endpoint', $data['endpoint'])->first();

        if ($existing) {
            Log::info('Push subscription updated', ['user_id' => $user->id]);
        }

        $user->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
            $data['contentEncoding'] ?? 'aesgcm'
        );

        return response()->json(['message' => 'Push subscription saved.']);
    }


    public function send(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user->pushSubscription) {
                return response()->json(['message' => 'Tidak berlangganan'], 400);
            }

            $title = 'Test Notification';
            $body = 'This is a test notification';
            $url = null; // You can set a URL here if needed

            $user->notify(new PushNotification($title, $body, $url));

            return response()->json(['message' => 'Notifikasi berhasil dikirim']);

        } catch (\Exception $e) {
            Log::error('Push notification error: ' . $e->getMessage());
            return response()->json(['message' => 'Kesalahan saat mengirim notifikasi'], 500);
        }
    }
}