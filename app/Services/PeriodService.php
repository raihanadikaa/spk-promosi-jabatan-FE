<?php

namespace App\Services;

use App\Models\AssessmentPeriod;
use App\Models\CriteriaWeight;
use App\Models\Criterion;
use App\Models\RankingResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Siklus periode: draft → berlangsung → ditutup. Hanya satu periode yang boleh berlangsung.
 */
class PeriodService
{
    public function create(array $data): AssessmentPeriod
    {
        $overlap = AssessmentPeriod::where('start_date', '<=', $data['end_date'])
            ->where('end_date', '>=', $data['start_date'])
            ->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['start_date' => 'Rentang tanggal bertumpuk dengan periode lain.']);
        }

        return DB::transaction(function () use ($data) {
            $source = AssessmentPeriod::orderByDesc('start_date')->first();

            $period = AssessmentPeriod::create([
                'period_name' => $data['period_name'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'assessment_deadline' => $data['assessment_deadline'],
                'status' => AssessmentPeriod::DRAFT,
            ]);

            // Bobot disalin dari periode terakhir; jika belum ada, dibagi rata lalu sisa ke kriteria pertama.
            $previous = $source ? $source->weights()->pluck('weight_value', 'criteria_id') : collect();
            $criteria = Criterion::orderBy('sort_order')->pluck('id');
            $even = intdiv(100, max(1, $criteria->count()));
            foreach ($criteria as $i => $criteriaId) {
                CriteriaWeight::create([
                    'period_id' => $period->id,
                    'criteria_id' => $criteriaId,
                    'weight_value' => $previous[$criteriaId] ?? ($even + ($i === 0 ? 100 - $even * $criteria->count() : 0)),
                ]);
            }

            return $period;
        });
    }

    public function activate(AssessmentPeriod $period): void
    {
        DB::transaction(function () use ($period) {
            if ($period->status !== AssessmentPeriod::DRAFT) {
                throw ValidationException::withMessages(['period' => 'Hanya periode berstatus draft yang bisa dimulai.']);
            }
            if (AssessmentPeriod::ongoing()->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['period' => 'Masih ada periode yang berlangsung. Tutup periode itu dulu.']);
            }

            $period->update(['status' => AssessmentPeriod::ONGOING]);
        });
    }

    public function close(AssessmentPeriod $period): void
    {
        DB::transaction(function () use ($period) {
            $period = AssessmentPeriod::whereKey($period->id)->lockForUpdate()->firstOrFail();

            if (! $period->isOngoing()) {
                throw ValidationException::withMessages(['period' => 'Periode ini tidak sedang berlangsung.']);
            }
            if (! $period->calculated_at) {
                throw ValidationException::withMessages(['period' => 'Ranking belum dihitung. Hitung ranking dulu sebelum menutup periode.']);
            }

            $undecided = RankingResult::where('period_id', $period->id)
                ->where('approval_status', RankingResult::PENDING)
                ->count();
            if ($undecided > 0) {
                throw ValidationException::withMessages(['period' => "Masih ada {$undecided} rekomendasi yang belum diputuskan direktur."]);
            }

            $period->update(['status' => AssessmentPeriod::CLOSED]);
        });
    }
}
