<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Notifications\PushNotification;
use Illuminate\Support\Facades\Log;
use NotificationChannels\WebPush\Models\PushSubscription;
use Illuminate\Support\Facades\Auth;

class PushController extends Controller
{


    public function subscribe(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                Log::warning('Push subscription attempt without authentication');
                return response()->json(['message' => 'Authentication required'], 401);
            }
            
            $data = $request->validate([
                'endpoint' => 'required|string',
                'keys.p256dh' => 'required|string',
                'keys.auth' => 'required|string',
                'contentEncoding' => 'nullable|string',
            ]);
            
            Log::info('Push subscription data received', [
                'user_id' => $user->id,
                'endpoint' => $data['endpoint']
            ]);
            
            $existing = $user->pushSubscriptions()->where('endpoint', $data['endpoint'])->first();
            
            if ($existing) {
                Log::info('Push subscription updated', ['user_id' => $user->id]);
            } else {
                Log::info('New push subscription created', ['user_id' => $user->id]);
            }
            
            $user->updatePushSubscription(
                $data['endpoint'],
                $data['keys']['p256dh'],
                $data['keys']['auth'],
                $data['contentEncoding'] ?? 'aesgcm'
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Push subscription saved successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Push subscription error: ' . $e->getMessage(), [
                'user_id' => $request->user() ? $request->user()->id : 'unauthenticated',
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save push subscription: ' . $e->getMessage()
            ], 500);
        }
    }


    public function send(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Authentication required'], 401);
            }
            
            $subscriptions = $user->pushSubscriptions;
            
            if ($subscriptions->isEmpty()) {
                Log::warning('Push notification attempt without subscription', ['user_id' => $user->id]);
                return response()->json([
                    'success' => false, 
                    'message' => 'No push subscription found for this user'
                ], 400);
            }
            
            // Get custom notification data if provided
            $title = $request->input('title', 'Notifikasi Baru');
            $body = $request->input('body', 'Anda memiliki pesan baru.');
            $url = $request->input('url');
            
            Log::info('Sending push notification', [
                'user_id' => $user->id,
                'title' => $title,
                'subscription_count' => $subscriptions->count()
            ]);
            
            // Send notification
            $user->notify(new PushNotification($title, $body, $url));
            
            return response()->json([
                'success' => true,
                'message' => 'Notification sent successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Push notification error: ' . $e->getMessage(), [
                'user_id' => $request->user() ? $request->user()->id : 'unauthenticated',
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false, 
                'message' => 'Error sending notification: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function unsubscribe(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Authentication required'], 401);
            }
            
            $endpoint = $request->input('endpoint');
            
            if (!$endpoint) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Endpoint is required'
                ], 400);
            }
            
            // Find and delete the subscription
            $deleted = $user->pushSubscriptions()
                ->where('endpoint', $endpoint)
                ->delete();
            
            if ($deleted) {
                Log::info('Push subscription deleted', ['user_id' => $user->id, 'endpoint' => $endpoint]);
                return response()->json([
                    'success' => true, 
                    'message' => 'Push subscription deleted successfully'
                ]);
            } else {
                Log::warning('Push subscription not found for deletion', ['user_id' => $user->id]);
                return response()->json([
                    'success' => false, 
                    'message' => 'Subscription not found'
                ], 404);
            }
            
        } catch (\Exception $e) {
            Log::error('Push unsubscription error: ' . $e->getMessage(), [
                'user_id' => $request->user() ? $request->user()->id : 'unauthenticated',
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false, 
                'message' => 'Error unsubscribing: ' . $e->getMessage()
            ], 500);
        }
    }
}