<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Concerns\EnforcesIdleTimeout;
use App\Http\Controllers\Controller;
use App\Queries\PromotionData;
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

        $period = PromotionData::currentPeriod();
        if (! $period) {
            return view('shared.no-period');
        }

        $me = $request->user()->id;
        $next = PromotionData::employeeQuery($period, ['supervisor_id' => $me, 'status' => 'draft'])->first()
            ?? PromotionData::employeeQuery($period, ['supervisor_id' => $me, 'status' => 'pending'])->first();

        return view('manager.dashboard', [
            'period' => $period,
            'open' => PromotionData::isOpen($period),
            'counts' => PromotionData::statusCounts($period, $me),
            'next' => $next ? PromotionData::employeeArray($next, $period) : null,
            'recent' => PromotionData::employeeQuery($period, ['supervisor_id' => $me])->limit(8)->get()
                ->map(fn ($e) => PromotionData::employeeArray($e, $period)),
            'ranking' => PromotionData::ranking($period, ['employee_ids' => $request->user()->subordinates()->pluck('id')->all()], limit: 5),
        ]);
    }
}
