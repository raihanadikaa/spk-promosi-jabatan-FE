<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bahasa tampilan: pilihan akun → pilihan di sesi (mis. dari halaman login) → bawaan aplikasi.
 */
class SetLocale
{
    public const SUPPORTED = ['id' => 'Bahasa Indonesia', 'en' => 'English'];

    public function handle(Request $request, Closure $next): Response
    {
        $candidate = $request->user()?->locale ?? ($request->hasSession() ? $request->session()->get('locale') : null);
        $locale = array_key_exists((string) $candidate, self::SUPPORTED) ? $candidate : config('app.locale');

        app()->setLocale($locale);
        Carbon::setLocale($locale); // "2 jam yang lalu" / "2 hours ago"

        return $next($request);
    }
}
