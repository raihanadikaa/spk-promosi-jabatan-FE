<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Notifications\AssessmentReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Bahasa, preferensi tampilan (tersimpan di akun), notifikasi berbahasa pembaca, dan hapus akun oleh admin. */
class LocalePreferenceTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function as(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    /* ---------------- Bahasa ---------------- */

    public function test_tamu_bisa_ganti_bahasa_di_halaman_login(): void
    {
        $this->get('/login')->assertSee('Masuk ke TalentRank')->assertSee('<html lang="id"', false);

        $this->from('/login')->post('/bahasa', ['locale' => 'en'])->assertRedirect('/login');
        $this->get('/login')->assertSee('Sign in to TalentRank')->assertSee('Welcome')->assertDontSee('Selamat datang')
            ->assertSee('<html lang="en"', false);

        $this->post('/bahasa', ['locale' => 'fr'])->assertSessionHasErrors('locale');
    }

    public function test_bahasa_pilihan_login_tersimpan_ke_akun_dan_bertahan_setelah_keluar(): void
    {
        $this->from('/login')->post('/bahasa', ['locale' => 'en']);
        $this->post('/login', ['email' => 'hrd@talentrank.test', 'password' => 'password'])
            ->assertRedirect('/hrd/dashboard')->assertSessionHas('success', 'Welcome, Sri Wahyuni!');
        $this->assertSame('en', $this->as('hrd@talentrank.test')->locale);

        $this->get('/hrd/dashboard')->assertSee('HR dashboard')->assertSee('Notifications')->assertSee('Sign out');

        // Sesudah keluar, halaman login tetap berbahasa pengguna.
        $this->post('/logout')->assertRedirect('/login')->assertSessionHas('success', 'You have signed out.');
        $this->get('/login')->assertSee('Sign in to TalentRank');
    }

    public function test_bahasa_akun_berlaku_di_perangkat_lain_dan_pesan_server_ikut_bahasa(): void
    {
        $hrd = $this->as('hrd@talentrank.test');
        $this->actingAs($hrd)->post('/bahasa', ['locale' => 'en']);
        $this->assertSame('en', $hrd->fresh()->locale);

        // "Perangkat lain" = sesi baru tanpa data bahasa di sesi.
        $this->flushSession();
        $this->actingAs($hrd->fresh())->get('/akun?tab=tampilan')->assertSee('Display language')->assertSee('Text size');
        $this->actingAs($hrd->fresh())->put('/akun/profil', ['name' => 'Sri Wahyuni', 'phone' => ''])->assertSessionHas('success', 'No changes.');
        $this->actingAs($hrd->fresh())->post('/hrd/proses-saw')->assertSessionHas('success', fn ($m) => str_starts_with($m, 'Ranking recalculated from'));
    }

    public function test_notifikasi_tampil_dalam_bahasa_pembaca(): void
    {
        $manager = $this->as('atasan@talentrank.test');
        $manager->notify(new AssessmentReminder('Semester 2 2026', '14 Nov 2026', ['A', 'B', 'C', 'D', 'E', 'F', 'G']));

        $this->actingAs($manager)->get('/notifikasi')->assertSee('Pengingat penilaian')
            ->assertSee('7 penilaian periode Semester 2 2026 belum dikirim (batas 14 Nov 2026): A, B, C, D, E, dan 2 lainnya.');

        $manager->forceFill(['locale' => 'en'])->save();
        $this->actingAs($manager->fresh())->get('/notifikasi')->assertSee('Assessment reminder')
            ->assertSee('7 assessments for Semester 2 2026 have not been submitted (deadline 14 Nov 2026): A, B, C, D, E, and 2 more.');
    }

    /** Penjaga: setiap teks yang dibungkus __() di tampilan & kode punya terjemahan Inggris. */
    public function test_semua_teks_yang_diterjemahkan_punya_padanan_inggris(): void
    {
        $en = json_decode(file_get_contents(lang_path('en.json')), true);
        $missing = [];
        $files = array_merge(
            glob(resource_path('views/{,*/,*/*/}*.blade.php'), GLOB_BRACE),
            glob(app_path('{,*/,*/*/,*/*/*/}*.php'), GLOB_BRACE),
        );
        foreach ($files as $file) {
            if (preg_match('#views/(welcome|dashboard|profile/|auth/(?!login)|vendor/|layouts/navigation|components/(dropdown|modal|nav-link|responsive|primary|secondary|danger|text-input|input-|auth-session|application-logo))#', str_replace('\\', '/', $file))
                || preg_match('#Controllers/(Auth/|ProfileController)|Requests/(Auth/|ProfileUpdate)#', str_replace('\\', '/', $file))) {
                continue; // bawaan Breeze yang tidak dipakai aplikasi
            }
            preg_match_all("/__\\('((?:[^'\\\\]|\\\\.)*)'/", file_get_contents($file), $m);
            foreach ($m[1] as $key) {
                $key = str_replace("\\'", "'", $key);
                if (! array_key_exists($key, $en) && ! str_starts_with($key, 'role:')) {
                    $missing[] = $key . '  (' . basename($file) . ')';
                }
            }
        }
        foreach (['admin', 'hrd', 'manager', 'direktur', 'karyawan'] as $role) {
            $this->assertArrayHasKey("role:$role", $en);
        }
        $this->assertSame([], array_values(array_unique($missing)), 'Belum diterjemahkan');
    }

    /* ---------------- Preferensi tampilan ---------------- */

    public function test_tema_dan_ukuran_teks_tersimpan_di_akun_dan_dipakai_saat_halaman_dibuka(): void
    {
        $this->putJson('/akun/preferensi', ['theme' => 'dark'])->assertUnauthorized(); // tamu tidak bisa
        $user = $this->as('karyawan@talentrank.test');

        $this->actingAs($user)->putJson('/akun/preferensi', ['theme' => 'dark'])->assertOk()->assertJson(['saved' => ['theme' => 'dark']]);
        $this->actingAs($user)->putJson('/akun/preferensi', ['text_size' => 'lg'])->assertOk();
        $this->assertSame(['dark', 'lg'], [$user->fresh()->theme, $user->fresh()->text_size]);

        // Skrip awal halaman membawa pilihan akun (berlaku di perangkat mana pun, tanpa kedip).
        $this->actingAs($user->fresh())->get('/karyawan/dashboard')->assertSee('{"theme":"dark","text":"lg"}', false);

        $this->actingAs($user)->putJson('/akun/preferensi', ['theme' => 'neon'])->assertUnprocessable();
        $this->actingAs($user)->putJson('/akun/preferensi', ['text_size' => 'xl'])->assertUnprocessable();
    }

    /* ---------------- Hapus akun: hanya admin ---------------- */

    public function test_pengguna_tidak_bisa_menghapus_akun_sendiri(): void
    {
        $user = $this->as('karyawan@talentrank.test');
        $this->actingAs($user)->delete('/akun', ['password' => 'password'])->assertStatus(405);
        $this->assertModelExists($user);
        $this->actingAs($user)->get('/akun?tab=akun')->assertSee('penghapusan dilakukan oleh admin');
        $this->actingAs($user)->delete("/admin/pengguna/{$user->id}", ['password' => 'password'])->assertForbidden();
        $this->actingAs($this->as('hrd@talentrank.test'))->delete("/admin/pengguna/{$user->id}", ['password' => 'password'])->assertForbidden();
    }

    public function test_admin_menghapus_akun_dengan_password_dan_data_tetap_aman(): void
    {
        Storage::fake('local');
        $admin = $this->as('admin@talentrank.test');
        $target = $this->as('karyawan@talentrank.test');
        $employee = Employee::where('user_id', $target->id)->firstOrFail();
        DB::table('sessions')->insert(['id' => str_repeat('z', 40), 'user_id' => $target->id, 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($admin)->get('/admin/pengguna')->assertSee('Hapus akun');
        $this->actingAs($admin)->delete("/admin/pengguna/{$target->id}", ['password' => 'salah'])->assertSessionHasErrorsIn('deleteUser', 'password');
        $this->assertModelExists($target);

        $this->actingAs($admin)->delete("/admin/pengguna/{$target->id}", ['password' => 'password'])
            ->assertRedirect('/admin/pengguna')->assertSessionHas('success', 'Akun Rian Pratama dihapus.');
        $this->assertModelMissing($target);
        $this->assertNull($employee->fresh()->user_id);
        $this->assertDatabaseMissing('sessions', ['id' => str_repeat('z', 40)]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'akun.hapus', 'user_id' => $admin->id]);
    }

    public function test_admin_tidak_bisa_menghapus_diri_sendiri_atau_peran_aktif_terakhir(): void
    {
        $admin = $this->as('admin@talentrank.test');
        $this->actingAs($admin)->delete("/admin/pengguna/{$admin->id}", ['password' => 'password'])->assertSessionHasErrorsIn('deleteUser', 'password');

        foreach (['hrd@talentrank.test', 'direktur@talentrank.test'] as $email) {
            $user = $this->as($email);
            $this->actingAs($admin)->delete("/admin/pengguna/{$user->id}", ['password' => 'password'])
                ->assertSessionHasErrorsIn('deleteUser', 'password');
            $this->assertModelExists($user);
        }

        // Atasan boleh dihapus; bawahannya diberi peringatan agar HRD menunjuk atasan baru.
        $manager = $this->as('atasan@talentrank.test');
        $this->actingAs($admin)->delete("/admin/pengguna/{$manager->id}", ['password' => 'password'])
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'karyawan aktif kini tanpa atasan penilai'));
        $this->assertModelMissing($manager);
        $this->assertSame(0, Employee::where('supervisor_id', $manager->id)->count()); // pesan sesuai data: atasan mereka kini kosong
        $this->assertDatabaseHas('activity_logs', ['action' => 'akun.hapus', 'description' => 'Menghapus akun Dimas Prakoso (atasan@talentrank.test).']);
    }
}
