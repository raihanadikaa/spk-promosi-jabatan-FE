<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use App\Models\AssessmentDetail;
use App\Models\AssessmentPeriod;
use App\Models\CriteriaWeight;
use App\Models\Criterion;
use App\Support\Activity;
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
                CriteriaWeight::create(['period_id' => $periodId, 'criteria_id' => $criterion->id, 'weight_value' => 0, 'attribute_type' => $criterion->attribute_type]);
            }

            return $criterion;
        });

        Activity::log('kriteria.tambah', "Menambahkan kriteria {$criterion->code} {$criterion->criteria_name} ({$criterion->attribute_type}).", $criterion);

        return to_route('hrd.criteria')->with('success', __('Kriteria :criteria_name (:code) ditambahkan dengan bobot 0%. Atur ulang bobot sampai total 100%.', ['criteria_name' => $criterion->criteria_name, 'code' => $criterion->code]));
    }

    public function update(Request $request, Criterion $criterion): RedirectResponse
    {
        $data = $this->validated($request, $criterion);

        if (AssessmentPeriod::ongoing()->exists() || $criterion->isAuto()) {
            // Di tengah periode (atau kriteria otomatis): hanya nama & keterangan.
            $data = ['criteria_name' => $data['criteria_name'], 'description' => $data['description']];
        }
        $criterion->fill($data);
        $changes = collect($criterion->getDirty())->map(fn ($new, $field) => ['dari' => $criterion->getOriginal($field), 'ke' => $new])->all();
        $criterion->save();
        if (isset($changes['attribute_type'])) {
            // Hanya terjadi saat tidak ada periode berlangsung: periode draft ikut jenis baru, periode tertutup tetap.
            CriteriaWeight::where('criteria_id', $criterion->id)
                ->whereIn('period_id', AssessmentPeriod::where('status', AssessmentPeriod::DRAFT)->select('id'))
                ->update(['attribute_type' => $criterion->attribute_type, 'updated_at' => now()]);
        }
        if ($changes) {
            Activity::log('kriteria.ubah', "Mengubah kriteria {$criterion->code}: " . implode(', ', array_keys($changes)) . '.', $criterion, properties: $changes);
        }

        return to_route('hrd.criteria')->with('success', __('Kriteria :criteria_name diperbarui.', ['criteria_name' => $criterion->criteria_name]));
    }

    public function destroy(Criterion $criterion): RedirectResponse
    {
        $this->assertNoOngoingPeriod('menghapus kriteria');

        if ($criterion->isAuto()) {
            throw ValidationException::withMessages(['criteria' => __('Kriteria yang dihitung otomatis (masa kerja) tidak bisa dihapus.')]);
        }
        if (AssessmentDetail::where('criteria_id', $criterion->id)->exists()) {
            throw ValidationException::withMessages(['criteria' => __('Kriteria :criteria_name sudah dipakai di penilaian, jadi tidak bisa dihapus agar riwayat tetap utuh.', ['criteria_name' => $criterion->criteria_name])]);
        }
        if (Criterion::count() <= 2) {
            throw ValidationException::withMessages(['criteria' => __('Minimal harus ada dua kriteria.')]);
        }

        $criterion->delete(); // bobotnya ikut terhapus (cascade)
        Activity::log('kriteria.hapus', "Menghapus kriteria {$criterion->code} {$criterion->criteria_name}.", properties: ['kode' => $criterion->code]);

        return to_route('hrd.criteria')->with('success', __('Kriteria :criteria_name dihapus. Atur ulang bobot sampai total 100%.', ['criteria_name' => $criterion->criteria_name]));
    }

    private function assertNoOngoingPeriod(string $action): void
    {
        if (AssessmentPeriod::ongoing()->exists()) {
            throw ValidationException::withMessages(['criteria' => __('Tidak bisa :action saat ada periode yang berlangsung. Lakukan setelah periode ditutup, sebelum periode berikutnya dimulai.', ['action' => $action])]);
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
