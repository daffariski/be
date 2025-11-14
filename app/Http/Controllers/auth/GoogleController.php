<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Redirect user to Google's OAuth page.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Handle callback from Google.
     */
   public function handleGoogleCallback()
{
    try {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
                'role' => 'customer',
                'password' => bcrypt(Str::random(16)),
            ]
        );

  
        if (!$user->customer) {
            Customer::create(['user_id' => $user->id]);
        }

        $token = $user->createToken('google_auth_token')->plainTextToken;

        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

        return response()->view('oauth-success', [
            'frontendUrl' => $frontendUrl,
            'token' => $token,
            'user_id' => $user->id,
        ]);
    } catch (\Exception $e) {
        return response()->view('oauth-failed', [
            'message' => $e->getMessage(),
        ]);
    }
}
}