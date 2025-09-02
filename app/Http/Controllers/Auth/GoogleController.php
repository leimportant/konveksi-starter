<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Exception;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->with([
            'prompt' => 'select_account',
            'access_type' => 'offline',
        ])->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // 1. Cari user berdasarkan google_id
            $user = User::where('google_id', $googleUser->id)->first();

            if ($user) {
                if (!$user->active) {
                    return redirect('/login')->withErrors(['msg' => 'User anda tidak aktif, silakan hubungi admin.']);
                }
            }

            // 2. Jika tidak ada, buat user baru
            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'employee_status' => 'customer',
                    'password' => Hash::make('123456dummy')
                ]);

                // Assign role langsung
                DB::table('user_role')->insert([
                    'user_id' => $user->id,
                    'role_id' => 7,
                ]);
            }

            // 3. Cek apakah Customer sudah ada
            $customer = Customer::where('user_id', $user->id)->first();

            if (!$customer) {
                Customer::create([
                    'user_id' => $user->id,
                    'name' => $googleUser->name,
                    'address' => '',
                    'phone_number' => '',
                    'saldo_kredit' => 0,
                    'is_active' => 'Y',
                ]);
            }

            // 4. Login dan redirect
            Auth::login($user);

            $token = $user->createToken('aninkafashion-token');
            
            // 6. Set HTTP Only Cookie with token
            $cookie = cookie('aninkafashion-token', $token->plainTextToken, 60 * 24 * 30, null, null, true, true); // 30 days

            Log::info("token: {$token->plainTextToken}");
             // 🔹 Decide: JSON response for API, or redirect for web
            if (request()->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'user'   => [
                        'name'  => $user->name,
                        'email' => $user->email,
                    ],
                    'redirect' => '/home'
                ])->withCookie($cookie);
            }

            return redirect()->intended('/home')->withCookie($cookie);

        } catch (Exception $e) {
            Log::error('Google Login Error: ' . $e->getMessage());
            return redirect('/login')->withErrors(['msg' => 'Google login failed.']);
        }
    }
}
