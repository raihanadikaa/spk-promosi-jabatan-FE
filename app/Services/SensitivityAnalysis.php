<?php

namespace App\Services;

use App\Models\AssessmentDetail;
use App\Models\Employee;
use App\Queries\PromotionData;
use Illuminate\Support\Facades\Cache;

/**
 * Analisis sensitivitas bobot SAW: seberapa stabil hasil ranking jika bobot kriteria digeser.
 *
 * Cara menggeser: bobot satu kriteria dinaikkan/diturunkan d poin persen, lalu selisihnya dibagi ke kriteria lain
 * sebanding bobot asalnya, sehingga total tetap 100%. Nilai mentah tidak diubah; hanya bobot.
 * Semua dihitung di memori dari nilai mentah peserta ranking terakhir; tidak ada data yang disimpan.
 */
class SensitivityAnalysis
{
    /** Pergeseran bobot (poin persen) yang ditampilkan di tabel skenario. */
    public const DELTAS = [-20, -10, -5, 5, 10, 20];

    /** Batas pencarian titik kritis (poin persen). */
    public const MAX_SHIFT = 30;

    public function __construct(private SawCalculator $saw) {}

    /**
     * @param  array{id:int, calculated_at_raw:?string}  $period
     * @param  array<string, float>|null  $customWeights  [kode => persen], total 100; null = tanpa simulasi bobot sendiri
     */
    public function analyze(array $period, ?array $customWeights = null): array
    {
        $criteria = PromotionData::criteria($period['id']);
        $weights = array_column($criteria, 'weight', 'code');
        $matrix = $this->matrix($period, array_column($criteria, 'code', 'id'));
        $top = PromotionData::RECOMMENDED_COUNT;

        if (count($matrix) < 2 || array_sum($weights) !== 100) {
            return ['ready' => false, 'participants' => count($matrix), 'weights' => $weights, 'criteria' => $criteria];
        }

        // Skenario & titik kritis tidak bergantung pada simulasi bobot sendiri → disimpan di cache per ranking + bobot.
        $key = 'sensitivitas.' . $period['id'] . '.' . md5(($period['calculated_at_raw'] ?? '') . json_encode($weights) . json_encode(array_column($criteria, 'type', 'code')));
        // Normalisasi (r) tidak bergantung pada bobot, jadi cukup dihitung sekali; tiap skenario hanya menjumlah ulang w·r.
        // Dihitung hanya bila perlu (cache kosong atau ada simulasi bobot sendiri).
        $normalized = null;
        $r = function () use (&$normalized, $criteria, $matrix) {
            return $normalized ??= $this->normalize($criteria, $matrix);
        };

        $analysis = Cache::remember($key, now()->addMinutes(30), function () use ($criteria, $weights, $r, $top) {
            $normalized = $r();
            $baseline = $this->run($weights, $normalized);
            $baseTop = array_slice(array_keys($baseline), 0, $top);

            $scenarios = [];
            $thresholds = [];
            foreach ($criteria as $c) {
                foreach (self::DELTAS as $d) {
                    $shifted = $this->shift($weights, $c['code'], $d);
                    $scenarios[$c['code']][$d] = $shifted === null ? null : $this->compare($baseline, $this->run($shifted, $normalized), $top);
                }
                $thresholds[$c['code']] = [
                    'down' => $this->criticalShift($weights, $normalized, $c['code'], -1, $baseTop),
                    'up' => $this->criticalShift($weights, $normalized, $c['code'], 1, $baseTop),
                ];
            }

            return [
                'baseline_top' => $baseTop,
                // Skor peringkat terakhir yang direkomendasikan & peringkat sesudahnya: makin tipis selisihnya, makin mudah tergeser.
                'margin' => count($baseline) > $top
                    ? round($baseline[$baseTop[$top - 1]]['score'] - array_values($baseline)[$top]['score'], 4)
                    : null,
                'scenarios' => $scenarios,
                'thresholds' => $thresholds,
            ];
        });

        $tested = collect($analysis['scenarios'])->flatten(1)->filter();
        $stable = $tested->where('same_set', true)->count();

        $custom = null;
        if ($customWeights !== null) {
            $baseline = $this->run($weights, $r());
            $result = $this->run($customWeights, $r());
            $custom = $this->compare($baseline, $result, $top) + [
                'weights' => $customWeights,
                'rows' => $this->customRows($baseline, $result, 10),
            ];
        }

        $names = Employee::whereIn('id', array_unique(array_merge(
            $analysis['baseline_top'],
            $tested->pluck('top')->flatten()->all(),
            $custom ? array_column($custom['rows'], 'id') : [],
        )))->pluck('name', 'id')->all();

        return [
            'ready' => true,
            'participants' => count($matrix),
            'criteria' => $criteria,
            'weights' => $weights,
            'baseline_top' => $analysis['baseline_top'],
            'margin' => $analysis['margin'],
            'scenarios' => $analysis['scenarios'],
            'thresholds' => $analysis['thresholds'],
            'stable' => $stable,
            'tested' => $tested->count(),
            'custom' => $custom,
            'names' => $names,
        ];
    }

    /**
     * Nilai mentah peserta ranking terakhir: [employee_id => [kode => nilai]].
     *
     * @param  array<int, string>  $codeById
     */
    private function matrix(array $period, array $codeById): array
    {
        $matrix = [];
        AssessmentDetail::query()
            ->join('assessments', 'assessments.id', '=', 'assessment_details.assessment_id')
            ->join('ranking_results', fn ($j) => $j->on('ranking_results.employee_id', '=', 'assessments.employee_id')
                ->on('ranking_results.period_id', '=', 'assessments.period_id'))
            ->where('assessments.period_id', $period['id'])
            ->select('assessment_details.id', 'assessments.employee_id', 'assessment_details.criteria_id', 'assessment_details.raw_score')
            ->toBase()
            // Dibaca per 2.000 baris berdasarkan id detail (unik): urutan pasti, tidak ada baris terbaca dua kali/terlewat,
            // dan tiap halaman langsung loncat lewat indeks (bukan OFFSET yang makin lambat di halaman belakang).
            ->lazyById(2000, 'assessment_details.id', 'id')
            ->each(function ($r) use (&$matrix, $codeById) {
                if (isset($codeById[$r->criteria_id])) {
                    $matrix[$r->employee_id][$codeById[$r->criteria_id]] = (float) $r->raw_score;
                }
            });

        // Hanya baris lengkap yang bisa dihitung (sama seperti proses SAW utama).
        $count = count($codeById);

        return array_filter($matrix, fn ($row) => count($row) === $count);
    }

    /**
     * Matriks ternormalisasi R memakai rumus SAW yang sama dengan perhitungan utama.
     *
     * @return array<int, array<string, float>>
     */
    private function normalize(array $criteria, array $matrix): array
    {
        $input = array_map(fn ($c) => ['code' => $c['code'], 'weight' => 0.0, 'type' => $c['type']], $criteria);
        $normalized = [];
        // Urutan baris dijaga (urut id karyawan) supaya skor seri diurutkan sama di setiap skenario.
        $result = $this->saw->calculate($input, $matrix);
        foreach (array_keys($matrix) as $id) {
            $normalized[$id] = $result[$id]['normalized'];
        }

        return $normalized;
    }

    /**
     * V = Σ w·r untuk semua karyawan, lalu diurutkan (pembulatan sama dengan SawCalculator).
     *
     * @return array<int, array{score: float, rank: int}> urut peringkat
     */
    private function run(array $weights, array $normalized): array
    {
        $fractions = array_map(fn ($w) => $w / 100, $weights);
        $scores = [];
        foreach ($normalized as $id => $row) {
            $sum = 0.0;
            foreach ($fractions as $code => $w) {
                $sum += round($row[$code] * $w, 4);
            }
            $scores[$id] = round($sum, 4);
        }
        arsort($scores); // stabil: skor seri tetap urut seperti matriks

        $ranked = [];
        $rank = 0;
        foreach ($scores as $id => $score) {
            $ranked[$id] = ['score' => $score, 'rank' => ++$rank];
        }

        return $ranked;
    }

    /**
     * Geser bobot satu kriteria sebesar $delta poin; sisanya dibagi proporsional ke kriteria lain.
     *
     * @return array<string, float>|null null jika pergeseran tidak mungkin (keluar dari 0–100 atau kriteria lain semuanya 0)
     */
    public function shift(array $weights, string $code, float $delta): ?array
    {
        $target = $weights[$code] + $delta;
        $others = array_sum($weights) - $weights[$code];
        if ($target < 0 || $target > 100 || $others <= 0) {
            return null;
        }

        $scale = (100 - $target) / $others;
        $shifted = [];
        foreach ($weights as $c => $w) {
            $shifted[$c] = $c === $code ? (float) $target : $w * $scale;
        }

        return $shifted;
    }

    private function compare(array $baseline, array $result, int $top): array
    {
        $baseTop = array_slice(array_keys($baseline), 0, $top);
        $newTop = array_slice(array_keys($result), 0, $top);
        $moved = 0;
        $maxShift = 0;
        foreach ($result as $id => $r) {
            $diff = abs($r['rank'] - $baseline[$id]['rank']);
            if ($diff > 0) {
                $moved++;
                $maxShift = max($maxShift, $diff);
            }
        }

        return [
            'top' => $newTop,
            'same_set' => ! array_diff($baseTop, $newTop) && count($baseTop) === count($newTop),
            'same_order' => $baseTop === $newTop,
            'moved' => $moved,
            'max_shift' => $maxShift,
        ];
    }

    /**
     * Pergeseran terkecil (1..MAX_SHIFT poin, ke satu arah) yang mengubah susunan kandidat yang direkomendasikan.
     * null = tetap sama sampai batas pencarian atau sampai bobot mentok 0/100.
     */
    private function criticalShift(array $weights, array $normalized, string $code, int $direction, array $baseTop): ?int
    {
        $top = count($baseTop);
        for ($step = 1; $step <= self::MAX_SHIFT; $step++) {
            $shifted = $this->shift($weights, $code, $direction * $step);
            if ($shifted === null) {
                return null;
            }
            $newTop = array_slice(array_keys($this->run($shifted, $normalized)), 0, $top);
            if (array_diff($baseTop, $newTop)) {
                return $step;
            }
        }

        return null;
    }

    /** Sepuluh teratas hasil simulasi beserta peringkat aslinya. */
    private function customRows(array $baseline, array $result, int $limit): array
    {
        $rows = [];
        foreach (array_slice($result, 0, $limit, true) as $id => $r) {
            $rows[] = [
                'id' => $id,
                'rank' => $r['rank'],
                'score' => $r['score'],
                'base_rank' => $baseline[$id]['rank'],
                'base_score' => $baseline[$id]['score'],
            ];
        }

        return $rows;
    }
}
