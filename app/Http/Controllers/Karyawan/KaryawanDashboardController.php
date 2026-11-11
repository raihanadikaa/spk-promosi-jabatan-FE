<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Concerns\EnforcesIdleTimeout;
use App\Http\Controllers\Controller;
use App\Models\AssessmentPeriod;
use App\Models\RankingResult;
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
            'history' => $employee ? $this->history($employee->id, $period['id'] ?? null) : [],
        ]);
    }

    /** Hasil periode lain yang sudah ditutup (terbaru dulu), dengan jumlah peserta tiap periode. */
    private function history(int $employeeId, ?int $currentPeriodId): array
    {
        $results = RankingResult::query()
            ->where('employee_id', $employeeId)
            ->when($currentPeriodId, fn ($q) => $q->where('period_id', '!=', $currentPeriodId))
            ->whereHas('period', fn ($q) => $q->where('status', AssessmentPeriod::CLOSED))
            ->with('period:id,period_name,start_date')
            ->get()
            ->sortByDesc(fn ($r) => $r->period->start_date);

        $totals = RankingResult::whereIn('period_id', $results->pluck('period_id'))
            ->selectRaw('period_id, count(*) as n')->groupBy('period_id')->pluck('n', 'period_id');

        return $results->map(fn ($r) => [
            'period' => $r->period->period_name,
            'score' => $r->final_score,
            'rank' => $r->rank,
            'total' => (int) ($totals[$r->period_id] ?? 0),
            'approval_status' => $r->approval_status,
        ])->values()->all();
    }
}
