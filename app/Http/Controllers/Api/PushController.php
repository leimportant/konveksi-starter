<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use NotificationChannels\WebPush\PushSubscription;
use App\Notifications\PushNotification;

class PushController extends Controller
{
    /**
     * Store the PushSubscription.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function subscribe(Request $request)
    {
        $request->validate( [
            'endpoint' => 'required|string',
            'keys.auth' => 'required|string',
            'keys.p256dh' => 'required|string',
        ]);

        $endpoint = $request->endpoint;
        $token = $request->keys['auth'];
        $key = $request->keys['p256dh'];
        $user = Auth::user();

        // Log subscription attempt
        Log::info('Push subscription attempt', [
            'user_id' => $user->id,
            'endpoint' => $endpoint,
        ]);

        try {
            $subscription = PushSubscription::findByEndpoint($endpoint);

            if ($subscription && $subscription->user_id !== $user->id) {
                // If subscription exists but belongs to another user, delete it
                $subscription->delete();
                $subscription = null;
            }

            if (!$subscription) {
                // Create new subscription
                $subscription = new PushSubscription();
                $subscription->user_id = $user->id;
                $subscription->endpoint = $endpoint;
                $subscription->public_key = $key;
                $subscription->auth_token = $token;
                $subscription->save();

                Log::info('Push subscription created', ['user_id' => $user->id]);
            } else {
                // Update existing subscription
                $subscription->public_key = $key;
                $subscription->auth_token = $token;
                $subscription->save();

                Log::info('Push subscription updated', ['user_id' => $user->id]);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Push subscription failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified subscription from storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unsubscribe(Request $request)
    {
        $request->validate( [
            'endpoint' => 'required|string',
        ]);

        $endpoint = $request->endpoint;
        $user = Auth::user();

        // Log unsubscription attempt
        Log::info('Push unsubscription attempt', [
            'user_id' => $user->id,
            'endpoint' => $endpoint,
        ]);

        try {
            $subscription = PushSubscription::where([
                ['user_id', $user->id],
                ['endpoint', $endpoint],
            ])->first();

            if ($subscription) {
                $subscription->delete();
                Log::info('Push subscription deleted', ['user_id' => $user->id]);
                return response()->json(['success' => true]);
            }

            return response()->json(['success' => false, 'message' => 'Subscription not found'], 404);
        } catch (\Exception $e) {
            Log::error('Push unsubscription failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Send a push notification to the specified user.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(Request $request)
    {
        $request->validate( [
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string',
            'body' => 'required|string',
            'url' => 'nullable|string',
        ]);

        $user = \App\Models\User::find($request->user_id);
        $title = $request->title;
        $body = $request->body;
        $url = $request->url;

        // Log push notification attempt
        Log::info('Manual push notification attempt', [
            'sender_id' => Auth::id(),
            'recipient_id' => $user->id,
            'title' => $title,
        ]);

        try {
            $notification = new PushNotification;
            $notification->title($title)
                ->body($body);

            if ($url) {
                $notification->url($url);
            }

            $user->notify($notification);

            Log::info('Manual push notification sent', [
                'recipient_id' => $user->id,
                'title' => $title,
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Manual push notification failed', [
                'recipient_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}