<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ShowsRanking;
use App\Queries\PromotionData;
use App\Services\SensitivityAnalysis;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Analisis sensitivitas bobot (HRD dan direktur). Hanya simulasi: tidak mengubah bobot maupun ranking tersimpan.
 */
class SensitivityController extends Controller
{
    use ShowsRanking;

    public function index(Request $request, SensitivityAnalysis $analysis): View
    {
        $period = PromotionData::currentPeriod();
        if (! $period) {
            return $this->noPeriodView();
        }

        $criteria = PromotionData::criteria($period['id']);
        $codes = array_column($criteria, 'code');

        $custom = null;
        if ($request->has('bobot')) {
            $validated = $request->validate([
                'bobot' => ['required', 'array'],
                'bobot.*' => ['required', 'numeric', 'min:0', 'max:100'],
            ], [], ['bobot.*' => 'bobot']);
            $custom = [];
            foreach ($codes as $code) {
                $custom[$code] = round((float) ($validated['bobot'][$code] ?? 0), 2);
            }
            if (abs(array_sum($custom) - 100) > 0.01) {
                throw ValidationException::withMessages(['bobot' => __('Total bobot simulasi harus 100% (sekarang ') . rtrim(rtrim(number_format(array_sum($custom), 2, ',', ''), '0'), ',') . '%).']);
            }
        }

        return view('shared.sensitivity', [
            'period' => $period,
            'result' => $analysis->analyze($period, $custom),
            'stale' => PromotionData::staleReasons($period),
            'formRoute' => $request->route()->getName(),
            'detailRoute' => str_replace('sensitivity', 'ranking.show', $request->route()->getName()),
        ]);
    }
}
