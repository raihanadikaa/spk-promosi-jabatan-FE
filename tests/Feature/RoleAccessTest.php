<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role, string $name = 'Penguji'): User
    {
        return User::factory()->create([
            'name' => $name,
            'role_id' => Role::firstOrCreate(['role_name' => $role])->id,
        ]);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/hrd/dashboard')->assertRedirect('/login');
    }

    public function test_setiap_role_bisa_membuka_halamannya(): void
    {
        $pages = [
            'admin' => ['/admin/dashboard', '/admin/pengguna'],
            'hrd' => ['/hrd/dashboard', '/hrd/karyawan', '/hrd/periode', '/hrd/kriteria', '/hrd/proses-saw', '/hrd/ranking', '/hrd/ranking/1'],
            'manager' => ['/atasan/dashboard', '/atasan/penilaian', '/atasan/penilaian/7', '/atasan/ranking'],
            'direktur' => ['/direktur/dashboard', '/direktur/ranking', '/direktur/ranking/1'],
            'karyawan' => ['/karyawan/dashboard'],
        ];
        $names = ['manager' => 'Dimas Prakoso', 'karyawan' => 'Rian Pratama'];

        foreach ($pages as $role => $urls) {
            $user = $this->userWithRole($role, $names[$role] ?? 'Penguji');
            foreach ($urls as $url) {
                $this->actingAs($user)->get($url)->assertOk();
            }
        }
    }

    public function test_role_lain_ditolak(): void
    {
        $this->actingAs($this->userWithRole('karyawan'))->get('/hrd/kriteria')->assertForbidden();
        $this->actingAs($this->userWithRole('manager'))->get('/direktur/ranking/1')->assertForbidden();
    }

    public function test_atasan_tidak_bisa_menilai_karyawan_di_luar_timnya(): void
    {
        // Karyawan #3 ada di departemen Keuangan, bukan bawahan Dimas Prakoso (Operasional).
        $this->actingAs($this->userWithRole('manager', 'Dimas Prakoso'))
            ->get('/atasan/penilaian/3')
            ->assertNotFound();
    }

    public function test_aksi_demo_hanya_untuk_role_yang_berhak(): void
    {
        $this->actingAs($this->userWithRole('karyawan'))
            ->post('/demo/aksi', ['action' => 'promotion.approve'])
            ->assertForbidden();

        $this->actingAs($this->userWithRole('direktur'))
            ->post('/demo/aksi', ['action' => 'promotion.approve'])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_redirect_setelah_aksi_tidak_boleh_ke_situs_luar(): void
    {
        $direktur = $this->userWithRole('direktur');

        foreach (['//evil.example', '/\\evil.example', 'https://evil.example'] as $target) {
            $this->actingAs($direktur)
                ->from('/direktur/dashboard')
                ->post('/demo/aksi', ['action' => 'promotion.approve', 'redirect' => $target])
                ->assertRedirect('/direktur/dashboard');
        }

        $this->actingAs($direktur)
            ->post('/demo/aksi', ['action' => 'promotion.approve', 'redirect' => '/direktur/ranking'])
            ->assertRedirect('/direktur/ranking');
    }

    public function test_logout_hanya_lewat_post(): void
    {
        $user = $this->userWithRole('hrd');

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
    }
}
