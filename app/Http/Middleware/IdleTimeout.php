<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Concerns\EnforcesIdleTimeout;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dipasang di semua halaman ber-login:
 * - NFR-SEC-05: logout paksa setelah 30 menit tanpa aktivitas.
 * - Akun yang dinonaktifkan admin langsung dikeluarkan, walau sesinya masih hidup.
 */
class IdleTimeout
{
    use EnforcesIdleTimeout;

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Akun Anda dinonaktifkan. Hubungi Administrator.');
        }

        return $this->enforceIdleTimeout($request) ?? $next($request);
    }
}
