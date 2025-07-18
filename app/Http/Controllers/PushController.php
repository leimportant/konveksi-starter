<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Notifications\PushNotification;
use Illuminate\Support\Facades\Log;

class PushController extends Controller
{


    public function subscribe(Request $request)
    {
        $user = $request->user();
        
        $user->updatePushSubscription(
            $request->input('endpoint'),
            $request->input('keys.p256dh'),
            $request->input('keys.auth')
        );

        return response()->json(['message' => 'Berhasil berlangganan']);
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