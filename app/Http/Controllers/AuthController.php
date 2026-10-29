<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesIdleTimeout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    use EnforcesIdleTimeout;

    /** NFR-SEC-05: 5 kali gagal → lockout. */
    private const MAX_LOGIN_ATTEMPTS = 5;

    /** NFR-SEC-05: durasi lockout (detik). 300 = 5 menit. */
    private const LOCKOUT_SECONDS = 300;

    public function showLogin(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            if ($redirect = $this->enforceIdleTimeout($request)) {
                return $redirect;
            }
            return redirect()->to(Auth::user()->dashboardUrl());
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // NFR-SEC-05: cek lockout
        $this->ensureIsNotThrottled($request);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            RateLimiter::clear($this->throttleKey($request));
            $request->session()->regenerate();
            $request->session()->put('last_activity_at', time());

            $user = Auth::user();

            // PENGAMAN: Cek jika user tidak memiliki role yang valid
            // Mencegah error 500 atau redirect loop
            if (empty($user->roleName())) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withErrors(['email' => 'Akun Anda belum memiliki Role. Hubungi Administrator.']);
            }

            if (! $user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                return back()->withErrors(['email' => 'Akun Anda dinonaktifkan. Hubungi Administrator.'])->onlyInput('email');
            }

            $user->forceFill(['last_login_at' => now()])->saveQuietly();

            return redirect()
                ->intended($user->dashboardUrl())
                ->with('success', 'Selamat datang, ' . $user->name . '!');
        }

        // NFR-SEC-05: catat kegagalan
        RateLimiter::hit($this->throttleKey($request), self::LOCKOUT_SECONDS);

        $attempts = RateLimiter::attempts($this->throttleKey($request));

        $msg = 'Email atau password salah.';
        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $msg = 'Email atau password salah. Akun terkunci sementara selama '
                . ceil(self::LOCKOUT_SECONDS / 60) . ' menit.';
        } elseif ($attempts >= 3) {
            $msg .= ' Sisa percobaan: ' . (self::MAX_LOGIN_ATTEMPTS - $attempts) . ' kali.';
        }

        return back()
            ->withErrors(['email' => $msg])
            ->onlyInput('email');
    }

    protected function ensureIsNotThrottled(Request $request): void
    {
        $key = $this->throttleKey($request);

        if (! RateLimiter::tooManyAttempts($key, self::MAX_LOGIN_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'email' => 'Akun terkunci sementara karena terlalu banyak percobaan login. '
                . 'Silakan coba lagi dalam ' . ceil($seconds / 60) . ' menit.',
        ]);
    }

    protected function throttleKey(Request $request): string
    {
        return Str::lower((string) $request->input('email')) . '|' . $request->ip();
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Anda telah berhasil logout.');
    }
}