<?php

namespace App\Services;

use App\Models\RankingResult;
use App\Models\User;
use App\Notifications\RevisionRequested;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Direktur menyetujui, menolak, atau meminta review ulang rekomendasi promosi.
 */
class DecisionService
{
    public const APPROVE = 'approve';
    public const REJECT = 'reject';
    public const REVISION = 'revision';

    public function decide(User $director, RankingResult $result, string $decision, ?string $note): void
    {
        $status = match ($decision) {
            self::APPROVE => RankingResult::APPROVED,
            self::REJECT => RankingResult::REJECTED,
            self::REVISION => RankingResult::REVISION,
            default => throw ValidationException::withMessages(['decision' => 'Keputusan tidak dikenal.']),
        };

        if ($decision === self::REJECT && blank($note)) {
            throw ValidationException::withMessages(['note' => 'Tulis alasan penolakan supaya HRD dan karyawan bisa menindaklanjuti.']);
        }
        if ($decision === self::REVISION && blank($note)) {
            throw ValidationException::withMessages(['note' => 'Tulis apa yang perlu ditinjau ulang oleh HRD.']);
        }

        $result = DB::transaction(function () use ($director, $result, $status, $note) {
            $result = RankingResult::whereKey($result->id)->with('period')->lockForUpdate()->firstOrFail();

            if (! $result->period->isOngoing()) {
                throw ValidationException::withMessages(['note' => 'Periode sudah ditutup; keputusan tidak bisa diubah.']);
            }
            if (! $result->is_recommended) {
                throw ValidationException::withMessages(['note' => 'Karyawan ini tidak termasuk rekomendasi promosi pada ranking terbaru.']);
            }
            if ($result->approval_status !== RankingResult::PENDING) {
                throw ValidationException::withMessages(['note' => $result->approval_status === RankingResult::REVISION
                    ? 'Rekomendasi ini sedang ditinjau ulang HRD. Tunggu ranking dihitung ulang.'
                    : 'Rekomendasi ini sudah diputuskan.']);
            }

            $result->update([
                'approval_status' => $status,
                'approval_note' => $note,
                'approved_by' => $director->id,
                'approved_at' => now(),
            ]);

            return $result;
        });

        if ($status === RankingResult::REVISION) {
            $hrd = User::where('is_active', true)->whereHas('role', fn ($r) => $r->where('role_name', 'hrd'))->get();
            Notification::send($hrd, new RevisionRequested($result->load('employee:id,name'), $director->name));
        }
    }
}
