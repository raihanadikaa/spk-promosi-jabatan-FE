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
                ['label' => 'Log aktivitas', 'route' => 'admin.activity', 'icon' => 'history', 'match' => 'admin.activity*'],
            ],
            'hrd' => [
                ['label' => 'Beranda', 'route' => 'hrd.dashboard', 'icon' => 'home'],
                ['label' => 'Karyawan', 'route' => 'hrd.employees', 'icon' => 'id-card'],
                ['label' => 'Departemen & jabatan', 'route' => 'hrd.organization', 'icon' => 'building'],
                ['label' => 'Periode penilaian', 'route' => 'hrd.periods', 'icon' => 'calendar'],
                ['label' => 'Kriteria & bobot', 'route' => 'hrd.criteria', 'icon' => 'sliders'],
                ['label' => 'Monitor penilaian', 'route' => 'hrd.monitor', 'icon' => 'clipboard'],
                ['label' => 'Proses SAW', 'route' => 'hrd.saw', 'icon' => 'calculator', 'match' => 'hrd.saw*'],
                ['label' => 'Ranking', 'route' => 'hrd.ranking', 'icon' => 'trophy', 'match' => 'hrd.ranking*'],
                ['label' => 'Analisis sensitivitas', 'route' => 'hrd.sensitivity', 'icon' => 'scale'],
                ['label' => 'Log aktivitas', 'route' => 'hrd.activity', 'icon' => 'history', 'match' => 'hrd.activity*'],
            ],
            'manager' => [
                ['label' => 'Beranda', 'route' => 'manager.dashboard', 'icon' => 'home'],
                ['label' => 'Penilaian bawahan', 'route' => 'manager.assessments', 'icon' => 'clipboard', 'match' => 'manager.assessments*'],
                ['label' => 'Ranking tim', 'route' => 'manager.ranking', 'icon' => 'trophy'],
            ],
            'direktur' => [
                ['label' => 'Beranda', 'route' => 'direktur.dashboard', 'icon' => 'home'],
                ['label' => 'Ranking', 'route' => 'direktur.ranking', 'icon' => 'trophy', 'match' => 'direktur.ranking*'],
                ['label' => 'Persetujuan promosi', 'route' => 'direktur.approvals', 'icon' => 'check-circle'],
                ['label' => 'Analisis sensitivitas', 'route' => 'direktur.sensitivity', 'icon' => 'scale'],
            ],
            'karyawan' => [
                ['label' => 'Skor saya', 'route' => 'karyawan.dashboard', 'icon' => 'user'],
            ],
            default => [],
        };
    }
}
