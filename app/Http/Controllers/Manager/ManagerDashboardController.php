<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Concerns\EnforcesIdleTimeout;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManagerDashboardController extends Controller
{
    use EnforcesIdleTimeout;

    /**
     * Tampilkan dashboard manager.
     * NFR-SEC-05: cek idle timeout 30 menit sebelum render.
     */
    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->enforceIdleTimeout($request)) {
            return $redirect;
        }

        return view('manager.dashboard');
    }
}