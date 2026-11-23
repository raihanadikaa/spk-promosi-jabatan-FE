<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Profil & pengaturan: data diri, foto/avatar, sesi login, hapus akun. */
class ProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function as(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_halaman_pengaturan_terbuka_untuk_semua_role(): void
    {
        $this->get('/akun')->assertRedirect('/login');

        foreach (['admin', 'hrd', 'atasan', 'direktur', 'karyawan'] as $role) {
            $this->actingAs($this->as("$role@talentrank.test"))->get('/akun')->assertOk()
                ->assertSee('Profil & pengaturan')->assertSee('Foto profil')->assertSee('Sesi login aktif')->assertSee('Hapus akun');
        }
    }

    public function test_ubah_nama_dan_nomor_hp_dengan_validasi(): void
    {
        $user = $this->as('karyawan@talentrank.test');

        $this->actingAs($user)->put('/akun/profil', ['name' => '  Rian   Pratama Putra ', 'phone' => '+62 812-3456-7890'])
            ->assertRedirect('/akun?tab=profil')->assertSessionHas('success', 'Profil tersimpan.');
        $this->assertSame('Rian Pratama Putra', $user->fresh()->name);
        $this->assertSame('+62 812-3456-7890', $user->fresh()->phone);
        $this->assertDatabaseHas('activity_logs', ['action' => 'akun.profil', 'user_id' => $user->id]);

        $this->actingAs($user)->put('/akun/profil', ['name' => '<script>', 'phone' => 'abc'])->assertSessionHasErrors(['name', 'phone']);
        $this->actingAs($user)->put('/akun/profil', ['name' => ''])->assertSessionHasErrors('name');
    }

    public function test_unggah_foto_diproses_ulang_jadi_webp_512_dan_foto_lama_dihapus(): void
    {
        $user = $this->as('hrd@talentrank.test');

        $this->actingAs($user)->post('/akun/foto', ['photo' => UploadedFile::fake()->image('wajah.jpg', 900, 600)])
            ->assertRedirect('/akun?tab=profil')->assertSessionHas('success');
        $first = $user->fresh()->avatar_path;
        $this->assertNotNull($first);
        Storage::disk('local')->assertExists($first);

        $info = getimagesizefromstring(Storage::disk('local')->get($first));
        $this->assertSame([512, 512, 'image/webp'], [$info[0], $info[1], $info['mime']]);

        // Foto tampil lewat rute terlindungi
        $url = $user->fresh()->avatarUrl();
        $this->actingAs($user)->get($url)->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->actingAs($this->as('karyawan@talentrank.test'))->get($url)->assertOk();
        $this->actingAs($user)->get("/avatar/{$user->id}/salah")->assertNotFound();

        // Foto baru menggantikan & menghapus yang lama
        $this->actingAs($user)->post('/akun/foto', ['photo' => UploadedFile::fake()->image('baru.png', 300, 300)]);
        Storage::disk('local')->assertMissing($first);
        Storage::disk('local')->assertExists($user->fresh()->avatar_path);
        $this->actingAs($user)->get('/akun')->assertSee($user->fresh()->avatarUrl(), false);
    }

    public function test_foto_tidak_valid_ditolak(): void
    {
        $user = $this->as('hrd@talentrank.test');

        $this->actingAs($user)->post('/akun/foto', ['photo' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')])->assertSessionHasErrors('photo');
        $this->actingAs($user)->post('/akun/foto', ['photo' => UploadedFile::fake()->createWithContent('palsu.jpg', '<?php echo 1; ?>')])->assertSessionHasErrors('photo');
        $this->actingAs($user)->post('/akun/foto', ['photo' => UploadedFile::fake()->image('kecil.png', 20, 20)])->assertSessionHasErrors('photo');
        $this->actingAs($user)->post('/akun/foto', ['photo' => UploadedFile::fake()->image('besar.jpg', 500, 500)->size(6000)])->assertSessionHasErrors('photo');
        $this->actingAs($user)->post('/akun/foto', [])->assertSessionHasErrors('photo');
        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_hapus_foto_dan_pilih_avatar_siap_pakai(): void
    {
        $user = $this->as('direktur@talentrank.test');
        $this->actingAs($user)->post('/akun/foto', ['photo' => UploadedFile::fake()->image('a.jpg', 200, 200)]);
        $path = $user->fresh()->avatar_path;

        $this->actingAs($user)->delete('/akun/foto')->assertSessionHas('success', 'Foto profil dihapus.');
        $this->assertNull($user->fresh()->avatar_path);
        Storage::disk('local')->assertMissing($path);

        // Avatar siap pakai menggantikan foto
        $this->actingAs($user)->post('/akun/foto', ['photo' => UploadedFile::fake()->image('b.jpg', 200, 200)]);
        $path = $user->fresh()->avatar_path;
        $this->actingAs($user)->put('/akun/avatar', ['preset' => 'laut'])->assertSessionHas('success', 'Avatar diganti.');
        $this->assertSame(['laut', null], [$user->fresh()->avatar_preset, $user->fresh()->avatar_path]);
        Storage::disk('local')->assertMissing($path);

        $this->actingAs($user)->put('/akun/avatar', ['preset' => 'tidak-ada'])->assertSessionHasErrors('preset');
        $this->actingAs($user)->put('/akun/avatar', ['preset' => ''])->assertSessionHas('success', 'Avatar kembali ke inisial nama.');
        $this->assertNull($user->fresh()->avatar_preset);
    }

    private function fakeSession(User $user, string $id, string $agent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile Safari/604.1'): void
    {
        DB::table('sessions')->insert(['id' => $id, 'user_id' => $user->id, 'ip_address' => '10.0.0.7', 'user_agent' => $agent, 'payload' => '', 'last_activity' => now()->getTimestamp()]);
    }

    public function test_sesi_login_bisa_dilihat_dan_dikeluarkan(): void
    {
        $user = $this->as('atasan@talentrank.test');
        $other = $this->as('direktur@talentrank.test');
        $this->fakeSession($user, str_repeat('a', 40));
        $this->fakeSession($other, str_repeat('b', 40));
        DB::table('sessions')->insert(['id' => str_repeat('c', 40), 'user_id' => $user->id, 'ip_address' => '1.1.1.1', 'user_agent' => 'x', 'payload' => '', 'last_activity' => now()->subDays(2)->getTimestamp()]);

        $this->actingAs($user)->get('/akun?tab=keamanan')->assertOk()->assertSee('Safari di iOS')->assertSee('10.0.0.7')->assertDontSee('1.1.1.1');

        // Sesi orang lain tidak bisa dikeluarkan
        $this->actingAs($user)->delete('/akun/sesi/' . str_repeat('b', 40))->assertNotFound();
        $this->assertDatabaseHas('sessions', ['id' => str_repeat('b', 40)]);

        $this->actingAs($user)->delete('/akun/sesi/' . str_repeat('a', 40))->assertSessionHas('success');
        $this->assertDatabaseMissing('sessions', ['id' => str_repeat('a', 40)]);
    }

    public function test_keluar_dari_semua_perangkat_lain_wajib_password(): void
    {
        $user = $this->as('atasan@talentrank.test');
        $this->fakeSession($user, str_repeat('d', 40));
        $this->fakeSession($user, str_repeat('e', 40));
        $token = $user->remember_token;

        $this->actingAs($user)->post('/akun/sesi/keluar-lainnya', ['password' => 'salah'])->assertSessionHasErrorsIn('sessions', 'password');
        $this->assertSame(2, DB::table('sessions')->where('user_id', $user->id)->count());

        $this->actingAs($user)->post('/akun/sesi/keluar-lainnya', ['password' => 'password'])->assertSessionHas('success', '2 sesi di perangkat lain dikeluarkan.');
        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->whereIn('id', [str_repeat('d', 40), str_repeat('e', 40)])->count());
        $this->assertNotSame($token, $user->fresh()->remember_token);
    }

    public function test_hapus_akun_dengan_password_dan_konfirmasi_data_tetap_aman(): void
    {
        $user = $this->as('karyawan@talentrank.test');
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)->delete('/akun', ['password' => 'password', 'confirmation' => 'hapus'])->assertSessionHasErrorsIn('deleteAccount', 'confirmation');
        $this->actingAs($user)->delete('/akun', ['password' => 'salah', 'confirmation' => 'HAPUS'])->assertSessionHasErrorsIn('deleteAccount', 'password');
        $this->assertModelExists($user);

        $this->actingAs($user)->delete('/akun', ['password' => 'password', 'confirmation' => 'HAPUS'])->assertRedirect('/login');
        $this->assertModelMissing($user);
        $this->assertGuest();
        $this->assertNull($employee->fresh()->user_id); // data karyawan & penilaian tetap ada
        $this->assertDatabaseHas('activity_logs', ['action' => 'akun.hapus', 'user_name' => 'Rian Pratama']);
    }

    public function test_hrd_atau_admin_terakhir_tidak_bisa_menghapus_akunnya(): void
    {
        foreach (['hrd@talentrank.test', 'admin@talentrank.test', 'direktur@talentrank.test'] as $email) {
            $user = $this->as($email);
            $this->actingAs($user)->get('/akun?tab=akun')->assertSee('satu-satunya');
            $this->actingAs($user)->delete('/akun', ['password' => 'password', 'confirmation' => 'HAPUS'])->assertSessionHasErrorsIn('deleteAccount', 'confirmation');
            $this->assertModelExists($user);
        }

        // Atasan boleh (masih ada atasan lain), tetapi bawahannya terdeteksi tanpa atasan penilai.
        $manager = $this->as('atasan@talentrank.test');
        $this->actingAs($manager)->delete('/akun', ['password' => 'password', 'confirmation' => 'HAPUS'])->assertRedirect('/login');
        $this->assertModelMissing($manager);
    }

    public function test_menu_akun_dan_notifikasi_ada_di_bar_atas(): void
    {
        $this->actingAs($this->as('hrd@talentrank.test'))->get('/hrd/dashboard')->assertOk()
            ->assertSee('id="notif-panel"', false)->assertSee('id="profile-menu"', false)
            ->assertSee('Profil saya')->assertSee('data-logout', false);
    }
}
