<?php

namespace Tests\Feature;

use App\Models\AssessmentPeriod;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\RankingResult;
use App\Models\User;
use App\Notifications\AssessmentReminder;
use App\Notifications\RevisionRequested;
use App\Queries\PromotionData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fitur back end yang mengikuti UI Figma: departemen & jabatan, monitor + pengingat,
 * review ulang direktur, ubah/hapus periode draft, ekspor matriks SAW, riwayat skor karyawan.
 */
class BackendFeaturesTest extends TestCase
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

    /* ---------------- Departemen & jabatan ---------------- */

    public function test_hrd_mengelola_departemen_dan_jabatan(): void
    {
        $hrd = $this->as('hrd@talentrank.test');

        $this->actingAs($hrd)->get('/hrd/organisasi')->assertOk();
        $this->actingAs($hrd)->get('/hrd/organisasi?tab=jabatan')->assertOk();

        $this->actingAs($hrd)->post('/hrd/departemen', ['department_name' => 'Riset'])->assertSessionHasNoErrors();
        $riset = Department::where('department_name', 'Riset')->firstOrFail();
        $this->actingAs($hrd)->post('/hrd/departemen', ['department_name' => 'Riset'])->assertSessionHasErrors('department_name');
        $this->actingAs($hrd)->put("/hrd/departemen/{$riset->id}", ['department_name' => 'Riset & Pengembangan'])->assertSessionHasNoErrors();
        $this->assertSame('Riset & Pengembangan', $riset->fresh()->department_name);
        $this->actingAs($hrd)->delete("/hrd/departemen/{$riset->id}")->assertSessionHasNoErrors();
        $this->assertModelMissing($riset);

        // Departemen yang masih dipakai karyawan tidak bisa dihapus.
        $used = Employee::firstOrFail()->department;
        $this->actingAs($hrd)->delete("/hrd/departemen/{$used->id}")->assertSessionHasErrors('organization');
        $this->assertModelExists($used);

        $this->actingAs($hrd)->post('/hrd/jabatan', ['position_name' => 'Direktur Muda', 'level' => 9])->assertSessionHasNoErrors();
        $this->actingAs($hrd)->post('/hrd/jabatan', ['position_name' => 'Tanpa Level'])->assertSessionHasErrors('level');
        $usedPosition = Employee::firstOrFail()->position;
        $this->actingAs($hrd)->delete("/hrd/jabatan/{$usedPosition->id}")->assertSessionHasErrors('organization');

        // Role lain tidak boleh.
        $this->actingAs($this->as('atasan@talentrank.test'))->post('/hrd/departemen', ['department_name' => 'X'])->assertForbidden();
    }

    /* ---------------- Monitor & pengingat ---------------- */

    public function test_monitor_dan_pengingat_ke_atasan(): void
    {
        $hrd = $this->as('hrd@talentrank.test');
        $atasan = $this->as('atasan@talentrank.test');

        $this->actingAs($hrd)->get('/hrd/monitor')->assertOk()->assertSee('Monitor penilaian');
        $this->actingAs($hrd)->get('/hrd/monitor?status=unfinished')->assertOk();

        $this->actingAs($hrd)->post('/hrd/monitor/ingatkan')->assertSessionHasNoErrors();
        $this->assertSame(1, $atasan->notifications()->where('type', AssessmentReminder::class)->count());

        $unfinished = Employee::where('supervisor_id', $atasan->id)
            ->whereDoesntHave('assessments', fn ($a) => $a->where('period_id', $this->period()->id)->where('status', 'submitted'))
            ->firstOrFail();
        $this->actingAs($hrd)->post("/hrd/monitor/ingatkan/{$unfinished->id}")->assertSessionHasNoErrors();
        $notification = $atasan->notifications()->latest()->firstOrFail();
        $this->assertStringContainsString($unfinished->name, $notification->data['body']);

        // Atasan membuka notifikasi → ditandai dibaca dan diarahkan ke halaman penilaian.
        $this->actingAs($atasan)->get("/notifikasi/{$notification->id}")->assertRedirect('/atasan/penilaian');
        $this->assertNotNull($notification->fresh()->read_at);

        // Notifikasi milik orang lain tidak bisa dibuka.
        $this->actingAs($hrd)->get("/notifikasi/{$notification->id}")->assertNotFound();

        $this->actingAs($atasan)->post('/notifikasi/baca-semua')->assertSessionHasNoErrors();
        $this->assertSame(0, $atasan->unreadNotifications()->count());
    }

    public function test_pengingat_ditolak_jika_periode_tidak_berlangsung(): void
    {
        AssessmentPeriod::query()->update(['status' => AssessmentPeriod::CLOSED]);

        $this->actingAs($this->as('hrd@talentrank.test'))->post('/hrd/monitor/ingatkan')->assertSessionHasErrors('monitor');
    }

    /* ---------------- Review ulang direktur ---------------- */

    public function test_direktur_minta_review_ulang_lalu_hitung_ulang_mengembalikan_ke_menunggu(): void
    {
        $period = $this->period();
        $hrd = $this->as('hrd@talentrank.test');
        $direktur = $this->as('direktur@talentrank.test');

        $this->actingAs($hrd)->post('/hrd/proses-saw')->assertSessionHasNoErrors();
        $result = RankingResult::where('period_id', $period->id)->where('approval_status', 'pending')->firstOrFail();

        $this->actingAs($direktur)->get('/direktur/persetujuan')->assertOk()->assertSee('Persetujuan promosi');
        $this->actingAs($direktur)->get("/direktur/ranking/{$result->employee_id}")->assertOk()->assertSee('Minta review ulang HRD');

        // Catatan wajib.
        $this->actingAs($direktur)->post("/direktur/ranking/{$result->employee_id}/keputusan", ['decision' => 'revision'])->assertSessionHasErrors('note');
        $this->actingAs($direktur)->post("/direktur/ranking/{$result->employee_id}/keputusan", ['decision' => 'revision', 'note' => 'Cek ulang nilai kompetensi.'])
            ->assertRedirect('/direktur/persetujuan');
        $this->assertSame('revision', $result->fresh()->approval_status);
        $this->assertSame(1, $hrd->notifications()->where('type', RevisionRequested::class)->count());

        // Selama ditinjau: direktur tidak bisa memutuskan, periode tidak bisa ditutup.
        $this->actingAs($direktur)->post("/direktur/ranking/{$result->employee_id}/keputusan", ['decision' => 'approve'])->assertSessionHasErrors('note');
        $this->actingAs($hrd)->post("/hrd/periode/{$period->id}/tutup")->assertSessionHasErrors('period');

        // Filter status review ulang.
        $this->actingAs($direktur)->get('/direktur/persetujuan?status=revision')->assertOk()->assertSee($result->employee->name);

        // HRD menghitung ulang → kembali menunggu keputusan.
        $this->actingAs($hrd)->post('/hrd/proses-saw')->assertSessionHasNoErrors();
        $fresh = $result->fresh();
        $this->assertSame('pending', $fresh->approval_status);
        $this->assertNull($fresh->approved_by);

        $this->actingAs($direktur)->post("/direktur/ranking/{$result->employee_id}/keputusan", ['decision' => 'approve'])->assertSessionHasNoErrors();
        $this->assertSame('approved', $result->fresh()->approval_status);
    }

    public function test_usulan_jabatan_adalah_level_terdekat_di_atasnya(): void
    {
        $levels = Position::orderBy('level')->get();
        $lowest = $levels->first();
        $next = $levels->first(fn ($p) => $p->level > $lowest->level);

        $this->assertSame($next?->position_name, PromotionData::nextPosition($lowest->level));
        $this->assertNull(PromotionData::nextPosition($levels->max('level')));
    }

    /* ---------------- Periode draft ---------------- */

    public function test_periode_draft_bisa_diubah_dan_dihapus_tapi_yang_berjalan_tidak(): void
    {
        $hrd = $this->as('hrd@talentrank.test');
        $data = ['period_name' => 'Semester 1 2027', 'start_date' => '2027-01-01', 'end_date' => '2027-06-30', 'assessment_deadline' => '2027-05-15'];

        $this->actingAs($hrd)->post('/hrd/periode', $data)->assertSessionHasNoErrors();
        $draft = AssessmentPeriod::where('period_name', 'Semester 1 2027')->firstOrFail();

        // Nama sendiri tidak dianggap duplikat saat mengubah.
        $this->actingAs($hrd)->put("/hrd/periode/{$draft->id}", ['assessment_deadline' => '2027-06-01'] + $data)->assertSessionHasNoErrors();
        $this->assertSame('2027-06-01', $draft->fresh()->assessment_deadline->toDateString());

        // Tidak boleh bertumpuk dengan periode lain.
        $this->actingAs($hrd)->put("/hrd/periode/{$draft->id}", ['start_date' => '2026-12-01'] + $data)->assertSessionHasErrors('start_date');

        $ongoing = $this->period();
        $this->actingAs($hrd)->put("/hrd/periode/{$ongoing->id}", [
            'period_name' => $ongoing->period_name, 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'assessment_deadline' => '2026-12-01',
        ])->assertSessionHasErrors('period');
        $this->actingAs($hrd)->delete("/hrd/periode/{$ongoing->id}")->assertSessionHasErrors('period');

        $this->actingAs($hrd)->delete("/hrd/periode/{$draft->id}")->assertSessionHasNoErrors();
        $this->assertModelMissing($draft);
    }

    /* ---------------- Ekspor matriks ---------------- */

    public function test_ekspor_matriks_saw_berisi_x_r_dan_v(): void
    {
        $hrd = $this->as('hrd@talentrank.test');
        $this->actingAs($hrd)->post('/hrd/proses-saw')->assertSessionHasNoErrors();

        $csv = $this->actingAs($hrd)->get('/hrd/proses-saw/matriks')->assertOk()->streamedContent();

        $this->assertStringContainsString('Bobot W', $csv);
        $this->assertStringContainsString('X C1', $csv);
        $this->assertStringContainsString('R C5', $csv);
        $this->assertStringContainsString('V (skor)', $csv);
        PromotionData::flush();
        $this->assertSame(PromotionData::rankingCount(PromotionData::currentPeriod()), preg_match_all('/^A\d+;/m', $csv));

        $this->actingAs($this->as('direktur@talentrank.test'))->get('/hrd/proses-saw/matriks')->assertForbidden();
    }

    /* ---------------- Riwayat karyawan ---------------- */

    public function test_karyawan_melihat_riwayat_periode_yang_sudah_ditutup(): void
    {
        $karyawan = $this->as('karyawan@talentrank.test');
        $employee = $karyawan->employee()->firstOrFail();
        $closed = AssessmentPeriod::where('status', 'closed')->firstOrFail();

        $response = $this->actingAs($karyawan)->get('/karyawan/dashboard')->assertOk();

        if (RankingResult::where('period_id', $closed->id)->where('employee_id', $employee->id)->exists()) {
            $response->assertSee('Riwayat penilaian')->assertSee($closed->period_name);
        } else {
            $response->assertDontSee('Riwayat penilaian');
        }
    }
}
