<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentPeriod;
use App\Models\Employee;
use App\Models\NormalizedScore;
use App\Models\RankingResult;
use App\Models\User;
use App\Queries\PromotionData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Alur lengkap: atasan menilai → HRD menghitung → direktur memutuskan → periode ditutup → periode baru.
 */
class PromotionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function as(string $email): User
    {
        PromotionData::flush();

        return User::where('email', $email)->firstOrFail();
    }

    private function employee(string $name): Employee
    {
        return Employee::where('name', $name)->firstOrFail();
    }

    private function period(): AssessmentPeriod
    {
        return AssessmentPeriod::where('status', 'ongoing')->firstOrFail();
    }

    public function test_alur_lengkap_satu_periode(): void
    {
        $yoga = $this->employee('Yoga Pradana');
        $period = $this->period();

        // 1. Atasan mengirim draft Yoga.
        $this->actingAs($this->as('atasan@talentrank.test'))
            ->put("/atasan/penilaian/{$yoga->id}", ['action' => 'submit', 'scores' => ['C2' => 95, 'C3' => 94, 'C4' => 0, 'C5' => 7], 'notes' => 'Project gudang selesai lebih cepat.'])
            ->assertRedirect('/atasan/penilaian')
            ->assertSessionHasNoErrors();

        $assessment = Assessment::where(['employee_id' => $yoga->id, 'period_id' => $period->id])->first();
        $this->assertSame('submitted', $assessment->status);
        $this->assertCount(5, $assessment->details); // 4 input + masa kerja otomatis

        // 2. Ranking jadi usang sampai HRD menghitung ulang.
        PromotionData::flush();
        $this->assertNotEmpty(PromotionData::staleReasons());

        $this->actingAs($this->as('hrd@talentrank.test'))->post('/hrd/proses-saw')->assertSessionHasNoErrors();
        PromotionData::flush();
        $this->assertSame([], PromotionData::staleReasons());

        $ranking = PromotionData::ranking();
        $this->assertCount(10, $ranking);
        $this->assertSame(1, $ranking[$yoga->id]['rank'], 'Nilai Yoga tertinggi di semua kriteria');
        $this->assertSame(5, NormalizedScore::whereIn('assessment_detail_id', $assessment->details->pluck('id'))->count());

        // Skor tersimpan = jumlah kontribusi (jejak bisa diaudit).
        $this->assertEqualsWithDelta(array_sum($ranking[$yoga->id]['contributions']), $ranking[$yoga->id]['score'], 0.0005);

        // 3. Keputusan yang sudah final (Siti, disetujui di seed) tetap ada walau peringkatnya bergeser.
        $siti = $this->employee('Siti Rahmawati');
        $this->assertSame('approved', $ranking[$siti->id]['approval_status']);

        // 4. Periode belum bisa ditutup selama masih ada rekomendasi yang menunggu.
        $this->actingAs($this->as('hrd@talentrank.test'))->post("/hrd/periode/{$period->id}/tutup")->assertSessionHasErrors('period');

        // 5. Direktur memutuskan semua yang menunggu; tolak wajib ada alasan.
        $pending = RankingResult::where('period_id', $period->id)->where('approval_status', 'pending')->pluck('employee_id');
        $this->assertNotEmpty($pending);
        $direktur = $this->as('direktur@talentrank.test');

        $first = $pending->shift();
        $this->actingAs($direktur)->post("/direktur/ranking/{$first}/keputusan", ['decision' => 'reject'])->assertSessionHasErrors('note');
        $this->actingAs($direktur)->post("/direktur/ranking/{$first}/keputusan", ['decision' => 'reject', 'note' => 'Perlu pengalaman memimpin tim.'])->assertSessionHasNoErrors();
        foreach ($pending as $id) {
            $this->actingAs($direktur)->post("/direktur/ranking/{$id}/keputusan", ['decision' => 'approve'])->assertSessionHasNoErrors();
        }
        // Tidak bisa diputuskan dua kali.
        $this->actingAs($direktur)->post("/direktur/ranking/{$first}/keputusan", ['decision' => 'approve'])->assertSessionHasErrors('note');

        // 6. Tutup periode → semua terkunci.
        $this->actingAs($this->as('hrd@talentrank.test'))->post("/hrd/periode/{$period->id}/tutup")->assertSessionHasNoErrors();
        $this->assertSame('closed', $period->fresh()->status);

        $rian = $this->employee('Rian Pratama');
        $this->actingAs($this->as('atasan@talentrank.test'))
            ->put("/atasan/penilaian/{$rian->id}", ['action' => 'draft', 'scores' => ['C2' => 10]])
            ->assertSessionHasErrors('period');
        $this->actingAs($this->as('hrd@talentrank.test'))->post('/hrd/proses-saw')->assertSessionHasErrors('saw');

        // 7. Periode baru: draft → atur bobot → mulai.
        $this->actingAs($this->as('hrd@talentrank.test'))->post('/hrd/periode', [
            'period_name' => 'Semester 1 2027', 'start_date' => '2027-01-01', 'end_date' => '2027-06-30', 'assessment_deadline' => '2027-05-15',
        ])->assertSessionHasNoErrors();
        $next = AssessmentPeriod::where('period_name', 'Semester 1 2027')->firstOrFail();
        $this->assertSame(100, (int) $next->weights()->sum('weight_value'), 'Bobot disalin dari periode terakhir');

        $this->actingAs($this->as('hrd@talentrank.test'))->put('/hrd/kriteria', ['criteria' => [
            'C1' => ['weight' => 10, 'type' => 'benefit'], 'C2' => ['weight' => 35, 'type' => 'benefit'],
            'C3' => ['weight' => 25, 'type' => 'benefit'], 'C4' => ['weight' => 15, 'type' => 'cost'], 'C5' => ['weight' => 15, 'type' => 'benefit'],
        ]])->assertSessionHasNoErrors();
        $this->assertSame(35, (int) $next->weights()->whereHas('criterion', fn ($q) => $q->where('code', 'C2'))->value('weight_value'));
        $this->assertSame(30, (int) $period->weights()->whereHas('criterion', fn ($q) => $q->where('code', 'C2'))->value('weight_value'), 'Bobot periode lama tidak berubah');

        $this->actingAs($this->as('hrd@talentrank.test'))->post("/hrd/periode/{$next->id}/mulai")->assertSessionHasNoErrors();
        PromotionData::flush();
        $this->assertSame($next->id, PromotionData::currentPeriod()['id']);

        // Di periode baru semua karyawan kembali belum dinilai, dan skor lama jadi pembanding.
        $this->assertSame(['pending'], collect(PromotionData::employees())->pluck('status')->unique()->values()->all());
    }

    public function test_draft_boleh_belum_lengkap_tapi_kirim_wajib_lengkap_dan_dalam_rentang(): void
    {
        $rian = $this->employee('Nadia Putri'); // bawahan Lestari, belum dinilai
        $lestari = $this->as('lestari.h@talentrank.test');

        $this->actingAs($lestari)->put("/atasan/penilaian/{$rian->id}", ['action' => 'draft', 'scores' => ['C2' => 80]])
            ->assertSessionHasNoErrors();
        $this->actingAs($lestari)->put("/atasan/penilaian/{$rian->id}", ['action' => 'submit', 'scores' => ['C2' => 80]])
            ->assertSessionHasErrors(['scores.C3', 'scores.C4', 'scores.C5']);
        $this->actingAs($lestari)->put("/atasan/penilaian/{$rian->id}", ['action' => 'submit', 'scores' => ['C2' => 101, 'C3' => -1, 'C4' => 'x', 'C5' => 2]])
            ->assertSessionHasErrors(['scores.C2', 'scores.C3', 'scores.C4']);

        $this->assertSame('draft', Assessment::where('employee_id', $rian->id)->value('status'));
    }

    public function test_penilaian_terkirim_terkunci_sampai_dibuka_hrd(): void
    {
        $ahmad = $this->employee('Ahmad Fauzi');
        $atasan = $this->as('atasan@talentrank.test');
        $scores = ['C2' => 50, 'C3' => 50, 'C4' => 1, 'C5' => 1];

        $this->actingAs($atasan)->put("/atasan/penilaian/{$ahmad->id}", ['action' => 'draft', 'scores' => $scores])
            ->assertSessionHasErrors('employee');

        $assessment = Assessment::where('employee_id', $ahmad->id)->first();
        $this->actingAs($this->as('hrd@talentrank.test'))->post("/hrd/penilaian/{$assessment->id}/buka")->assertSessionHasNoErrors();

        $this->actingAs($atasan)->put("/atasan/penilaian/{$ahmad->id}", ['action' => 'draft', 'scores' => $scores])
            ->assertSessionHasNoErrors();
        $this->assertSame('draft', $assessment->fresh()->status);
    }

    public function test_bobot_harus_total_100(): void
    {
        $this->actingAs($this->as('hrd@talentrank.test'))->put('/hrd/kriteria', ['criteria' => [
            'C1' => ['weight' => 50, 'type' => 'benefit'], 'C2' => ['weight' => 50, 'type' => 'benefit'],
            'C3' => ['weight' => 50, 'type' => 'benefit'], 'C4' => ['weight' => 0, 'type' => 'cost'], 'C5' => ['weight' => 0, 'type' => 'benefit'],
        ]])->assertSessionHasErrors('criteria');

        $this->actingAs($this->as('hrd@talentrank.test'))->put('/hrd/kriteria', ['criteria' => ['C1' => ['weight' => 100, 'type' => 'aneh']]])
            ->assertSessionHasErrors();
    }

    public function test_hanya_satu_periode_berlangsung_dan_tanggal_tidak_bertumpuk(): void
    {
        $hrd = $this->as('hrd@talentrank.test');

        $this->actingAs($hrd)->post('/hrd/periode', [
            'period_name' => 'Bertumpuk', 'start_date' => '2026-12-01', 'end_date' => '2027-02-01', 'assessment_deadline' => '2027-01-15',
        ])->assertSessionHasErrors('start_date');

        $this->actingAs($hrd)->post('/hrd/periode', [
            'period_name' => 'Semester 1 2027', 'start_date' => '2027-01-01', 'end_date' => '2027-06-30', 'assessment_deadline' => '2027-08-01',
        ])->assertSessionHasErrors('assessment_deadline');

        $this->actingAs($hrd)->post('/hrd/periode', [
            'period_name' => 'Semester 1 2027', 'start_date' => '2027-01-01', 'end_date' => '2027-06-30', 'assessment_deadline' => '2027-05-01',
        ])->assertSessionHasNoErrors();
        $draft = AssessmentPeriod::where('period_name', 'Semester 1 2027')->first();

        $this->actingAs($hrd)->post("/hrd/periode/{$draft->id}/mulai")->assertSessionHasErrors('period');
    }

    public function test_hrd_mengelola_karyawan_dan_atasan_penilai(): void
    {
        $hrd = $this->as('hrd@talentrank.test');
        $dept = DB::table('departments')->where('department_name', 'Keuangan')->value('id');
        $pos = DB::table('positions')->where('position_name', 'Staf')->value('id');
        $ratna = User::where('email', 'ratna.sari@talentrank.test')->value('id');

        $data = ['name' => 'Budi Santoso', 'nik' => '3201230101', 'department_id' => $dept, 'position_id' => $pos,
            'supervisor_id' => $ratna, 'join_date' => '2023-01-01', 'is_active' => 1];
        $this->actingAs($hrd)->post('/hrd/karyawan', $data)->assertSessionHasNoErrors();
        $this->actingAs($hrd)->post('/hrd/karyawan', $data)->assertSessionHasErrors('nik');

        // Atasan penilai harus akun atasan aktif.
        $direktur = User::where('email', 'direktur@talentrank.test')->value('id');
        $this->actingAs($hrd)->post('/hrd/karyawan', ['nik' => '3201230102', 'supervisor_id' => $direktur] + $data)
            ->assertSessionHasErrors('supervisor_id');
        $this->actingAs($hrd)->post('/hrd/karyawan', ['nik' => '3201230103', 'join_date' => '2999-01-01'] + $data)
            ->assertSessionHasErrors('join_date');

        // Nonaktifkan → hilang dari daftar penilaian atasan.
        $budi = Employee::where('nik', '3201230101')->first();
        $this->actingAs($hrd)->put("/hrd/karyawan/{$budi->id}", ['is_active' => 0] + array_diff_key($data, ['is_active' => 1]))
            ->assertSessionHasNoErrors();
        $this->assertFalse($budi->fresh()->is_active);
        PromotionData::flush();
        $this->assertArrayNotHasKey($budi->id, PromotionData::subordinatesOf(User::find($ratna)));
    }

    public function test_tanpa_periode_semua_halaman_tetap_aman(): void
    {
        DB::table('assessment_periods')->delete();

        foreach ([
            'hrd@talentrank.test' => ['/hrd/dashboard', '/hrd/kriteria', '/hrd/proses-saw', '/hrd/ranking', '/hrd/periode', '/hrd/karyawan'],
            'atasan@talentrank.test' => ['/atasan/dashboard', '/atasan/penilaian', '/atasan/ranking', '/atasan/penilaian/1'],
            'direktur@talentrank.test' => ['/direktur/dashboard', '/direktur/ranking'],
            'karyawan@talentrank.test' => ['/karyawan/dashboard'],
        ] as $email => $urls) {
            foreach ($urls as $url) {
                $status = $this->actingAs($this->as($email))->get($url)->status();
                $this->assertContains($status, [200, 404], "$email $url → $status");
            }
        }

        $this->actingAs($this->as('hrd@talentrank.test'))->post('/hrd/proses-saw')->assertSessionHasErrors('saw');
        $this->actingAs($this->as('hrd@talentrank.test'))->get('/hrd/ranking/unduh')->assertNotFound();
    }

    public function test_unduh_csv_berisi_ranking_dan_aman_dari_formula_injection(): void
    {
        Employee::where('name', 'Siti Rahmawati')->update(['name' => '=HYPERLINK("http://jahat")']);
        PromotionData::flush();

        $csv = $this->actingAs($this->as('hrd@talentrank.test'))->get('/hrd/ranking/unduh')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->streamedContent();

        $this->assertStringContainsString('Peringkat;Nama;NIK', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString(';=HYPERLINK', $csv);
    }
}
