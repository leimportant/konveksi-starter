<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TokenController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();
        $token = $user->createToken('aninkafashion-token');
        
        // Log the token for testing
        Log::info('New token generated', [
            'user' => $user->email,
            'token' => $token->plainTextToken
        ]);
        
        return response()->json([
            'token' => $token->plainTextToken
        ]);
    }

    public function destroy(Request $request)
    {
        // Revoke the current token
        if ($request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }
        
        // Logout from session
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function checkToken(Request $request)
    {
        if ($request->user() && $request->user()->currentAccessToken()) {
            return response()->json([
                'status' => 'active',
                'token_info' => [
                    'name' => $request->user()->currentAccessToken()->name,
                    'created_at' => $request->user()->currentAccessToken()->created_at,
                    'last_used_at' => $request->user()->currentAccessToken()->last_used_at
                ]
            ]);
        }

        return response()->json([
            'status' => 'invalid'
        ], 401);
    }
}