<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Customer::create([
            'id' => $user->id,
            'name' => $user->name,
            'address' => '',
            'phone_number' => '',
            'saldo_kredit' => 0,
            'is_active' => 'Y',
        ]);

        DB::table('user_role')->insert([
            'user_id' => $user->id,
            'role_id' => 7,
        ]);
        
        event(new Registered($user));
        Auth::login($user);

        $token = $user->createToken('aninkafashion-token');
        
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

        $response = redirect()->intended('/home');
        return $response->withCookie($cookie);
    }
}
