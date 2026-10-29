<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Akun demo per role + data contoh alur promosi.
     * Lokal: password semua "password". Production: wajib set DEMO_SEED_PASSWORD di env.
     */
    public function run(): void
    {
        $password = app()->isProduction() ? env('DEMO_SEED_PASSWORD') : 'password';

        if (blank($password) || (app()->isProduction() && strlen($password) < 12)) {
            throw new RuntimeException('Set DEMO_SEED_PASSWORD (minimal 12 karakter) sebelum seeding di production.');
        }

        foreach (['admin', 'hrd', 'manager', 'direktur', 'karyawan'] as $roleName) {
            Role::firstOrCreate(['role_name' => $roleName]);
        }
        $roles = Role::pluck('id', 'role_name');

        $accounts = [
            ['admin', 'Admin Sistem', 'admin@talentrank.test', true],
            ['hrd', 'Sri Wahyuni', 'hrd@talentrank.test', true],
            ['manager', 'Dimas Prakoso', 'atasan@talentrank.test', true],
            ['manager', 'Ratna Sari', 'ratna.sari@talentrank.test', true],
            ['manager', 'Andi Kurniawan', 'andi.kurniawan@talentrank.test', true],
            ['manager', 'Yusuf Hidayat', 'yusuf.hidayat@talentrank.test', true],
            ['manager', 'Lestari Handayani', 'lestari.h@talentrank.test', true],
            ['manager', 'Wawan Setiawan', 'wawan.setiawan@talentrank.test', false],
            ['direktur', 'Bambang Hartono', 'direktur@talentrank.test', true],
            ['karyawan', 'Rian Pratama', 'karyawan@talentrank.test', true],
        ];

        foreach ($accounts as [$role, $name, $email, $active]) {
            User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'password' => $password,
                'role_id' => $roles[$role],
            ])->forceFill(['is_active' => $active])->save();
        }

        $this->call(PromotionDemoSeeder::class);
    }
}
