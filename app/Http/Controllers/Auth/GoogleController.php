<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
                    'employee_status' => '-', // Set to null or a default value if not applicable
                    'password' => encrypt('123456dummy')
                ]);

                // Assign the default role to the new user
                // insert into user_roles (user_id, role_id) values ($newUser->id, 1);
                DB::table('user_role')->insert([
                    'user_id' => $newUser->id,
                    'role_id' => 7 // Assuming 1 is the ID for the default role
                ]);

                // the default role, adjust as necessary


                Auth::login($newUser);
                return redirect()->intended('/dashboard');
            }

        } catch (Exception $e) {
            Log::info($e->getMessage());
            echo($e->getMessage());
        }
    }
}