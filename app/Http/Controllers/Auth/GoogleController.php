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

            $existingUser = User::where('google_id', $googleUser->id)->first();

            if ($existingUser) {
                Auth::login($existingUser);
                return redirect()->intended('/home');
            } else {
                $newUser = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'location_id' => 1,
                    'employee_status' => 'customer',
                    'password' => Hash::make('123456dummy')
                ]);

                DB::table('user_role')->insert([
                    'user_id' => $newUser->id,
                    'role_id' => 7
                ]);

                Customer::create([
                    'id' => $newUser->id,
                    'name' => $googleUser->name,
                    'address' => "",
                    'phone_number' => "",
                    'saldo_kredit' => 0,
                    'is_active' => 'Y',
                ]);

                Auth::login($newUser);
                return redirect()->intended('/home');
            }
        } catch (Exception $e) {
            Log::error('Google Login Error: ' . $e->getMessage());
            return redirect('/login')->withErrors(['msg' => 'Google login failed.']);
        }
    }


}