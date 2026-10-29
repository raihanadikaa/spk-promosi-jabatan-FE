<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentDetail;
use App\Models\AssessmentPeriod;
use App\Models\Criterion;
use App\Models\NormalizedScore;
use App\Models\RankingResult;
use App\Queries\PromotionData;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Menghitung ranking SAW satu periode dan menyimpan seluruh jejaknya
 * (nilai mentah → normalized_scores → ranking_results) dalam satu transaksi.
 */
class RankingService
{
    public function __construct(private SawCalculator $saw) {}

    /**
     * @return int jumlah karyawan yang diranking
     */
    public function calculate(AssessmentPeriod $period): int
    {
        return DB::transaction(function () use ($period) {
            // Kunci baris periode: dua HRD yang menekan "Hitung" bersamaan tidak saling menimpa.
            $period = AssessmentPeriod::whereKey($period->id)->lockForUpdate()->firstOrFail();

            if (! $period->isOngoing()) {
                throw ValidationException::withMessages(['saw' => 'Ranking hanya bisa dihitung pada periode yang sedang berlangsung.']);
            }

            $criteria = Criterion::orderBy('sort_order')->get();
            $weights = $period->weights()->pluck('weight_value', 'criteria_id');
            $total = (int) $weights->sum();
            if ($total !== 100 || $weights->count() !== $criteria->count()) {
                throw ValidationException::withMessages(['saw' => "Total bobot kriteria masih {$total}%. Perbaiki di halaman Kriteria & bobot sebelum menghitung."]);
            }

            $assessments = Assessment::query()
                ->where('period_id', $period->id)
                ->where('status', Assessment::SUBMITTED)
                ->whereHas('employee', fn ($q) => $q->where('is_active', true))
                ->with(['employee:id,join_date', 'details'])
                ->get();

            if ($assessments->isEmpty()) {
                throw ValidationException::withMessages(['saw' => 'Belum ada penilaian yang dikirim atasan. Ranking bisa dihitung setelah minimal satu penilaian masuk.']);
            }

            // 1. Kriteria otomatis (masa kerja) dihitung ulang sesuai akhir periode.
            foreach ($criteria->filter->isAuto() as $auto) {
                foreach ($assessments as $a) {
                    $detail = AssessmentDetail::updateOrCreate(
                        ['assessment_id' => $a->id, 'criteria_id' => $auto->id],
                        ['raw_score' => $a->employee->tenureAt($period->end_date)],
                    );
                    $a->setRelation('details', $a->details->where('criteria_id', '!=', $auto->id)->push($detail));
                }
            }

            // 2. Matriks keputusan → SAW. Penilaian yang belum lengkap tidak ikut dihitung.
            $codeById = $criteria->pluck('code', 'id');
            $matrix = [];
            $detailIds = [];
            foreach ($assessments as $a) {
                if ($a->details->count() < $criteria->count()) {
                    continue;
                }
                foreach ($a->details as $d) {
                    $code = $codeById[$d->criteria_id];
                    $matrix[$a->employee_id][$code] = $d->raw_score;
                    $detailIds[$a->employee_id][$code] = $d->id;
                }
            }

            $input = $criteria->map(fn ($c) => [
                'code' => $c->code,
                'weight' => $weights[$c->id] / 100,
                'type' => $c->attribute_type,
            ])->all();

            $results = $this->saw->calculate($input, $matrix);

            // 3. Simpan normalisasi per detail (jejak perhitungan).
            $now = now();
            $normalizedRows = [];
            foreach ($results as $employeeId => $r) {
                foreach ($r['normalized'] as $code => $value) {
                    $normalizedRows[] = [
                        'assessment_detail_id' => $detailIds[$employeeId][$code],
                        'normalized_value' => $value,
                        'weighted_value' => $r['contributions'][$code],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            NormalizedScore::upsert($normalizedRows, ['assessment_detail_id'], ['normalized_value', 'weighted_value', 'updated_at']);

            // 4. Simpan ranking. Keputusan direktur yang sudah final tidak diubah.
            $existing = RankingResult::where('period_id', $period->id)->get()->keyBy('employee_id');
            foreach ($results as $employeeId => $r) {
                $row = $existing[$employeeId] ?? new RankingResult(['employee_id' => $employeeId, 'period_id' => $period->id]);
                $recommended = $r['rank'] <= PromotionData::RECOMMENDED_COUNT;

                $row->fill([
                    'final_score' => $r['score'],
                    'rank' => $r['rank'],
                    'is_recommended' => $recommended,
                ]);
                if (! $row->isDecided()) {
                    $row->approval_status = $recommended ? RankingResult::PENDING : null;
                }
                $row->save();
            }

            // Yang tidak lagi ikut perhitungan dihapus dari ranking, kecuali sudah diputuskan direktur.
            RankingResult::where('period_id', $period->id)
                ->whereNotIn('employee_id', array_keys($results))
                ->where(fn ($q) => $q->whereNull('approval_status')->orWhere('approval_status', RankingResult::PENDING))
                ->delete();

            $period->forceFill(['calculated_at' => $now])->save();

            return count($results);
        });
    }
}
