<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan standar untuk setiap respons HTML.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();

        $response = $next($request);

        $nonce = Vite::cspNonce();

        // Saat `npm run dev`, aset dimuat dari server Vite (port 5173) + websocket HMR.
        $hot = Vite::isRunningHot() ? rtrim(trim(file_get_contents(Vite::hotFile())), '/') : null;
        $dev = $hot ? " {$hot}" : '';
        $devWs = $hot ? ' ' . preg_replace('#^http#', 'ws', $hot) : '';

        // Alpine.js mengevaluasi ekspresi dengan new Function → butuh 'unsafe-eval'.
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval'{$dev}",
            "style-src 'self' 'unsafe-inline'{$dev}",
            "font-src 'self' data:{$dev}",
            "img-src 'self' data:",
            "connect-src 'self'{$devWs}{$dev}",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "object-src 'none'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Halaman ber-login jangan disimpan di cache browser (tombol Back setelah logout).
        if ($request->user()) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
