<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Concerns\EnforcesIdleTimeout;
use App\Http\Controllers\Controller;
use App\Queries\PromotionData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HrdDashboardController extends Controller
{
    use EnforcesIdleTimeout;

    /**
     * Tampilkan dashboard HRD.
     * NFR-SEC-05: cek idle timeout 30 menit sebelum render.
     */
    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->enforceIdleTimeout($request)) {
            return $redirect;
        }

        $period = PromotionData::currentPeriod();
        if (! $period) {
            return view('shared.no-period');
        }

        // Tindak lanjut: karyawan yang belum selesai dinilai (draft tampil lebih dulu). Dibatasi agar ringan.
        $followUps = PromotionData::employeeQuery($period, ['status' => 'unfinished'])->limit(8)->get()
            ->map(fn ($e) => PromotionData::employeeArray($e, $period))
            ->sortBy(fn ($e) => $e['status'] === 'draft' ? 0 : 1)
            ->values();

        return view('hrd.dashboard', [
            'period' => $period,
            'counts' => PromotionData::statusCounts($period),
            'approvals' => PromotionData::approvalCounts($period),
            'departments' => PromotionData::departmentProgress($period),
            'followUps' => $followUps,
            'top' => PromotionData::ranking($period, limit: 5),
            'stale' => PromotionData::staleReasons($period),
        ]);
    }
}
