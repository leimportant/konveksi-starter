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
        $request->validate([
            'order_id' => 'required|string',
        ]);

        $messages = ChatMessage::where('order_id', $request->order_id)
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
            'sender_id' => 'sometimes|integer',
            'receiver_id' => 'required|integer',
            'message' => 'required|string',
            'order_id' => 'nullable|string',
        ]);

        $validated['sender_id'] = Auth::id(); // ambil dari token/login

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
        } else {
            // Jika receiver_id tersedia, cari user
            $receiver = User::find($validated['receiver_id']);
        }

        $chat = ChatMessage::create([
            'id' => (string) Str::uuid(),
            'sender_type' => $validated['sender_type'],
            'sender_id' => $validated['sender_id'],
            'receiver_id' => $validated['receiver_id'] ?? "*",
            'message' => $validated['message'],
            'order_id' => $validated['order_id'] ?? null,
            'is_read' => false,
        ]);

        event(new ChatMessageSent($chat));
        // Kirim email ke penerima
        if ($receiver && $receiver->email) {
            Mail::to($receiver->email)->send(new ChatMessageNotification($chat));
        }

        return response()->json(['message' => 'Message sent', 'data' => $chat], 201);
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(Request $request)
    {
        $request->validate([
            'id' => 'required',
        ]);

        ChatMessage::where('id', $request->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'Messages marked as read']);
    }

    public function conversations()
    {
        $user = Auth::user();
        $userId = $user->id;
        $userRoleIds = $user->roles->pluck('id');
 Log::info($userId);
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
                        ->where('is_read', false)
                        ->count(),
                ];
            })
            ->values();

            Log::info($conversations);

        return response()->json($conversations);
    }


}
