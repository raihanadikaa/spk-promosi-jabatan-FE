<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Concerns\EnforcesIdleTimeout;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * NFR-SEC-05: logout paksa setelah 30 menit tanpa aktivitas, untuk semua halaman yang butuh login.
 */
class IdleTimeout
{
    use EnforcesIdleTimeout;

    public function handle(Request $request, Closure $next): Response
    {
        return $this->enforceIdleTimeout($request) ?? $next($request);
    }
}
