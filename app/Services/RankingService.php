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
                throw ValidationException::withMessages(['saw' => __('Ranking hanya bisa dihitung pada periode yang sedang berlangsung.')]);
            }

            $criteria = Criterion::orderBy('sort_order')->get();
            $weights = $period->weights()->pluck('weight_value', 'criteria_id');
            $types = $period->weights()->pluck('attribute_type', 'criteria_id');
            $total = (int) $weights->sum();
            if ($total !== 100 || $weights->count() !== $criteria->count()) {
                throw ValidationException::withMessages(['saw' => __('Total bobot kriteria masih :total%. Perbaiki di halaman Kriteria & bobot sebelum menghitung.', ['total' => $total])]);
            }

            $assessments = Assessment::query()
                ->where('period_id', $period->id)
                ->where('status', Assessment::SUBMITTED)
                ->whereHas('employee', fn ($q) => $q->where('is_active', true))
                ->with(['employee:id,join_date', 'details'])
                ->get();

            if ($assessments->isEmpty()) {
                throw ValidationException::withMessages(['saw' => __('Belum ada penilaian yang dikirim atasan. Ranking bisa dihitung setelah minimal satu penilaian masuk.')]);
            }

            $now = now();

            // 1. Kriteria otomatis (masa kerja) dihitung ulang sesuai akhir periode.
            //    Disimpan massal (upsert per 500 baris): satu-per-satu = ribuan query saat karyawannya ribuan.
            foreach ($criteria->filter->isAuto() as $auto) {
                $rows = $assessments->map(fn ($a) => [
                    'assessment_id' => $a->id,
                    'criteria_id' => $auto->id,
                    'raw_score' => $a->employee->tenureAt($period->end_date),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();
                foreach (array_chunk($rows, 500) as $chunk) {
                    AssessmentDetail::upsert($chunk, ['assessment_id', 'criteria_id'], ['raw_score', 'updated_at']);
                }

                $fresh = AssessmentDetail::where('criteria_id', $auto->id)
                    ->whereIn('assessment_id', Assessment::select('id')->where('period_id', $period->id)->where('status', Assessment::SUBMITTED))
                    ->get()
                    ->keyBy('assessment_id');
                foreach ($assessments as $a) {
                    $a->setRelation('details', $a->details->where('criteria_id', '!=', $auto->id)->push($fresh[$a->id]));
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
                'type' => $types[$c->id] ?? $c->attribute_type,
            ])->all();

            $results = $this->saw->calculate($input, $matrix);

            // 3. Simpan normalisasi per detail (jejak perhitungan).
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
            // Dicicil per 500 baris: satu query raksasa melewati batas placeholder database
            // (SQLite ±32 ribu, MySQL 65.535) begitu karyawannya ribuan.
            foreach (array_chunk($normalizedRows, 500) as $chunk) {
                NormalizedScore::upsert($chunk, ['assessment_detail_id'], ['normalized_value', 'weighted_value', 'updated_at']);
            }

            // 4. Simpan ranking (massal). Keputusan direktur yang sudah final tidak diubah.
            $existing = RankingResult::where('period_id', $period->id)->get()->keyBy('employee_id');
            $rankingRows = [];
            foreach ($results as $employeeId => $r) {
                $old = $existing[$employeeId] ?? null;
                $recommended = $r['rank'] <= PromotionData::RECOMMENDED_COUNT;

                if ($old?->isDecided()) {
                    $status = $old->approval_status;
                } else {
                    // Termasuk 'revision': setelah HRD meninjau & menghitung ulang, kandidat kembali menunggu keputusan.
                    $status = $recommended ? RankingResult::PENDING : null;
                }
                $keepApprover = $old && $old->approval_status === $status;

                $rankingRows[] = [
                    'employee_id' => $employeeId,
                    'period_id' => $period->id,
                    'final_score' => $r['score'],
                    'rank' => $r['rank'],
                    'is_recommended' => $recommended,
                    'approval_status' => $status,
                    'approved_by' => $keepApprover ? $old->approved_by : null,
                    'approved_at' => $keepApprover ? $old->getRawOriginal('approved_at') : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            foreach (array_chunk($rankingRows, 500) as $chunk) {
                RankingResult::upsert($chunk, ['period_id', 'employee_id'],
                    ['final_score', 'rank', 'is_recommended', 'approval_status', 'approved_by', 'approved_at', 'updated_at']);
            }

            // Yang tidak lagi ikut perhitungan dihapus dari ranking, kecuali sudah diputuskan direktur.
            RankingResult::where('period_id', $period->id)
                ->whereNotIn('employee_id', array_keys($results))
                ->where(fn ($q) => $q->whereNull('approval_status')->orWhereIn('approval_status', [RankingResult::PENDING, RankingResult::REVISION]))
                ->delete();

            $period->forceFill(['calculated_at' => $now])->save();

            return count($results);
        });
    }
}
