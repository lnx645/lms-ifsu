<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the Google authentication callback.
     */
    public function callback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();
        // cek dulu by email

        $email = $googleUser->getEmail();
        if (User::whereEmail($email)->doesntExist()) {
            Toast::error('Akun google tidak terdaftar! Atau belum aktif Silahkan hubungi operator sekolah');

            return redirect()->route('auth.login');
        }

        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'google_id' => $googleUser->getId(),
                'name' => $googleUser->getName() ?? $googleUser->getNickname() ?? $googleUser->getEmail(),
                'password' => Str::password(),
            ],
        );

        Auth::login($user);
        Toast::success('Berhasil login mengunakan akun google!');

        return redirect()->intended(route('app.dashboard'));
    }
}
