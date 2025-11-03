<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse|JsonResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        Auth::user();

        $user = \App\Models\User::where('email', $request->email)->first();

        if ($user->active == 'false' || $user->active == 'N' || !$user->active) {
            return redirect('/login')->withErrors(['msg' => 'User anda tidak aktif, silakan hubungi admin.']);
        }

        $token = $user->createToken('aninkafashion-token');
        
        Log::info("token: {$token->plainTextToken}");
        $cookie = cookie(
            'aninkafashion-token', 
            $token->plainTextToken, 
            60 * 24 * 30,
            null, 
            null, 
            true,
            true
        );

        if ($request->wantsJson()) {
            $response = new JsonResponse([
                'status' => 'success',
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'redirect' => '/home'
            ]);
            return $response->withCookie($cookie);
        }

        return redirect()->intended('/home')->withCookie($cookie);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        // Revoke current token if exists
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        } else if ($request->user() && $request->user()->tokens()->count() > 0) {
            // If currentAccessToken is null but other tokens exist, delete all of them
            $request->user()->tokens()->delete();
        }

        // Logout from session
        Auth::guard('web')->logout();

        // Invalidate session
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Remove auth cookie
        $cookie = cookie()->forget('aninkafashion-token');

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Logged out successfully'
            ])->withCookie($cookie);
        }

        return redirect('/')->withCookie($cookie);
    }
}