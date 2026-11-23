<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Concerns\ShowsRanking;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveAssessmentRequest;
use App\Queries\PromotionData;
use App\Services\AssessmentService;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManagerController extends Controller
{
    use ShowsRanking;

    public function assessments(Request $request): View
    {
        $period = PromotionData::currentPeriod();
        if (! $period) {
            return $this->noPeriodView();
        }

        $v = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending,draft,submitted'],
        ]);
        $filters = ['q' => $v['q'] ?? null, 'status' => $v['status'] ?? null, 'supervisor_id' => $request->user()->id];

        return view('manager.assessments', [
            'team' => PromotionData::employeesPage($period, $filters),
            'counts' => PromotionData::statusCounts($period, $request->user()->id),
            'filters' => $filters,
            'period' => $period,
            'open' => PromotionData::isOpen($period),
        ]);
    }

    public function assess(Request $request, int $employee): View
    {
        $period = PromotionData::currentPeriod();
        if (! $period) {
            return $this->noPeriodView();
        }

        // Atasan hanya boleh membuka bawahannya sendiri (dokumen ROLE); selain itu 404.
        $model = PromotionData::employeeQuery($period, ['supervisor_id' => $request->user()->id])->whereKey($employee)->first() ?? abort(404);
        $target = PromotionData::employeeArray($model, $period);

        return view('manager.assess', [
            'employee' => $target,
            'criteria' => PromotionData::criteria($period['id']),
            'period' => $period,
            'locked' => $target['status'] === 'submitted' || ! PromotionData::isOpen($period),
        ]);
    }

    public function save(SaveAssessmentRequest $request, int $employee, AssessmentService $service): RedirectResponse
    {
        $target = $request->user()->subordinates()->active()->findOrFail($employee);
        $period = PromotionData::currentPeriodModel() ?? abort(404);
        $submit = $request->validated('action') === 'submit';

        $assessment = $service->save($request->user(), $target, $period, $request->scores(), $request->validated('notes'), $submit);
        Activity::log(
            $submit ? 'penilaian.kirim' : 'penilaian.draft',
            ($submit ? 'Mengirim penilaian ' : 'Menyimpan draft penilaian ') . $target->name . '.',
            $assessment, $period, ['nilai' => array_filter($request->scores(), fn ($v) => $v !== null)],
        );

        return $submit
            ? to_route('manager.assessments')->with('success', __('Penilaian :name dikirim ke HRD.', ['name' => $target->name]))
            : to_route('manager.assessments.edit', $target->id)->with('success', __('Draft penilaian disimpan.'));
    }

    public function ranking(Request $request): View
    {
        $teamIds = $request->user()->subordinates()->pluck('id')->all();

        return $this->rankingView($request, null, 'team', $teamIds);
    }
}
