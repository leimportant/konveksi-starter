<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\ChatLog;
use Log;

class ChatAssistantController extends Controller
{
    /**
     * 🧾 Save chat log (from user or assistant)
     * Route: POST /chat/{id}/save
     */
    public function saveChatLog(Request $request, $id)
    {
        try {
            // Validasi data dasar
            $validated = $request->validate([
                'chat_id' => 'required|string|max:100',
                'role' => 'required|in:user,assistant,admin',
                'question' => 'nullable|string',
                'content' => 'required|string',
                'rating' => 'nullable|in:like,dislike',
                'is_saved' => 'boolean',
                'is_important' => 'boolean',
            ]);

            // Simpan data
            $chat = ChatLog::updateOrCreate(
                ['chat_id' => $validated['chat_id']],
                [
                    'transaction_id' => $id,
                    'role' => $validated['role'],
                    'question' => $validated['question'] ?? null,
                    'content' => $validated['content'],
                    'rating' => $validated['rating'] ?? null,
                    'is_saved' => $validated['is_saved'] ?? false,
                    'is_important' => $validated['is_important'] ?? false,
                ]
            );

            return response()->json([
                'status' => true,
                'message' => 'Chat log saved successfully',
                'data' => $chat,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to save chat log',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ⭐ Rate a chat (like / dislike)
     * Route: POST /chat/{id}/rating
     */
    public function rating(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'chat_id' => 'required|string|max:100',
                'rating' => 'required|in:like,dislike',
                'content' => 'nullable|string' // kalau juga kirim content
            ]);

            $chat = ChatLog::updateOrCreate(
                [
                    'transaction_id' => $id,
                    'chat_id' => $validated['chat_id'],
                ],
                [
                    'rating' => $validated['rating'],
                    'content' => $validated['content'] ?? null, // simpan content jika baru
                ]
            );

            return response()->json([
                'status' => true,
                'message' => 'Rating updated successfully',
                'data' => $chat
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update rating',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * 🧠 Get chat logs by transaction ID
     * Route: GET /chat/{id}/get
     */
    public function getChatLog($id)
    {
        try {
            $logs = ChatLog::where('transaction_id', $id)
                ->orderBy('created_at', 'asc')
                ->get();

            if ($logs->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No chat found for this transaction ID',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Chat logs retrieved successfully',
                'data' => $logs,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to get chat logs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ⚠️ Escalate chat to admin
     * Route: POST /chat/{id}/escalate
     */
    public function escalate(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'chat_id' => 'required|string|max:100',
            ]);

            $updated = ChatLog::where('transaction_id', $id)
                ->where('chat_id', $validated['chat_id'])
                ->update(['escalated_at' => now()]);

            if (!$updated) {
                return response()->json(['status' => false, 'message' => 'Chat not found'], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Chat escalated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to escalate chat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🧑‍💼 Admin reply to an escalated chat
     * Route: POST /chat/{id}/reply
     */
    public function replyByAdmin(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'chat_id' => 'required|string|max:100',
                'content' => 'required|string',
            ]);

            // Cek apakah chat yang direply memang ada dan sudah dieskalasi
            $originalChat = ChatLog::where('transaction_id', $id)
                ->where('chat_id', $validated['chat_id'])
                ->first();

            if (!$originalChat) {
                return response()->json([
                    'status' => false,
                    'message' => 'Original chat not found for admin reply'
                ], 404);
            }

            if (is_null($originalChat->escalated_at)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Chat has not been escalated yet'
                ], 400);
            }

            // Buat ID unik untuk balasan admin
            $replyId = 'admin_' . uniqid();

            // Simpan balasan admin
            $reply = ChatLog::create([
                'transaction_id' => $id,
                'chat_id' => $replyId,
                'role' => 'admin',
                'question' => null,
                'content' => $validated['content'],
                'is_saved' => true, // otomatis disimpan
                'is_important' => true, // bisa dianggap penting
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Admin reply saved successfully',
                'data' => $reply
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to save admin reply',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function markAsImportant(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'chat_id' => 'required|string|max:100',
                'is_important' => 'required|boolean',
                'content' => 'nullable|string', // ✅ sekarang content ikut divalidasi
            ]);

            $chat = ChatLog::updateOrCreate(
                [
                    'transaction_id' => $id,
                    'chat_id' => $validated['chat_id'],
                ],
                [
                    'is_important' => $validated['is_important'],
                    'content' => $validated['content'] ?? null, // ✅ simpan content jika ada
                ]
            );

            return response()->json([
                'status' => true,
                'message' => 'Important flag updated & content saved',
                'data' => $chat
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



}

