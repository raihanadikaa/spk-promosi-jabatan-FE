<?php

namespace App\Services;

use App\Models\RankingResult;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Direktur menyetujui atau menolak rekomendasi promosi.
 */
class DecisionService
{
    public function decide(User $director, RankingResult $result, bool $approve, ?string $note): void
    {
        if (! $approve && blank($note)) {
            throw ValidationException::withMessages(['note' => 'Tulis alasan penolakan supaya HRD dan karyawan bisa menindaklanjuti.']);
        }

        DB::transaction(function () use ($director, $result, $approve, $note) {
            $result = RankingResult::whereKey($result->id)->with('period')->lockForUpdate()->firstOrFail();

            if (! $result->period->isOngoing()) {
                throw ValidationException::withMessages(['note' => 'Periode sudah ditutup; keputusan tidak bisa diubah.']);
            }
            if (! $result->is_recommended) {
                throw ValidationException::withMessages(['note' => 'Karyawan ini tidak termasuk rekomendasi promosi pada ranking terbaru.']);
            }
            if ($result->isDecided()) {
                throw ValidationException::withMessages(['note' => 'Rekomendasi ini sudah diputuskan.']);
            }

            $result->update([
                'approval_status' => $approve ? RankingResult::APPROVED : RankingResult::REJECTED,
                'approval_note' => $note,
                'approved_by' => $director->id,
                'approved_at' => now(),
            ]);
        });
    }
}
