<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Ganti password akun sendiri (semua role), mis. setelah menerima password sementara dari admin.
 */
class AccountController extends Controller
{
    public function editPassword(): View
    {
        return view('account.password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'Password saat ini salah.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'password.different' => 'Password baru harus berbeda dari password saat ini.',
        ], [
            'current_password' => 'password saat ini',
            'password' => 'password baru',
        ]);

        $user = $request->user();
        $remembered = $request->hasCookie(Auth::getRecallerName());

        // Rotasi remember_token: cookie "Ingat saya" di perangkat lain tidak berlaku lagi.
        $user->forceFill(['password' => $validated['password'], 'remember_token' => Str::random(60)])->save();

        // Keluarkan sesi lain yang mungkin masih memakai password lama.
        DB::table('sessions')->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->delete();

        // Perangkat ini tetap masuk (dengan cookie "Ingat saya" baru bila sebelumnya dipakai).
        Auth::login($user, $remembered);
        $request->session()->regenerate();

        return back()->with('success', 'Password berhasil diganti.');
    }
}
