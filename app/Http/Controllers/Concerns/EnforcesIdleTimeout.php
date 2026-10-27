<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait EnforcesIdleTimeout
{
    /**
     * NFR-SEC-05: 30 menit idle = logout paksa.
     * Kembalikan RedirectResponse jika sesi harus berakhir, null jika masih valid.
     */
    protected function enforceIdleTimeout(Request $request): ?RedirectResponse
    {
        if (! Auth::check()) {
            return null;
        }

        $last = (int) $request->session()->get('last_activity_at', 0);

        // 1800 detik = 30 menit
        if ($last > 0 && (time() - $last) > 1800) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Sesi Anda telah berakhir karena tidak aktif selama 30 menit. Silakan login kembali.');
        }

        // perbarui timestamp aktivitas
        $request->session()->put('last_activity_at', time());

        return null;
    }
}