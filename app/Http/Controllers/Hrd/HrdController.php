<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Concerns\ShowsRanking;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Http\Requests\StorePeriodRequest;
use App\Http\Requests\UpdateWeightsRequest;
use App\Models\Assessment;
use App\Models\AssessmentPeriod;
use App\Models\CriteriaWeight;
use App\Models\Criterion;
use App\Models\Employee;
use App\Models\User;
use App\Queries\PromotionData;
use App\Services\AssessmentService;
use App\Services\EmployeeImportService;
use App\Services\PeriodService;
use App\Services\RankingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HrdController extends Controller
{
    use ShowsRanking;

    /* ---------------- Karyawan ---------------- */

    public function employees(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:pending,draft,submitted,unfinished'],
            'nonaktif' => ['nullable', 'boolean'],
        ]);
        $filters = [
            'q' => $validated['q'] ?? null,
            'department_id' => isset($validated['department']) ? (int) $validated['department'] : null,
            'status' => $validated['status'] ?? null,
            'with_inactive' => (bool) ($validated['nonaktif'] ?? false),
        ];

        return view('hrd.employees', [
            'employees' => PromotionData::employeesPage(PromotionData::currentPeriod(), $filters),
            'filters' => $filters,
            'departments' => PromotionData::departments(),
            'positions' => PromotionData::positions(),
            'managers' => User::whereHas('role', fn ($r) => $r->where('role_name', 'manager'))
                ->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all(),
            'portalAccounts' => User::whereHas('role', fn ($r) => $r->where('role_name', 'karyawan'))
                ->orderBy('name')->get(['id', 'name', 'email'])->all(),
            'linkedAccounts' => Employee::whereNotNull('user_id')->pluck('id', 'user_id')->all(),
        ]);
    }

    public function storeEmployee(EmployeeRequest $request): RedirectResponse
    {
        $employee = Employee::create($request->validated());

        return to_route('hrd.employees')->with('success', "{$employee->name} ditambahkan.");
    }

    public function updateEmployee(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated() + ['is_active' => $request->boolean('is_active')]);

        return to_route('hrd.employees')->with('success', "Data {$employee->name} diperbarui.");
    }

    /** Impor banyak karyawan sekaligus dari CSV. Semua baris harus valid, kalau tidak dibatalkan seluruhnya. */
    public function importEmployees(Request $request, EmployeeImportService $import): RedirectResponse
    {
        $request->validate(
            ['file' => ['required', 'file', 'max:1024', 'mimes:csv,txt']],
            ['file.mimes' => 'Berkas harus CSV.', 'file.max' => 'Ukuran berkas maksimal 1 MB.'],
            ['file' => 'berkas'],
        );

        $count = $import->import($request->file('file')->getRealPath());

        return to_route('hrd.employees')->with('success', "{$count} karyawan ditambahkan dari berkas.");
    }

    public function employeeTemplate(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, EmployeeImportService::HEADER, ';');
            fputcsv($out, ['Contoh Nama', '3201990101', array_values(PromotionData::departments())[0] ?? 'Operasional', array_values(PromotionData::positions())[0] ?? 'Staf', '2022-01-17', ''], ';');
            fclose($out);
        }, 'templat-karyawan.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /* ---------------- Periode ---------------- */

    public function periods(): View
    {
        $stats = Assessment::query()
            ->selectRaw('period_id, status, count(*) as total')
            ->groupBy('period_id', 'status')
            ->get()
            ->groupBy('period_id')
            ->map(fn ($rows) => $rows->pluck('total', 'status'));

        return view('hrd.periods', [
            'periods' => PromotionData::periods(),
            'stats' => $stats,
            'activeEmployees' => Employee::active()->count(),
        ]);
    }

    public function storePeriod(StorePeriodRequest $request, PeriodService $periods): RedirectResponse
    {
        $period = $periods->create($request->validated());

        return to_route('hrd.periods')->with('success', "Periode {$period->period_name} dibuat sebagai draft.");
    }

    public function activatePeriod(AssessmentPeriod $period, PeriodService $periods): RedirectResponse
    {
        $periods->activate($period);

        return to_route('hrd.periods')->with('success', "Periode {$period->period_name} dimulai. Atasan sudah bisa mengisi nilai.");
    }

    public function closePeriod(AssessmentPeriod $period, PeriodService $periods): RedirectResponse
    {
        $periods->close($period);

        return to_route('hrd.periods')->with('success', "Periode {$period->period_name} ditutup. Nilai dan bobot sekarang terkunci.");
    }

    public function updatePeriod(StorePeriodRequest $request, AssessmentPeriod $period, PeriodService $periods): RedirectResponse
    {
        $periods->update($period, $request->validated());

        return to_route('hrd.periods')->with('success', "Periode {$period->period_name} diperbarui.");
    }

    public function destroyPeriod(AssessmentPeriod $period, PeriodService $periods): RedirectResponse
    {
        $periods->delete($period);

        return to_route('hrd.periods')->with('success', "Periode {$period->period_name} dihapus.");
    }

    /* ---------------- Kriteria & bobot ---------------- */

    public function criteria(): View
    {
        $model = PromotionData::weightsPeriodModel();
        if (! $model) {
            return $this->noPeriodView();
        }
        $period = PromotionData::periodArray($model);

        return view('hrd.criteria', [
            'criteria' => PromotionData::criteria($period['id']),
            'period' => $period,
            'editable' => $period['status'] !== AssessmentPeriod::CLOSED,
            'previousName' => $model->previous()?->period_name,
        ]);
    }

    public function updateWeights(UpdateWeightsRequest $request): RedirectResponse
    {
        $period = PromotionData::weightsPeriodModel();
        if (! $period || $period->status === AssessmentPeriod::CLOSED) {
            throw ValidationException::withMessages(['criteria' => 'Bobot periode yang sudah ditutup tidak bisa diubah.']);
        }

        DB::transaction(function () use ($request, $period) {
            foreach (Criterion::all() as $c) {
                $input = $request->validated("criteria.{$c->code}");

                $weight = CriteriaWeight::firstOrNew(['period_id' => $period->id, 'criteria_id' => $c->id]);
                $weight->weight_value = (int) $input['weight'];

                if ($c->attribute_type !== $input['type']) {
                    $c->update(['attribute_type' => $input['type']]);
                    $weight->updated_at = now(); // jenis berubah → ranking perlu dihitung ulang
                }
                $weight->save();
            }
        });

        return to_route('hrd.criteria')->with('success', 'Bobot kriteria disimpan. Hitung ulang ranking agar hasilnya ikut berubah.');
    }

    /** Salin bobot dari periode sebelumnya ke periode yang sedang diatur. */
    public function copyWeights(): RedirectResponse
    {
        $period = PromotionData::weightsPeriodModel();
        if (! $period || $period->status === AssessmentPeriod::CLOSED) {
            throw ValidationException::withMessages(['criteria' => 'Bobot periode yang sudah ditutup tidak bisa diubah.']);
        }
        $previous = $period->previous();
        $weights = $previous?->weights()->pluck('weight_value', 'criteria_id') ?? collect();
        if ($weights->isEmpty()) {
            throw ValidationException::withMessages(['criteria' => 'Belum ada periode sebelumnya yang bobotnya bisa disalin.']);
        }

        DB::transaction(function () use ($period, $weights) {
            foreach (Criterion::pluck('id') as $criteriaId) {
                CriteriaWeight::updateOrCreate(
                    ['period_id' => $period->id, 'criteria_id' => $criteriaId],
                    ['weight_value' => (int) ($weights[$criteriaId] ?? 0)],
                );
            }
        });

        return to_route('hrd.criteria')->with('success', "Bobot disalin dari {$previous->period_name}. Hitung ulang ranking agar hasilnya ikut berubah.");
    }

    /* ---------------- Proses SAW ---------------- */

    public function saw(Request $request): View
    {
        $period = PromotionData::currentPeriod();
        if (! $period) {
            return $this->noPeriodView();
        }

        $q = $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? null;
        $counts = PromotionData::statusCounts($period);
        $ranked = PromotionData::rankingCount($period);

        return view('hrd.saw', [
            'criteria' => PromotionData::criteria($period['id']),
            'ranking' => PromotionData::rankingPage($period, ['q' => $q]),
            'bounds' => PromotionData::bounds($period),
            'excluded' => max(0, $counts['total'] - $ranked),
            'ranked' => $ranked,
            'q' => $q,
            'period' => $period,
            'stale' => PromotionData::staleReasons($period),
        ]);
    }

    public function runSaw(RankingService $ranking): RedirectResponse
    {
        $period = PromotionData::currentPeriodModel()
            ?? throw ValidationException::withMessages(['saw' => 'Belum ada periode penilaian.']);

        $count = $ranking->calculate($period);

        return back()->with('success', "Ranking dihitung ulang dari {$count} penilaian terkirim.");
    }

    public function reopenAssessment(Assessment $assessment, AssessmentService $service): RedirectResponse
    {
        $service->reopen($assessment);

        return back()->with('success', "Penilaian {$assessment->employee->name} dibuka kembali untuk dikoreksi atasan.");
    }

    /* ---------------- Ranking ---------------- */

    public function ranking(Request $request): View
    {
        return $this->rankingView($request, 'hrd.ranking.show', 'all', exportRoute: 'hrd.ranking.export');
    }

    public function candidate(int $employee): View
    {
        return $this->candidateView($employee, ['url' => route('hrd.ranking'), 'label' => 'Ranking'], canDecide: false);
    }

    public function export(): StreamedResponse
    {
        return $this->exportCsv();
    }

    /**
     * Jejak perhitungan SAW lengkap untuk dicek ulang di Excel:
     * baris bobot W, lalu tiap alternatif: matriks X (mentah), R (ternormalisasi), dan V (Σ w·r).
     */
    public function exportMatrix(): StreamedResponse
    {
        $period = PromotionData::currentPeriod() ?? abort(404);
        $criteria = PromotionData::criteria($period['id']);
        $num = fn ($v, $d = 4) => number_format((float) $v, $d, ',', '');
        $safe = fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : (string) $v;

        return response()->streamDownload(function () use ($period, $criteria, $num, $safe) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            $codes = array_column($criteria, 'code');

            fputcsv($out, ['Periode', $safe($period['name'])], ';');
            fputcsv($out, array_merge(['Bobot W', '', ''], array_map(fn ($c) => $num($c['weight'] / 100, 2), $criteria), [''], array_map(fn ($c) => $c['type'], $criteria)), ';');
            fputcsv($out, array_merge(
                ['Alternatif', 'Nama', 'NIK'],
                array_map(fn ($c) => "X {$c}", $codes),
                [''],
                array_map(fn ($c) => "R {$c}", $codes),
                ['V (skor)', 'Peringkat'],
            ), ';');

            PromotionData::rankingQuery($period)->chunk(200, function ($results) use ($out, $period, $codes, $num, $safe) {
                foreach (PromotionData::rankingRows($results, $period) as $row) {
                    fputcsv($out, array_map($safe, array_merge(
                        ['A' . $row['rank'], $row['name'], $row['nik']],
                        array_map(fn ($c) => $num($row['raw'][$c] ?? 0, 2), $codes),
                        [''],
                        array_map(fn ($c) => $num($row['normalized'][$c] ?? 0), $codes),
                        [$num($row['score']), $row['rank']],
                    )), ';');
                }
            });
            fclose($out);
        }, 'matriks-saw-' . str($period['name'])->slug() . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
