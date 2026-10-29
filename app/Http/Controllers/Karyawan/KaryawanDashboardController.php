<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Concerns\EnforcesIdleTimeout;
use App\Http\Controllers\Controller;
use App\Support\DemoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KaryawanDashboardController extends Controller
{
    use EnforcesIdleTimeout;

    /**
     * Tampilkan dashboard karyawan.
     * NFR-SEC-05: cek idle timeout 30 menit sebelum render.
     */
    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->enforceIdleTimeout($request)) {
            return $redirect;
        }

        // Akun karyawan dicocokkan ke data employee lewat nama (nanti: employees.user_id).
        $employee = collect(DemoData::employees())->firstWhere('name', $request->user()->name);

        return view('karyawan.dashboard', [
            'period' => DemoData::currentPeriod(),
            'employee' => $employee,
            'result' => $employee ? (DemoData::ranking()[$employee['id']] ?? null) : null,
            'total' => count(DemoData::ranking()),
        ]);
    }
}