<?php

namespace App\Http\Controllers\Direktur;

use App\Http\Controllers\Concerns\ShowsRanking;
use App\Http\Controllers\Controller;
use App\Models\RankingResult;
use App\Queries\PromotionData;
use App\Services\DecisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DirekturController extends Controller
{
    use ShowsRanking;

    public function ranking(Request $request): View
    {
        return $this->rankingView($request, 'direktur.ranking.show', 'all', exportRoute: 'direktur.ranking.export');
    }

    public function candidate(int $employee): View
    {
        return $this->candidateView($employee, ['url' => route('direktur.approvals'), 'label' => 'Persetujuan promosi'], canDecide: true);
    }

    /** Daftar kandidat yang direkomendasikan sistem beserta status keputusannya. */
    public function approvals(Request $request): View
    {
        $period = PromotionData::currentPeriod();
        if (! $period) {
            return $this->noPeriodView();
        }

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending,approved,rejected,revision'],
        ]);
        $filters = ['q' => $validated['q'] ?? null, 'status' => $validated['status'] ?? 'recommended'];

        return view('direktur.approvals', [
            'period' => $period,
            'rows' => PromotionData::rankingPage($period, $filters),
            'counts' => PromotionData::approvalCounts($period),
            'filters' => ['q' => $filters['q'], 'status' => $validated['status'] ?? null],
            'open' => PromotionData::isOpen($period),
        ]);
    }

    public function decide(Request $request, int $employee, DecisionService $decisions): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject,revision'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $period = PromotionData::currentPeriodModel() ?? abort(404);
        $result = RankingResult::where('period_id', $period->id)->where('employee_id', $employee)->firstOrFail();

        $decisions->decide($request->user(), $result, $validated['decision'], $validated['note'] ?? null);

        return to_route('direktur.approvals')->with('success', match ($validated['decision']) {
            'approve' => 'Promosi disetujui.',
            'reject' => 'Rekomendasi promosi ditolak.',
            'revision' => 'Permintaan review ulang dikirim ke HRD.',
        });
    }

    public function export(): StreamedResponse
    {
        return $this->exportCsv();
    }

    public function report(): View
    {
        return $this->reportView('direktur.ranking');
    }
}
