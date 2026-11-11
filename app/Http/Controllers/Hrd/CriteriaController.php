<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use App\Models\AssessmentDetail;
use App\Models\AssessmentPeriod;
use App\Models\CriteriaWeight;
use App\Models\Criterion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Tambah / ubah / hapus kriteria penilaian.
 *
 * Susunan kriteria (tambah, hapus, rentang nilai) hanya boleh berubah saat TIDAK ada periode yang berlangsung:
 * penilaian yang sudah diisi atasan memakai susunan itu, jadi mengubahnya di tengah periode membuat nilai tidak sebanding.
 * Nama dan keterangan boleh diubah kapan saja.
 */
class CriteriaController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $this->assertNoOngoingPeriod('menambah kriteria');
        $data = $this->validated($request);

        $criterion = DB::transaction(function () use ($data) {
            $max = (int) Criterion::lockForUpdate()->get()->max(fn ($c) => (int) preg_replace('/\D/', '', $c->code));
            $criterion = Criterion::create($data + [
                'code' => 'C' . ($max + 1),
                'sort_order' => (int) Criterion::max('sort_order') + 1,
            ]);

            // Periode yang belum ditutup mendapat bobot 0 untuk kriteria baru; HRD mengatur ulang sampai total 100%.
            foreach (AssessmentPeriod::where('status', '!=', AssessmentPeriod::CLOSED)->pluck('id') as $periodId) {
                CriteriaWeight::create(['period_id' => $periodId, 'criteria_id' => $criterion->id, 'weight_value' => 0]);
            }

            return $criterion;
        });

        return to_route('hrd.criteria')->with('success', "Kriteria {$criterion->criteria_name} ({$criterion->code}) ditambahkan dengan bobot 0%. Atur ulang bobot sampai total 100%.");
    }

    public function update(Request $request, Criterion $criterion): RedirectResponse
    {
        $data = $this->validated($request, $criterion);

        if (AssessmentPeriod::ongoing()->exists() || $criterion->isAuto()) {
            // Di tengah periode (atau kriteria otomatis): hanya nama & keterangan.
            $data = ['criteria_name' => $data['criteria_name'], 'description' => $data['description']];
        }
        $criterion->update($data);

        return to_route('hrd.criteria')->with('success', "Kriteria {$criterion->criteria_name} diperbarui.");
    }

    public function destroy(Criterion $criterion): RedirectResponse
    {
        $this->assertNoOngoingPeriod('menghapus kriteria');

        if ($criterion->isAuto()) {
            throw ValidationException::withMessages(['criteria' => 'Kriteria yang dihitung otomatis (masa kerja) tidak bisa dihapus.']);
        }
        if (AssessmentDetail::where('criteria_id', $criterion->id)->exists()) {
            throw ValidationException::withMessages(['criteria' => "Kriteria {$criterion->criteria_name} sudah dipakai di penilaian, jadi tidak bisa dihapus agar riwayat tetap utuh."]);
        }
        if (Criterion::count() <= 2) {
            throw ValidationException::withMessages(['criteria' => 'Minimal harus ada dua kriteria.']);
        }

        $criterion->delete(); // bobotnya ikut terhapus (cascade)

        return to_route('hrd.criteria')->with('success', "Kriteria {$criterion->criteria_name} dihapus. Atur ulang bobot sampai total 100%.");
    }

    private function assertNoOngoingPeriod(string $action): void
    {
        if (AssessmentPeriod::ongoing()->exists()) {
            throw ValidationException::withMessages(['criteria' => "Tidak bisa {$action} saat ada periode yang berlangsung. Lakukan setelah periode ditutup, sebelum periode berikutnya dimulai."]);
        }
    }

    private function validated(Request $request, ?Criterion $criterion = null): array
    {
        return $request->validate([
            'criteria_name' => ['required', 'string', 'max:60'],
            'description' => ['required', 'string', 'max:255'],
            'attribute_type' => [$criterion ? 'sometimes' : 'required', 'in:benefit,cost'],
            'input_type' => [$criterion ? 'sometimes' : 'required', 'in:score,count'],
            'unit' => [$criterion ? 'sometimes' : 'required', 'string', 'max:30'],
            'min_value' => [$criterion ? 'sometimes' : 'required', 'integer', 'min:0', 'max:65535'],
            'max_value' => [$criterion ? 'sometimes' : 'required', 'integer', 'gt:min_value', 'max:65535'],
        ], [
            'max_value.gt' => 'Nilai maksimal harus lebih besar dari nilai minimal.',
        ], [
            'criteria_name' => 'nama kriteria', 'description' => 'cara menilai', 'attribute_type' => 'jenis',
            'input_type' => 'bentuk nilai', 'unit' => 'satuan', 'min_value' => 'nilai minimal', 'max_value' => 'nilai maksimal',
        ]);
    }
}
