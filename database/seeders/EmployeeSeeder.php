<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Setup Role
        $karyawanRole = Role::firstOrCreate(['role_name' => 'karyawan']);

        // 2. Siapkan data dummy Department & Position agar Foreign Key tidak error
        // Kita gunakan insertOrIgnore agar jika ID 1 sudah ada, tidak error
        try {
            DB::table('departments')->insertOrIgnore([
                'id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('positions')->insertOrIgnore([
                'id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Abaikan jika ada error (misal tabel sudah terisi atau kolom unik bentrok)
        }

        $this->command->info('1. Membersihkan data user lama (jika ada)...');
        // Hapus user karyawan lama agar tidak bentrok saat insert ulang
        DB::table('users')->where('email', 'like', 'karyawan%@talentrank.test')->delete();
        DB::table('employees')->where('nik', 'like', 'EMP%')->delete();

        $this->command->info('2. Membuat 1000 User...');
        $usersData = [];
        $now = Carbon::now();
        $hashedPassword = Hash::make('password'); 

        for ($i = 1; $i <= 1000; $i++) {
            $usersData[] = [
                'name' => "Karyawan Dummy {$i}",
                'email' => "karyawan{$i}@talentrank.test",
                'password' => $hashedPassword, 
                'role_id' => $karyawanRole->id, 
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($usersData, 500) as $chunk) {
            DB::table('users')->insert($chunk);
        }

        $this->command->info('3. Mengambil ID User yang baru dibuat...');
        $createdUsers = User::where('email', 'like', 'karyawan%@talentrank.test')->get();

        $this->command->info('4. Membuat 1000 Data Karyawan...');
        $employeesData = [];

        foreach ($createdUsers as $index => $user) {
            $no = $index + 1;
            $employeesData[] = [
                'user_id'       => $user->id,
                'name'          => $user->name, 
                'nik'           => 'EMP' . str_pad($no, 4, '0', STR_PAD_LEFT),
                'department_id' => 1, // Mengacu ke ID 1 yang baru kita buat
                'position_id'   => 1, // Mengacu ke ID 1 yang baru kita buat
                'supervisor_id' => null,       
                'join_date'     => now()->format('Y-m-d'),
                'is_active'     => true,       
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }

        foreach (array_chunk($employeesData, 500) as $chunk) {
            DB::table('employees')->insert($chunk);
        }

        $this->command->info('✅ SUKSES! 1000 Data Karyawan berhasil dibuat.');
    }
}