<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\Customer;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use \Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
   public function edit(Request $request): Response
    {
        $user = $request->user();

        Log::info($user);

        // Ambil data customer jika ada
        $customer = Customer::find($user->id);

        return Inertia::render('settings/Profile', [
            'name' => $user->name,
            'email' => $user->email,
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'address' => $customer?->address,
            'phone_number' => $customer?->phone_number,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // Update user basic info
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->forceFill(['email_verified_at' => null]);
        }

        $user->save();

        $isExist = Customer::where('id', $user->id)->first();

        if ($isExist) {
            // Sudah ada -> update
            Customer::where('id', $user->id)->update([
                'name' => $request->input('name'),
                'phone_number' => $request->input('phone_number'),
                'address' => $request->input('address'),
            ]);
        } else {
            // Belum ada -> create
            Customer::create([
                'id' => $user->id,
                'name' => $request->input('name'),
                'phone_number' => $request->input('phone_number'),
                'address' => $request->input('address'),
                'is_active' => 'Y',
            ]);
        }



        return to_route('profile.edit')->with('status', 'profile-updated');
    }


    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
