<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_login_berhasil_mencatat_waktu_dan_akun_nonaktif_ditolak(): void
    {
        $this->post('/login', ['email' => 'hrd@talentrank.test', 'password' => 'password'])->assertRedirect('/hrd/dashboard');
        $this->assertNotNull($this->user('hrd@talentrank.test')->last_login_at);
        $this->post('/logout');

        $this->post('/login', ['email' => 'wawan.setiawan@talentrank.test', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_dikunci_setelah_5_kali_gagal(): void
    {
        foreach (range(1, 5) as $_) {
            $this->post('/login', ['email' => 'hrd@talentrank.test', 'password' => 'salah']);
        }
        $this->post('/login', ['email' => 'hrd@talentrank.test', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_akun_yang_dinonaktifkan_langsung_dikeluarkan(): void
    {
        $hrd = $this->user('hrd@talentrank.test');
        $this->actingAs($this->user('admin@talentrank.test'))->post("/admin/pengguna/{$hrd->id}/status")->assertSessionHasNoErrors();

        $this->actingAs($hrd->fresh())->get('/hrd/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_admin_tidak_bisa_mengunci_diri_sendiri(): void
    {
        $admin = $this->user('admin@talentrank.test');
        $hrdRole = Role::where('role_name', 'hrd')->value('id');

        $this->actingAs($admin)->post("/admin/pengguna/{$admin->id}/status")->assertSessionHasErrors('user');
        $this->actingAs($admin)->post("/admin/pengguna/{$admin->id}/reset-password")->assertSessionHasErrors('user');
        $this->actingAs($admin)->put("/admin/pengguna/{$admin->id}", ['name' => 'Admin', 'email' => $admin->email, 'role_id' => $hrdRole])
            ->assertSessionHasErrors('role_id');
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_admin_membuat_akun_dengan_password_sementara_yang_bisa_dipakai_login(): void
    {
        $role = Role::where('role_name', 'manager')->value('id');

        $response = $this->actingAs($this->user('admin@talentrank.test'))
            ->post('/admin/pengguna', ['name' => 'Atasan Baru', 'email' => 'baru@talentrank.test', 'role_id' => $role])
            ->assertSessionHasNoErrors();
        $temp = $response->getSession()->get('temporary_password')['password'];

        $this->assertTrue(Hash::check($temp, $this->user('baru@talentrank.test')->password));
        $this->actingAs($this->user('admin@talentrank.test'))
            ->post('/admin/pengguna', ['name' => 'Duplikat', 'email' => 'baru@talentrank.test', 'role_id' => $role])
            ->assertSessionHasErrors('email');
    }

    public function test_ganti_password_wajib_password_lama_yang_benar(): void
    {
        $user = $this->user('karyawan@talentrank.test');

        $this->actingAs($user)->put('/akun/password', ['current_password' => 'salah', 'password' => 'Baru12345', 'password_confirmation' => 'Baru12345'])
            ->assertSessionHasErrors('current_password');
        $this->actingAs($user)->put('/akun/password', ['current_password' => 'password', 'password' => 'pendek', 'password_confirmation' => 'pendek'])
            ->assertSessionHasErrors('password');
        $this->actingAs($user)->put('/akun/password', ['current_password' => 'password', 'password' => 'Baru12345', 'password_confirmation' => 'Baru12345'])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('Baru12345', $user->fresh()->password));
    }

    public function test_ganti_password_membatalkan_cookie_ingat_saya_lama(): void
    {
        $user = $this->user('karyawan@talentrank.test');
        $user->forceFill(['remember_token' => 'token-lama'])->save();

        $this->actingAs($user)->put('/akun/password', ['current_password' => 'password', 'password' => 'Baru12345', 'password_confirmation' => 'Baru12345'])
            ->assertSessionHasNoErrors();

        $this->assertNotSame('token-lama', $user->fresh()->remember_token);
    }

    public function test_karyawan_hanya_melihat_skor_sendiri(): void
    {
        $html = $this->actingAs($this->user('karyawan@talentrank.test'))->get('/karyawan/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('Skor akhir', $html);
        $this->assertStringNotContainsString('Siti Rahmawati', $html);
        $this->assertStringNotContainsString('Ahmad Fauzi', $html);
    }
}
