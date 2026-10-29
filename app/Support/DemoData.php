<?php

namespace App\Support;

use App\Services\SawCalculator;
use Carbon\CarbonImmutable;

/**
 * Data contoh selama backend belum tersambung.
 * Struktur mengikuti ERD (employees, criteria, criteria_weights, assessments, ranking_results).
 * Semua skor dihitung lewat SawCalculator, tidak diketik manual.
 */
class DemoData
{
    /** Jumlah kandidat teratas yang direkomendasikan untuk promosi. */
    public const RECOMMENDED_COUNT = 3;

    public static function criteria(): array
    {
        return [
            ['code' => 'C1', 'name' => 'Masa kerja', 'weight' => 15, 'type' => 'benefit', 'unit' => 'tahun',
                'input' => 'auto', 'help' => 'Dihitung otomatis dari tanggal bergabung sampai akhir periode.'],
            ['code' => 'C2', 'name' => 'Kinerja', 'weight' => 30, 'type' => 'benefit', 'unit' => 'skor 0–100',
                'input' => 'score', 'help' => 'Pencapaian target kerja dan hasil project selama periode.'],
            ['code' => 'C3', 'name' => 'Kompetensi', 'weight' => 25, 'type' => 'benefit', 'unit' => 'skor 0–100',
                'input' => 'score', 'help' => 'Keahlian teknis, kepemimpinan, dan komunikasi.'],
            ['code' => 'C4', 'name' => 'Kedisiplinan', 'weight' => 15, 'type' => 'cost', 'unit' => 'hari',
                'input' => 'count', 'help' => 'Jumlah hari terlambat atau absen tanpa keterangan. Makin kecil makin baik.'],
            ['code' => 'C5', 'name' => 'Pelatihan & sertifikasi', 'weight' => 15, 'type' => 'benefit', 'unit' => 'sertifikat',
                'input' => 'count', 'help' => 'Jumlah pelatihan atau sertifikasi yang diselesaikan dan relevan dengan jabatan.'],
        ];
    }

    public static function periods(): array
    {
        return [
            ['id' => 2, 'name' => 'Semester 2 2026', 'start' => '2026-07-01', 'end' => '2026-12-31', 'status' => 'ongoing',
                'deadline' => '2026-11-14'],
            ['id' => 1, 'name' => 'Semester 1 2026', 'start' => '2026-01-01', 'end' => '2026-06-30', 'status' => 'closed',
                'deadline' => '2026-05-15'],
        ];
    }

    public static function currentPeriod(): array
    {
        return self::periods()[0];
    }

    public static function departments(): array
    {
        return ['Operasional', 'Keuangan', 'Pemasaran', 'Teknologi Informasi', 'SDM & Umum'];
    }

    public static function positions(): array
    {
        return [
            ['name' => 'Staf', 'level' => 1],
            ['name' => 'Staf Senior', 'level' => 2],
            ['name' => 'Supervisor', 'level' => 3],
            ['name' => 'Manajer', 'level' => 4],
        ];
    }

    /** Atasan langsung per departemen. */
    public static function assessors(): array
    {
        return [
            'Operasional' => 'Dimas Prakoso',
            'Keuangan' => 'Ratna Sari',
            'Pemasaran' => 'Andi Kurniawan',
            'Teknologi Informasi' => 'Yusuf Hidayat',
            'SDM & Umum' => 'Sri Wahyuni',
        ];
    }

    /**
     * raw: nilai mentah C2–C5 (C1 dihitung dari join_date). null = belum dinilai.
     * status: submitted | draft | pending (belum dinilai)
     */
    private static function rawEmployees(): array
    {
        return [
            [1, 'Ahmad Fauzi', '3201170301', 'Operasional', 'Staf Senior', '2017-03-01', 'submitted', [92, 88, 2, 5], '2026-10-20 09:12'],
            [2, 'Rian Pratama', '3201200817', 'Operasional', 'Staf', '2020-08-17', 'submitted', [85, 80, 4, 3], '2026-10-20 09:40'],
            [3, 'Siti Rahmawati', '3201160111', 'Keuangan', 'Staf Senior', '2016-01-11', 'submitted', [88, 90, 1, 4], '2026-10-18 14:05'],
            [4, 'Dewi Lestari', '3201190502', 'Pemasaran', 'Staf', '2019-05-02', 'submitted', [90, 78, 3, 2], '2026-10-21 10:30'],
            [5, 'Bagas Saputra', '3201180910', 'Teknologi Informasi', 'Staf Senior', '2018-09-10', 'submitted', [87, 92, 5, 6], '2026-10-22 16:18'],
            [6, 'Nadia Putri', '3201210201', 'SDM & Umum', 'Staf', '2021-02-01', 'pending', null, null],
            [7, 'Yoga Pradana', '3201191104', 'Operasional', 'Staf', '2019-11-04', 'draft', [78, 75, 6, 1], null],
            [8, 'Intan Permata', '3201200316', 'Keuangan', 'Staf', '2020-03-16', 'submitted', [80, 82, 2, 2], '2026-10-18 15:22'],
            [9, 'Fajar Nugroho', '3201220110', 'Teknologi Informasi', 'Staf', '2022-01-10', 'pending', null, null],
            [10, 'Lina Marlina', '3201150720', 'Pemasaran', 'Staf Senior', '2015-07-20', 'submitted', [84, 85, 3, 3], '2026-10-21 11:02'],
            [11, 'Hendra Wijaya', '3201161003', 'Operasional', 'Staf Senior', '2016-10-03', 'submitted', [83, 86, 8, 4], '2026-10-23 08:47'],
            [12, 'Putri Ayu Lestari', '3201180423', 'SDM & Umum', 'Staf', '2018-04-23', 'submitted', [86, 81, 2, 3], '2026-10-24 13:30'],
        ];
    }

    public static function employees(): array
    {
        $periodEnd = CarbonImmutable::parse(self::currentPeriod()['end']);
        $assessors = self::assessors();
        $levels = array_column(self::positions(), 'level', 'name');

        $employees = [];
        foreach (self::rawEmployees() as [$id, $name, $nik, $dept, $position, $join, $status, $raw, $submittedAt]) {
            $tenure = round(CarbonImmutable::parse($join)->diffInMonths($periodEnd) / 12, 1);

            $employees[$id] = [
                'id' => $id,
                'name' => $name,
                'nik' => $nik,
                'department' => $dept,
                'position' => $position,
                'level' => $levels[$position],
                'join_date' => $join,
                'tenure' => $tenure,
                'assessor' => $assessors[$dept],
                'status' => $status,
                'submitted_at' => $submittedAt,
                'raw' => $raw === null ? null : [
                    'C1' => $tenure, 'C2' => $raw[0], 'C3' => $raw[1], 'C4' => $raw[2], 'C5' => $raw[3],
                ],
            ];
        }

        return $employees;
    }

    public static function employee(int $id): ?array
    {
        return self::employees()[$id] ?? null;
    }

    public static function subordinatesOf(string $assessor): array
    {
        return array_filter(self::employees(), fn ($e) => $e['assessor'] === $assessor);
    }

    /** Status approval direktur untuk kandidat yang direkomendasikan. */
    private static function approvals(): array
    {
        return [
            3 => ['status' => 'approved', 'at' => '2026-10-27 10:15', 'note' => 'Disetujui untuk posisi Supervisor Keuangan mulai Januari 2027.'],
        ];
    }

    /**
     * Ranking periode berjalan: hanya assessment berstatus submitted yang dihitung.
     */
    public static function ranking(): array
    {
        $criteria = array_map(fn ($c) => [
            'code' => $c['code'], 'weight' => $c['weight'] / 100, 'type' => $c['type'],
        ], self::criteria());

        $employees = self::employees();
        $matrix = [];
        foreach ($employees as $id => $e) {
            if ($e['status'] === 'submitted') {
                $matrix[$id] = $e['raw'];
            }
        }

        $approvals = self::approvals();
        $rows = [];
        foreach ((new SawCalculator)->calculate($criteria, $matrix) as $id => $result) {
            $recommended = $result['rank'] <= self::RECOMMENDED_COUNT;
            $approval = $approvals[$id] ?? null;

            $rows[$id] = $employees[$id] + $result + [
                'recommended' => $recommended,
                'approval_status' => $recommended ? ($approval['status'] ?? 'pending') : null,
                'approval_at' => $approval['at'] ?? null,
                'approval_note' => $approval['note'] ?? null,
                'previous_score' => self::previousScores()[$id] ?? null,
            ];
        }

        return $rows;
    }

    /** Skor akhir periode Semester 1 2026 (sudah ditutup). */
    public static function previousScores(): array
    {
        return [1 => 0.8710, 2 => 0.7905, 3 => 0.8932, 4 => 0.8120, 5 => 0.8604, 8 => 0.7552, 10 => 0.8275, 11 => 0.8013, 12 => 0.7740];
    }

    public static function users(): array
    {
        return [
            ['name' => 'Admin Sistem', 'email' => 'admin@talentrank.test', 'role' => 'admin', 'active' => true, 'last_login' => '2026-10-29 08:02'],
            ['name' => 'Sri Wahyuni', 'email' => 'hrd@talentrank.test', 'role' => 'hrd', 'active' => true, 'last_login' => '2026-10-29 07:55'],
            ['name' => 'Dimas Prakoso', 'email' => 'atasan@talentrank.test', 'role' => 'manager', 'active' => true, 'last_login' => '2026-10-28 16:40'],
            ['name' => 'Ratna Sari', 'email' => 'ratna.sari@talentrank.test', 'role' => 'manager', 'active' => true, 'last_login' => '2026-10-27 09:13'],
            ['name' => 'Andi Kurniawan', 'email' => 'andi.k@talentrank.test', 'role' => 'manager', 'active' => true, 'last_login' => '2026-10-26 13:20'],
            ['name' => 'Yusuf Hidayat', 'email' => 'yusuf.h@talentrank.test', 'role' => 'manager', 'active' => true, 'last_login' => '2026-10-22 10:05'],
            ['name' => 'Bambang Hartono', 'email' => 'direktur@talentrank.test', 'role' => 'direktur', 'active' => true, 'last_login' => '2026-10-27 10:11'],
            ['name' => 'Rian Pratama', 'email' => 'karyawan@talentrank.test', 'role' => 'karyawan', 'active' => true, 'last_login' => '2026-10-25 19:48'],
            ['name' => 'Wawan Setiawan', 'email' => 'wawan.s@talentrank.test', 'role' => 'manager', 'active' => false, 'last_login' => '2026-06-30 17:02'],
        ];
    }

    public static function roleLabels(): array
    {
        return [
            'admin' => 'Admin',
            'hrd' => 'HRD',
            'manager' => 'Atasan',
            'direktur' => 'Direktur',
            'karyawan' => 'Karyawan',
        ];
    }
}
