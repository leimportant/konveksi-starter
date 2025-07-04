<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChatMessage;
use Illuminate\Support\Str;
use App\Mail\ChatMessageNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Events\ChatMessageSent;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use App\Models\User;


class ChatMessageController extends Controller
{
    /**
     * Get messages between two users (admin & customer)
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $order_id = $request->order_id ?? "";

        $messages = ChatMessage::with('sender')
            ->where('order_id', $order_id)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    /**
     * Send a new chat message
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'sender_type' => 'required|in:admin,customer',
            'message' => 'required|string',
            'receiver_id' => 'nullable|integer|exists:users,id',
            'content_data' => 'nullable|array',
            'order_id' => 'nullable|string',
        ]);

        $validated['sender_id'] = Auth::id();

        $receiver = null;

        // Jika customer dan receiver_id kosong → cari admin dari setting
        if ($validated['sender_type'] === 'customer' && empty($validated['receiver_id'])) {
            $adminSetting = Setting::where('key', 'notif_mail_chat')->first();

            if ($adminSetting && $adminSetting->value) {
                $emails = array_map('trim', explode(',', $adminSetting->value));
                $receiver = User::whereIn('email', $emails)->first();

                if ($receiver) {
                    $validated['receiver_id'] = $receiver->id;
                }
            }
        } elseif (!empty($validated['receiver_id'])) {
            // Jika receiver_id tersedia, cari user
            $receiver = User::find($validated['receiver_id']);
        }

        $chat = ChatMessage::create([
            'id' => (string) Str::uuid(),
            'sender_type' => $validated['sender_type'],
            'sender_id' => $validated['sender_id'],
            'receiver_id' => $validated['receiver_id'] ?? 0,
            'content_data' => json_encode($validated['content_data'] ?? []),
            'message' => $validated['message'],
            'order_id' => $validated['order_id'] ?? null,
            'is_read' => false,
        ]);

        event(new ChatMessageSent($chat));

        // Kirim email ke penerima jika ada
        if ($receiver && $receiver->email) {
            Mail::to($receiver->email)->send(new ChatMessageNotification($chat));
        }

        return response()->json([
            'message' => $validated['message'] ?? 'Message sent',
            'data' => $chat,
        ], 201);
    }


    /**
     * Mark messages as read
     */
    public function markAsRead($ordeId)
    {

        ChatMessage::where('order_id', $ordeId)
            ->where('is_read', '0')
            ->update(['is_read' => 1]);

        return response()->json(['message' => 'Messages marked as read']);
    }

    public function conversations()
    {
        $user = Auth::user();
        $userId = $user->id;

        Log::info($userId);
        $userRoleIds = $user->roles->pluck('id');

        $query = ChatMessage::query();

        // Jika bukan owner (role_id 2), filter berdasarkan user ID
        if (!$userRoleIds->contains(2)) {
            $query->where(function ($q) use ($userId) {
                $q->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            });
        }

        $conversations = $query
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('order_id')
            ->map(function ($messages, $orderId) use ($userId) {
                $last = $messages->last();
                return [
                    'order_id' => $orderId,
                    'last_message' => $last->message,
                    'last_sender_name' => $last->sender_name,
                    'last_sent_at' => $last->created_at,
                    'unread_count' => $messages
                        ->where('receiver_id', $userId)
                        ->where('is_read', '0')
                        ->count(),
                ];
            })
            ->values();

        Log::info($conversations);

        return response()->json($conversations);
    }


}
