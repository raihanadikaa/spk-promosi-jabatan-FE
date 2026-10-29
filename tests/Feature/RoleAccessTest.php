<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function as(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function employeeId(string $name): int
    {
        return Employee::where('name', $name)->value('id');
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/hrd/dashboard')->assertRedirect('/login');
        $this->put('/atasan/penilaian/1')->assertRedirect('/login');
    }

    public function test_setiap_role_bisa_membuka_halamannya(): void
    {
        $siti = $this->employeeId('Siti Rahmawati');
        $yoga = $this->employeeId('Yoga Pradana');
        $pages = [
            'admin@talentrank.test' => ['/admin/dashboard', '/admin/pengguna', '/akun/password'],
            'hrd@talentrank.test' => ['/hrd/dashboard', '/hrd/karyawan', '/hrd/karyawan?nonaktif=1', '/hrd/periode', '/hrd/kriteria', '/hrd/proses-saw', '/hrd/ranking', "/hrd/ranking/$siti", '/hrd/ranking/unduh'],
            'atasan@talentrank.test' => ['/atasan/dashboard', '/atasan/penilaian', "/atasan/penilaian/$yoga", '/atasan/ranking'],
            'direktur@talentrank.test' => ['/direktur/dashboard', '/direktur/ranking', "/direktur/ranking/$siti", '/direktur/ranking/unduh'],
            'karyawan@talentrank.test' => ['/karyawan/dashboard'],
        ];

        foreach ($pages as $email => $urls) {
            foreach ($urls as $url) {
                $this->actingAs($this->as($email))->get($url)->assertOk();
            }
        }
    }

    public function test_role_lain_ditolak(): void
    {
        $this->actingAs($this->as('karyawan@talentrank.test'))->get('/hrd/kriteria')->assertForbidden();
        $this->actingAs($this->as('atasan@talentrank.test'))->get('/direktur/ranking')->assertForbidden();
        $this->actingAs($this->as('hrd@talentrank.test'))->post('/direktur/ranking/1/keputusan', ['decision' => 'approve'])->assertForbidden();
        $this->actingAs($this->as('direktur@talentrank.test'))->put('/hrd/kriteria', [])->assertForbidden();
        $this->actingAs($this->as('admin@talentrank.test'))->get('/hrd/ranking')->assertForbidden();
    }

    public function test_atasan_tidak_bisa_membuka_atau_menilai_karyawan_di_luar_timnya(): void
    {
        $siti = $this->employeeId('Siti Rahmawati'); // Keuangan, bukan bawahan Dimas (Operasional)
        $atasan = $this->as('atasan@talentrank.test');

        $this->actingAs($atasan)->get("/atasan/penilaian/$siti")->assertNotFound();
        $this->actingAs($atasan)->put("/atasan/penilaian/$siti", ['action' => 'draft', 'scores' => ['C2' => 50]])->assertNotFound();
    }

    public function test_id_yang_tidak_ada_menghasilkan_404_bukan_error(): void
    {
        $this->actingAs($this->as('hrd@talentrank.test'))->get('/hrd/ranking/99999')->assertNotFound();
        $this->actingAs($this->as('hrd@talentrank.test'))->get('/hrd/ranking/abc')->assertNotFound();
        $this->actingAs($this->as('hrd@talentrank.test'))->post('/hrd/periode/99999/mulai')->assertNotFound();
        $this->actingAs($this->as('direktur@talentrank.test'))->post('/direktur/ranking/99999/keputusan', ['decision' => 'approve'])->assertNotFound();
    }

    public function test_logout_hanya_lewat_post(): void
    {
        $user = $this->as('hrd@talentrank.test');

        $this->actingAs($user)->get('/logout')->assertStatus(405);
        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_header_keamanan_terpasang(): void
    {
        $this->get('/login')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy');

        $this->actingAs($this->as('hrd@talentrank.test'))->get('/hrd/dashboard')
            ->assertHeader('Cache-Control', 'no-store, private');
    }
}
