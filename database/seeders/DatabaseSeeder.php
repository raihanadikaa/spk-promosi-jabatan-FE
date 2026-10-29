<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Akun demo untuk tiap role. Password semua: password
     */
    public function run(): void
    {
        $accounts = [
            'admin'    => ['Admin Sistem', 'admin@talentrank.test'],
            'hrd'      => ['Sri Wahyuni', 'hrd@talentrank.test'],
            'manager'  => ['Dimas Prakoso', 'atasan@talentrank.test'],
            'direktur' => ['Bambang Hartono', 'direktur@talentrank.test'],
            'karyawan' => ['Rian Pratama', 'karyawan@talentrank.test'],
        ];

        foreach ($accounts as $roleName => [$name, $email]) {
            $role = Role::firstOrCreate(['role_name' => $roleName]);

            User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => 'password', 'role_id' => $role->id],
            );
        }
    }
}
