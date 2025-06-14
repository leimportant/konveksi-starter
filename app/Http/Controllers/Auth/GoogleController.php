<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;
use Exception;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->with(['prompt' => 'select_account'])->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            // $googleUser  = Socialite::driver('google')->user();
            $googleUser = Socialite::driver('google')->stateless()->user();

            $finduser = User::where('google_id', $googleUser->id)->first();

            if($finduser){
                Auth::login($finduser);
                return redirect()->intended('/dashboard');
            }else{
                $newUser = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id'=> $googleUser->id,
                    'password' => encrypt('123456dummy')
                ]);

                Auth::login($newUser);
                return redirect()->intended('/dashboard');
            }

        } catch (Exception $e) {
            Log::info($e->getMessage());
            echo($e->getMessage());
        }
    }
}