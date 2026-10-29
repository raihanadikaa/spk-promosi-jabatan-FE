<?php

namespace App\Support;

/**
 * Menu sidebar per role. Hak akses mengikuti matriks di dokumen ROLE.
 */
class Navigation
{
    public static function for(string $role): array
    {
        return match ($role) {
            'admin' => [
                ['label' => 'Beranda', 'route' => 'admin.dashboard', 'icon' => 'home'],
                ['label' => 'Pengguna & role', 'route' => 'admin.users', 'icon' => 'users'],
            ],
            'hrd' => [
                ['label' => 'Beranda', 'route' => 'hrd.dashboard', 'icon' => 'home'],
                ['label' => 'Karyawan', 'route' => 'hrd.employees', 'icon' => 'id-card'],
                ['label' => 'Periode penilaian', 'route' => 'hrd.periods', 'icon' => 'calendar'],
                ['label' => 'Kriteria & bobot', 'route' => 'hrd.criteria', 'icon' => 'sliders'],
                ['label' => 'Proses SAW', 'route' => 'hrd.saw', 'icon' => 'calculator'],
                ['label' => 'Ranking', 'route' => 'hrd.ranking', 'icon' => 'trophy', 'match' => 'hrd.ranking*'],
            ],
            'manager' => [
                ['label' => 'Beranda', 'route' => 'manager.dashboard', 'icon' => 'home'],
                ['label' => 'Penilaian bawahan', 'route' => 'manager.assessments', 'icon' => 'clipboard', 'match' => 'manager.assessments*'],
                ['label' => 'Ranking tim', 'route' => 'manager.ranking', 'icon' => 'trophy'],
            ],
            'direktur' => [
                ['label' => 'Beranda', 'route' => 'direktur.dashboard', 'icon' => 'home'],
                ['label' => 'Ranking & rekomendasi', 'route' => 'direktur.ranking', 'icon' => 'trophy', 'match' => 'direktur.ranking*'],
            ],
            'karyawan' => [
                ['label' => 'Skor saya', 'route' => 'karyawan.dashboard', 'icon' => 'user'],
            ],
            default => [],
        };
    }
}
