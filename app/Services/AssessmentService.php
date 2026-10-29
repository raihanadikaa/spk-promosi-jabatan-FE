<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentDetail;
use App\Models\AssessmentPeriod;
use App\Models\Criterion;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Atasan mengisi nilai bawahan: simpan draft atau kirim (terkunci).
 */
class AssessmentService
{
    /**
     * @param  array<string, int|float|null>  $scores  [kode kriteria => nilai], hanya kriteria non-otomatis
     */
    public function save(User $assessor, Employee $employee, AssessmentPeriod $period, array $scores, ?string $notes, bool $submit): Assessment
    {
        return DB::transaction(function () use ($assessor, $employee, $period, $scores, $notes, $submit) {
            $period = AssessmentPeriod::whereKey($period->id)->lockForUpdate()->firstOrFail();

            if (! $period->isOngoing()) {
                throw ValidationException::withMessages(['period' => 'Periode penilaian tidak sedang berlangsung, nilai tidak bisa diubah.']);
            }
            if ($employee->supervisor_id !== $assessor->id || ! $employee->is_active) {
                throw ValidationException::withMessages(['employee' => 'Anda hanya bisa menilai bawahan langsung yang aktif.']);
            }

            $assessment = Assessment::firstOrNew(['period_id' => $period->id, 'employee_id' => $employee->id]);
            if ($assessment->isSubmitted()) {
                throw ValidationException::withMessages(['employee' => 'Penilaian ini sudah dikirim dan terkunci. Hubungi HRD jika perlu koreksi.']);
            }

            $assessment->fill([
                'assessor_id' => $assessor->id,
                'status' => $submit ? Assessment::SUBMITTED : Assessment::DRAFT,
                'notes' => $notes,
                'submitted_at' => $submit ? now() : null,
            ])->save();

            foreach (Criterion::orderBy('sort_order')->get() as $c) {
                $value = $c->isAuto() ? $employee->tenureAt($period->end_date) : ($scores[$c->code] ?? null);

                if ($value === null) {
                    // Draft boleh belum lengkap; nilai yang dikosongkan dihapus.
                    AssessmentDetail::where(['assessment_id' => $assessment->id, 'criteria_id' => $c->id])->delete();
                    continue;
                }

                AssessmentDetail::updateOrCreate(
                    ['assessment_id' => $assessment->id, 'criteria_id' => $c->id],
                    ['raw_score' => $value],
                );
            }

            return $assessment;
        });
    }

    /** HRD membuka kembali penilaian terkirim supaya atasan bisa mengoreksi. */
    public function reopen(Assessment $assessment): void
    {
        if (! $assessment->period->isOngoing()) {
            throw ValidationException::withMessages(['assessment' => 'Penilaian di periode yang sudah ditutup tidak bisa dibuka kembali.']);
        }
        if (! $assessment->isSubmitted()) {
            throw ValidationException::withMessages(['assessment' => 'Penilaian ini belum dikirim.']);
        }

        $assessment->update(['status' => Assessment::DRAFT, 'submitted_at' => null]);
    }
}
