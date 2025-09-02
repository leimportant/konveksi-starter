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

class FacebookController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('facebook')->with([
            'prompt' => 'select_account',
            'access_type' => 'offline',
        ])->redirect();
    }

    public function handleFacebookCallback()
    {
        try {
            $fbUser = Socialite::driver('facebook')->stateless()->user();

            // 1. Cari user berdasarkan email terlebih dahulu
            $user = User::where('email', $fbUser->getEmail())->first();

            if ($user) {
                if (!$user->facebook_id) {
                    $user->update(['facebook_id' => $fbUser->getId()]);
                }

                if (!$user->active) {
                    return redirect('/login')->withErrors(['msg' => 'User anda tidak aktif, silakan hubungi admin.']);
                }
            }

            // 2. Jika tidak ada user, buat baru
            if (!$user) {
                $user = User::create([
                    'name' => $fbUser->getName(),
                    'email' => $fbUser->getEmail(),
                    'facebook_id' => $fbUser->getId(),
                    'employee_status' => 'customer',
                    'password' => Hash::make('123456dummy') // dummy password
                ]);

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
                    'name' => $user->name,
                    'address' => '',
                    'phone_number' => '',
                    'saldo_kredit' => 0,
                    'is_active' => 'Y',
                ]);
            }

            // 4. Login dan redirect
            Auth::login($user);
            return redirect()->intended('/home');

        } catch (Exception $e) {
            Log::error('Facebook Login Error: ' . $e->getMessage());
            return redirect('/login')->withErrors(['msg' => 'Facebook login failed.']);
        }
    }

}
