<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Concerns\EnforcesIdleTimeout;
use App\Http\Controllers\Controller;
use App\Queries\PromotionData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KaryawanDashboardController extends Controller
{
    use EnforcesIdleTimeout;

    /**
     * Tampilkan dashboard karyawan: hanya skor milik sendiri (dokumen ROLE).
     * NFR-SEC-05: cek idle timeout 30 menit sebelum render.
     */
    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->enforceIdleTimeout($request)) {
            return $redirect;
        }

        $period = PromotionData::currentPeriod();
        $employee = $request->user()->employee()->with('supervisor:id,name')->first();

        return view('karyawan.dashboard', [
            'period' => $period,
            'employee' => $employee,
            'result' => $employee ? PromotionData::rankingRow($period, $employee->id) : null,
            'total' => PromotionData::rankingCount($period),
            'criteria' => $period ? PromotionData::criteria($period['id']) : [],
        ]);
    }
}
