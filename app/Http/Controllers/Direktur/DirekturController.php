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
        return $this->candidateView($employee, ['url' => route('direktur.ranking'), 'label' => 'Ranking & rekomendasi'], canDecide: true);
    }

    public function decide(Request $request, int $employee, DecisionService $decisions): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $period = PromotionData::currentPeriodModel() ?? abort(404);
        $result = RankingResult::where('period_id', $period->id)->where('employee_id', $employee)->firstOrFail();
        $approve = $validated['decision'] === 'approve';

        $decisions->decide($request->user(), $result, $approve, $validated['note'] ?? null);

        return to_route('direktur.dashboard')->with('success', $approve ? 'Promosi disetujui.' : 'Rekomendasi promosi ditolak.');
    }

    public function export(): StreamedResponse
    {
        return $this->exportCsv();
    }
}
