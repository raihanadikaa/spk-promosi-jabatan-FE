<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AssessmentPeriod;
use App\Models\Employee;
use App\Models\RankingResult;
use App\Models\User;
use App\Queries\PromotionData;
use App\Services\SawCalculator;
use App\Services\SensitivityAnalysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Log aktivitas, analisis sensitivitas bobot, dan grafik (sebaran skor, riwayat skor).
 */
class AuditTrailAndAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function as(string $email): User
    {
        PromotionData::flush();

        return User::where('email', $email)->firstOrFail();
    }

    private function period(): AssessmentPeriod
    {
        return AssessmentPeriod::where('status', 'ongoing')->firstOrFail();
    }

    /* ---------------- Log aktivitas ---------------- */

    public function test_aksi_penting_tercatat_di_log_dengan_pelaku_dan_periode(): void
    {
        $period = $this->period();
        $hrd = $this->as('hrd@talentrank.test');

        $this->actingAs($hrd)->put('/hrd/kriteria', ['criteria' => [
            'C1' => ['weight' => 10, 'type' => 'benefit'], 'C2' => ['weight' => 35, 'type' => 'benefit'],
            'C3' => ['weight' => 25, 'type' => 'benefit'], 'C4' => ['weight' => 15, 'type' => 'cost'], 'C5' => ['weight' => 15, 'type' => 'benefit'],
        ]])->assertSessionHasNoErrors();
        $this->actingAs($hrd)->post('/hrd/proses-saw')->assertSessionHasNoErrors();

        $weights = ActivityLog::where('action', 'kriteria.bobot')->firstOrFail();
        $this->assertSame($hrd->id, $weights->user_id);
        $this->assertSame($period->id, $weights->period_id);
        $this->assertSame(['dari' => 15, 'ke' => 10], $weights->properties['C1']['bobot']);
        $this->assertSame(['dari' => 30, 'ke' => 35], $weights->properties['C2']['bobot']);
        $this->assertArrayNotHasKey('C3', $weights->properties); // yang tidak berubah tidak dicatat
        $this->assertStringContainsString('C1 15%→10%', $weights->description);

        $saw = ActivityLog::where('action', 'saw.hitung')->firstOrFail();
        $this->assertSame(10, $saw->properties['bobot']['C1']);

        // Keputusan direktur tercatat beserta catatannya.
        $first = RankingResult::where('period_id', $period->id)->where('approval_status', 'pending')->orderBy('rank')->firstOrFail();
        $this->actingAs($this->as('direktur@talentrank.test'))
            ->post("/direktur/ranking/{$first->employee_id}/keputusan", ['decision' => 'reject', 'note' => 'Belum memimpin tim.'])
            ->assertSessionHasNoErrors();
        $decision = ActivityLog::where('action', 'keputusan.reject')->firstOrFail();
        $this->assertSame('Belum memimpin tim.', $decision->properties['catatan']);
        $this->assertStringContainsString($first->employee->name, $decision->description);
    }

    public function test_penilaian_atasan_dan_akun_tercatat_tanpa_password(): void
    {
        $atasan = $this->as('atasan@talentrank.test');
        $target = $atasan->subordinates()->active()->whereDoesntHave('assessments', fn ($q) => $q->where('period_id', $this->period()->id)->where('status', 'submitted'))->firstOrFail();

        $this->actingAs($atasan)->put("/atasan/penilaian/{$target->id}", ['action' => 'draft', 'scores' => ['C2' => 80]])->assertSessionHasNoErrors();
        $log = ActivityLog::where('action', 'penilaian.draft')->firstOrFail();
        $this->assertSame(['C2' => 80], $log->properties['nilai']);

        $admin = $this->as('admin@talentrank.test');
        $user = User::where('email', 'karyawan@talentrank.test')->firstOrFail();
        $this->actingAs($admin)->post("/admin/pengguna/{$user->id}/reset-password")->assertSessionHasNoErrors();
        $reset = ActivityLog::where('action', 'akun.reset')->firstOrFail();
        $this->assertNull($reset->properties);
        $this->assertStringNotContainsString(session('temporary_password')['password'], $reset->description);
    }

    public function test_login_dan_logout_tercatat(): void
    {
        $this->post('/login', ['email' => 'hrd@talentrank.test', 'password' => 'password'])->assertRedirect();
        $this->post('/logout')->assertRedirect('/login');

        $this->assertSame(['akun.login', 'akun.logout'], ActivityLog::orderBy('id')->pluck('action')->all());
    }

    public function test_halaman_log_bisa_dicari_difilter_dan_hanya_untuk_hrd_admin(): void
    {
        $period = $this->period();
        $hrd = $this->as('hrd@talentrank.test');
        $this->actingAs($hrd)->post('/hrd/proses-saw');
        $this->actingAs($hrd)->post('/hrd/departemen', ['department_name' => 'Riset Khusus']);

        $this->actingAs($hrd)->get('/hrd/log-aktivitas')->assertOk()->assertSee('Riset Khusus')->assertSee('Menghitung ranking SAW');
        $this->actingAs($hrd)->get('/hrd/log-aktivitas?q=Riset')->assertOk()->assertSee('Riset Khusus')->assertDontSee('Menghitung ranking SAW');
        $this->actingAs($hrd)->get('/hrd/log-aktivitas?kategori=saw')->assertOk()->assertSee('Menghitung ranking SAW')->assertDontSee('Riset Khusus');
        $this->actingAs($hrd)->get("/hrd/log-aktivitas?periode={$period->id}")->assertOk()->assertSee('Menghitung ranking SAW');
        $this->actingAs($hrd)->get('/hrd/log-aktivitas?q=%25')->assertOk()->assertSee('Tidak ada catatan yang cocok')->assertDontSee('Riset Khusus');
        $this->actingAs($hrd)->get('/hrd/log-aktivitas?kategori=bukan')->assertSessionHasErrors('kategori');
        $this->actingAs($hrd)->get('/hrd/log-aktivitas?dari=2026-12-01&sampai=2026-11-01')->assertSessionHasErrors('sampai');

        $csv = $this->actingAs($hrd)->get('/hrd/log-aktivitas/unduh?kategori=karyawan');
        $csv->assertOk();
        $this->assertStringContainsString('Riset Khusus', $csv->streamedContent());
        $this->assertStringNotContainsString('Menghitung ranking SAW', $csv->streamedContent());

        $this->actingAs($this->as('admin@talentrank.test'))->get('/admin/log-aktivitas')->assertOk()->assertSee('Riset Khusus');
        $this->actingAs($this->as('admin@talentrank.test'))->get('/admin/dashboard')->assertOk()->assertSee('Riset Khusus');
        foreach (['atasan@talentrank.test', 'direktur@talentrank.test', 'karyawan@talentrank.test'] as $email) {
            $this->actingAs($this->as($email))->get('/hrd/log-aktivitas')->assertForbidden();
        }
    }

    public function test_log_dipaginasi(): void
    {
        $now = now();
        ActivityLog::insert(collect(range(1, 60))->map(fn ($i) => [
            'category' => 'periode', 'action' => 'periode.buat', 'description' => "Catatan nomor {$i}.", 'created_at' => $now->copy()->addSeconds($i),
        ])->all());

        $this->actingAs($this->as('hrd@talentrank.test'))->get('/hrd/log-aktivitas')
            ->assertOk()->assertSee('Catatan nomor 60.')->assertDontSee('Catatan nomor 30.');
        $this->actingAs($this->as('hrd@talentrank.test'))->get('/hrd/log-aktivitas?page=2')
            ->assertOk()->assertSee('Catatan nomor 30.')->assertDontSee('Catatan nomor 60.');
    }

    /* ---------------- Jenis kriteria per periode ---------------- */

    public function test_ubah_jenis_kriteria_tidak_mengubah_periode_yang_sudah_ditutup(): void
    {
        $closed = AssessmentPeriod::where('status', 'closed')->firstOrFail();
        $current = $this->period();
        $hrd = $this->as('hrd@talentrank.test');
        PromotionData::flush();
        $closedBefore = collect(PromotionData::criteria($closed->id))->pluck('type', 'code')->all();
        $this->assertSame('cost', $closedBefore['C4']);

        $this->actingAs($hrd)->put('/hrd/kriteria', ['criteria' => [
            'C1' => ['weight' => 15, 'type' => 'benefit'], 'C2' => ['weight' => 30, 'type' => 'benefit'],
            'C3' => ['weight' => 25, 'type' => 'benefit'], 'C4' => ['weight' => 15, 'type' => 'benefit'], 'C5' => ['weight' => 15, 'type' => 'benefit'],
        ]])->assertSessionHasNoErrors();

        PromotionData::flush();
        $this->assertSame('benefit', collect(PromotionData::criteria($current->id))->firstWhere('code', 'C4')['type']);
        $this->assertSame($closedBefore, collect(PromotionData::criteria($closed->id))->pluck('type', 'code')->all());
        $this->assertSame(['dari' => 'cost', 'ke' => 'benefit'], ActivityLog::where('action', 'kriteria.bobot')->firstOrFail()->properties['C4']['jenis']);

        // Ranking periode berjalan dihitung dengan jenis baru: C4 terbesar sekarang bernilai r = 1.
        $this->actingAs($hrd)->post('/hrd/proses-saw')->assertSessionHasNoErrors();
        PromotionData::flush();
        $period = PromotionData::currentPeriod();
        $max = max(array_map(fn ($r) => (float) ($r['raw']['C4'] ?? 0), PromotionData::ranking($period)));
        $best = collect(PromotionData::ranking($period))->first(fn ($r) => (float) $r['raw']['C4'] === $max);
        $this->assertEquals(1.0, $best['normalized']['C4']);
    }

    public function test_proses_saw_memperingatkan_kriteria_cost_dengan_nilai_nol(): void
    {
        $hrd = $this->as('hrd@talentrank.test');
        $this->actingAs($hrd)->post('/hrd/proses-saw')->assertSessionHasNoErrors();
        PromotionData::flush();
        $minC4 = PromotionData::bounds(PromotionData::currentPeriod())['C4'];

        $page = $this->actingAs($hrd)->get('/hrd/proses-saw')->assertOk();
        $minC4 == 0.0 ? $page->assertSee('ada peserta bernilai 0') : $page->assertDontSee('ada peserta bernilai 0');

        // Nilai 0 → geseran +1: peserta di atas 0 tidak lagi semuanya r = 0, dan yang lebih kecil tetap lebih baik.
        if ($minC4 == 0.0) {
            $page->assertSee('(min + 1)', false);
            $rows = collect(PromotionData::ranking(PromotionData::currentPeriod()));
            foreach ($rows as $r) {
                $this->assertEqualsWithDelta(round(1 / ((float) $r['raw']['C4'] + 1), 4), $r['normalized']['C4'], 0.0001);
            }
            // Rincian kandidat menampilkan rumus dengan geseran, bukan "0 ÷ x".
            $someone = $rows->first(fn ($r) => (float) $r['raw']['C4'] > 0);
            $this->actingAs($hrd)->get("/hrd/ranking/{$someone['id']}")->assertOk()
                ->assertSee('(0 + 1) ÷ (' . (int) $someone['raw']['C4'] . ' + 1)', false);
        }
    }

    /** Data demo tidak punya nilai cost 0, jadi dibuat satu: geseran +1 harus terpakai di perhitungan & tampilan. */
    public function test_cost_bernilai_nol_memakai_geseran_di_perhitungan_dan_rincian(): void
    {
        $period = $this->period();
        $c4 = \App\Models\Criterion::where('code', 'C4')->value('id');
        $detail = \App\Models\AssessmentDetail::where('criteria_id', $c4)
            ->whereHas('assessment', fn ($q) => $q->where('period_id', $period->id)->where('status', 'submitted'))->firstOrFail();
        $detail->update(['raw_score' => 0]);

        $hrd = $this->as('hrd@talentrank.test');
        $this->actingAs($hrd)->post('/hrd/proses-saw')->assertSessionHasNoErrors();
        PromotionData::flush();
        $this->assertSame(0.0, (float) PromotionData::bounds(PromotionData::currentPeriod())['C4']);

        $rows = collect(PromotionData::ranking(PromotionData::currentPeriod()));
        foreach ($rows as $r) {
            $this->assertEqualsWithDelta(round(1 / ((float) $r['raw']['C4'] + 1), 4), $r['normalized']['C4'], 0.0001);
        }
        $this->assertGreaterThan(0, $rows->filter(fn ($r) => (float) $r['raw']['C4'] > 0 && $r['normalized']['C4'] > 0)->count());

        $this->actingAs($hrd)->get('/hrd/proses-saw')->assertOk()->assertSee('ada peserta bernilai 0')->assertSee('(min + 1)', false);
        $someone = $rows->first(fn ($r) => (float) $r['raw']['C4'] > 0);
        $this->actingAs($hrd)->get("/hrd/ranking/{$someone['id']}")->assertOk()
            ->assertSee('(0 + 1) ÷ (' . (int) $someone['raw']['C4'] . ' + 1)', false);
    }

    /* ---------------- Atasan penilai yang tidak lagi valid ---------------- */

    public function test_atasan_dinonaktifkan_admin_terdeteksi_di_monitor_hrd(): void
    {
        $manager = User::where('email', 'atasan@talentrank.test')->firstOrFail();
        $subordinates = Employee::active()->where('supervisor_id', $manager->id)->count();
        $this->assertGreaterThan(0, $subordinates);
        $hrd = $this->as('hrd@talentrank.test');
        $before = Employee::active()->withoutValidSupervisor()->count();

        $this->actingAs($this->as('admin@talentrank.test'))->post("/admin/pengguna/{$manager->id}/status")
            ->assertSessionHas('success', fn ($m) => str_contains($m, "Perhatian: {$subordinates} karyawan aktif"));

        $this->assertSame($before + $subordinates, Employee::active()->withoutValidSupervisor()->count());
        $this->actingAs($hrd)->get('/hrd/monitor')->assertOk()
            ->assertSee(($before + $subordinates) . ' karyawan aktif belum punya atasan penilai yang aktif')
            ->assertSee('(tidak aktif sebagai atasan)');

        $employee = Employee::active()->where('supervisor_id', $manager->id)->firstOrFail();
        $this->actingAs($hrd)->post("/hrd/monitor/ingatkan/{$employee->id}")->assertSessionHasErrors('monitor');

        // Mengaktifkan kembali tidak memberi peringatan.
        $this->actingAs($this->as('admin@talentrank.test'))->post("/admin/pengguna/{$manager->id}/status")
            ->assertSessionHas('success', fn ($m) => ! str_contains($m, 'Perhatian'));
        $this->assertSame($before, Employee::active()->withoutValidSupervisor()->count());
    }

    /* ---------------- Analisis sensitivitas ---------------- */

    public function test_geser_bobot_menjaga_total_100_dan_proporsi_kriteria_lain(): void
    {
        $analysis = new SensitivityAnalysis(new SawCalculator);
        $shifted = $analysis->shift(['C1' => 20, 'C2' => 40, 'C3' => 40], 'C1', 10);

        $this->assertEqualsWithDelta(100, array_sum($shifted), 1e-9);
        $this->assertSame(30.0, $shifted['C1']);
        $this->assertEqualsWithDelta(35, $shifted['C2'], 1e-9);
        $this->assertEqualsWithDelta($shifted['C2'], $shifted['C3'], 1e-9);
        $this->assertNull($analysis->shift(['C1' => 20, 'C2' => 80], 'C1', -25));
        $this->assertNull($analysis->shift(['C1' => 100, 'C2' => 0], 'C1', -10)); // kriteria lain semuanya 0
    }

    public function test_hasil_dasar_analisis_sama_dengan_ranking_tersimpan(): void
    {
        $hrd = $this->as('hrd@talentrank.test');
        $this->actingAs($hrd)->post('/hrd/proses-saw')->assertSessionHasNoErrors();
        PromotionData::flush();

        $period = PromotionData::currentPeriod();
        $result = app(SensitivityAnalysis::class)->analyze($period);
        $stored = RankingResult::where('period_id', $period['id'])->orderBy('rank')->limit(PromotionData::RECOMMENDED_COUNT)->pluck('employee_id')->all();

        $this->assertTrue($result['ready']);
        $this->assertSame($stored, $result['baseline_top']);
        $this->assertSame(count(SensitivityAnalysis::DELTAS) * 5, count(collect($result['scenarios'])->flatten(1)));
        $this->assertSame(RankingResult::where('period_id', $period['id'])->count(), $result['participants']);
    }

    public function test_halaman_sensitivitas_dan_simulasi_tidak_mengubah_data(): void
    {
        $period = $this->period();
        $before = RankingResult::where('period_id', $period->id)->orderBy('id')->get(['id', 'rank', 'final_score'])->toArray();
        $weightsBefore = $period->weights()->pluck('weight_value', 'criteria_id')->all();

        $hrd = $this->as('hrd@talentrank.test');
        $this->actingAs($hrd)->get('/hrd/sensitivitas')->assertOk()->assertSee('Batas aman bobot')->assertSee('Skenario pergeseran bobot');

        $custom = ['C1' => 0, 'C2' => 100, 'C3' => 0, 'C4' => 0, 'C5' => 0];
        $page = $this->actingAs($hrd)->get('/hrd/sensitivitas?' . http_build_query(['bobot' => $custom]));
        $page->assertOk()->assertSee('Peringkat simulasi');

        // Bobot 100% kinerja → peringkat 1 simulasi = nilai C2 tertinggi.
        $bestC2 = Employee::whereIn('id', RankingResult::where('period_id', $period->id)->pluck('employee_id'))
            ->get()->sortByDesc(fn ($e) => (float) $e->assessments()->where('period_id', $period->id)->first()
                ->details()->whereHas('criterion', fn ($c) => $c->where('code', 'C2'))->value('raw_score'))->first();
        $page->assertSeeInOrder(['Peringkat simulasi', $bestC2->name]);

        $this->actingAs($hrd)->get('/hrd/sensitivitas?' . http_build_query(['bobot' => ['C1' => 50, 'C2' => 10, 'C3' => 0, 'C4' => 0, 'C5' => 0]]))
            ->assertSessionHasErrors('bobot');
        $this->actingAs($hrd)->get('/hrd/sensitivitas?' . http_build_query(['bobot' => ['C1' => -5, 'C2' => 105]]))
            ->assertSessionHasErrors('bobot.C1');

        $this->assertSame($before, RankingResult::where('period_id', $period->id)->orderBy('id')->get(['id', 'rank', 'final_score'])->toArray());
        $this->assertSame($weightsBefore, $period->weights()->pluck('weight_value', 'criteria_id')->all());

        $this->actingAs($this->as('direktur@talentrank.test'))->get('/direktur/sensitivitas')->assertOk()->assertSee('Batas aman bobot');
        $this->actingAs($this->as('atasan@talentrank.test'))->get('/hrd/sensitivitas')->assertForbidden();
        $this->actingAs($this->as('karyawan@talentrank.test'))->get('/direktur/sensitivitas')->assertForbidden();
    }

    public function test_sensitivitas_tanpa_ranking_tampil_kosong_dengan_pesan(): void
    {
        RankingResult::query()->delete();

        $this->actingAs($this->as('hrd@talentrank.test'))->get('/hrd/sensitivitas')
            ->assertOk()->assertSee('Analisis belum bisa dijalankan');
    }

    /* ---------------- Grafik ---------------- */

    public function test_sebaran_skor_menjumlah_semua_peserta(): void
    {
        $period = PromotionData::currentPeriod();
        $d = PromotionData::scoreDistribution($period);
        $total = RankingResult::where('period_id', $period['id'])->count();

        $this->assertCount(10, $d['bins']);
        $this->assertSame($total, array_sum(array_column($d['bins'], 'total')));
        $this->assertSame(RankingResult::where('period_id', $period['id'])->whereNotNull('approval_status')->count(), array_sum(array_column($d['bins'], 'recommended')));

        $this->actingAs($this->as('hrd@talentrank.test'))->get('/hrd/dashboard')->assertOk()->assertSee('Sebaran skor akhir')->assertSee('Keputusan direktur');
        $this->actingAs($this->as('direktur@talentrank.test'))->get('/direktur/dashboard')->assertOk()->assertSee('Sebaran skor seluruh karyawan');
    }

    public function test_detail_kandidat_menampilkan_riwayat_skor_antar_periode(): void
    {
        $period = $this->period();
        $employeeId = RankingResult::where('period_id', $period->id)->whereIn('employee_id',
            RankingResult::where('period_id', '!=', $period->id)->select('employee_id'))->value('employee_id');
        $this->assertNotNull($employeeId, 'Seeder harus punya karyawan yang diranking di dua periode.');

        $history = PromotionData::scoreHistory($employeeId);
        $this->assertCount(2, $history);
        $this->assertSame($period->id, $history[1]['period_id']); // urut lama → baru

        $this->actingAs($this->as('hrd@talentrank.test'))->get("/hrd/ranking/{$employeeId}")
            ->assertOk()->assertSee('Riwayat skor')->assertSee($history[0]['period']);
    }
}
