<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentDetail;
use App\Models\AssessmentPeriod;
use App\Models\CriteriaWeight;
use App\Models\Criterion;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\RankingResult;
use App\Models\User;
use App\Services\RankingService;
use Illuminate\Database\Seeder;

/**
 * Data contoh (simulasi) untuk menguji alur: 12 karyawan, 2 periode,
 * 9 penilaian terkirim, ranking terhitung, 1 keputusan direktur.
 */
class PromotionDemoSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::pluck('id', 'email');

        $supervisors = [
            'Operasional' => $users['atasan@talentrank.test'],
            'Keuangan' => $users['ratna.sari@talentrank.test'],
            'Pemasaran' => $users['andi.kurniawan@talentrank.test'],
            'Teknologi Informasi' => $users['yusuf.hidayat@talentrank.test'],
            'SDM & Umum' => $users['lestari.h@talentrank.test'],
        ];
        $departments = collect($supervisors)->keys()
            ->mapWithKeys(fn ($name) => [$name => Department::firstOrCreate(['department_name' => $name])->id]);

        $positions = collect([['Staf', 1], ['Staf Senior', 2], ['Supervisor', 3], ['Manajer', 4]])
            ->mapWithKeys(fn ($p) => [$p[0] => Position::firstOrCreate(['position_name' => $p[0]], ['level' => $p[1]])->id]);

        // [nama, NIK, departemen, jabatan, tgl bergabung, status, [C2, C3, C4, C5] | null, waktu kirim]
        $people = [
            ['Ahmad Fauzi', '3201170301', 'Operasional', 'Staf Senior', '2017-03-01', 'submitted', [92, 88, 2, 5], '2026-10-20 09:12'],
            ['Rian Pratama', '3201200817', 'Operasional', 'Staf', '2020-08-17', 'submitted', [85, 80, 4, 3], '2026-10-20 09:40'],
            ['Siti Rahmawati', '3201160111', 'Keuangan', 'Staf Senior', '2016-01-11', 'submitted', [88, 90, 1, 4], '2026-10-18 14:05'],
            ['Dewi Lestari', '3201190502', 'Pemasaran', 'Staf', '2019-05-02', 'submitted', [90, 78, 3, 2], '2026-10-21 10:30'],
            ['Bagas Saputra', '3201180910', 'Teknologi Informasi', 'Staf Senior', '2018-09-10', 'submitted', [87, 92, 5, 6], '2026-10-22 16:18'],
            ['Nadia Putri', '3201210201', 'SDM & Umum', 'Staf', '2021-02-01', null, null, null],
            ['Yoga Pradana', '3201191104', 'Operasional', 'Staf', '2019-11-04', 'draft', [78, 75, 6, 1], null],
            ['Intan Permata', '3201200316', 'Keuangan', 'Staf', '2020-03-16', 'submitted', [80, 82, 2, 2], '2026-10-18 15:22'],
            ['Fajar Nugroho', '3201220110', 'Teknologi Informasi', 'Staf', '2022-01-10', null, null, null],
            ['Lina Marlina', '3201150720', 'Pemasaran', 'Staf Senior', '2015-07-20', 'submitted', [84, 85, 3, 3], '2026-10-21 11:02'],
            ['Hendra Wijaya', '3201161003', 'Operasional', 'Staf Senior', '2016-10-03', 'submitted', [83, 86, 8, 4], '2026-10-23 08:47'],
            ['Putri Ayu Lestari', '3201180423', 'SDM & Umum', 'Staf', '2018-04-23', 'submitted', [86, 81, 2, 3], '2026-10-24 13:30'],
        ];

        $previousPeriod = AssessmentPeriod::create([
            'period_name' => 'Semester 1 2026', 'start_date' => '2026-01-01', 'end_date' => '2026-06-30',
            'assessment_deadline' => '2026-05-15', 'status' => AssessmentPeriod::CLOSED, 'calculated_at' => '2026-06-20 10:00',
        ]);
        $period = AssessmentPeriod::create([
            'period_name' => 'Semester 2 2026', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31',
            'assessment_deadline' => '2026-11-14', 'status' => AssessmentPeriod::ONGOING,
        ]);

        $criteria = Criterion::orderBy('sort_order')->get()->keyBy('code');
        $weights = ['C1' => 15, 'C2' => 30, 'C3' => 25, 'C4' => 15, 'C5' => 15];
        foreach ([$previousPeriod, $period] as $p) {
            foreach ($weights as $code => $w) {
                CriteriaWeight::create(['period_id' => $p->id, 'criteria_id' => $criteria[$code]->id, 'weight_value' => $w]);
            }
        }

        $employees = [];
        foreach ($people as [$name, $nik, $dept, $position, $join, $status, $scores, $submittedAt]) {
            $employee = Employee::create([
                'name' => $name,
                'nik' => $nik,
                'department_id' => $departments[$dept],
                'position_id' => $positions[$position],
                'supervisor_id' => $supervisors[$dept],
                'user_id' => $name === 'Rian Pratama' ? $users['karyawan@talentrank.test'] : null,
                'join_date' => $join,
            ]);
            $employees[$name] = $employee;

            if ($status === null) {
                continue;
            }

            $assessment = Assessment::create([
                'employee_id' => $employee->id,
                'period_id' => $period->id,
                'assessor_id' => $supervisors[$dept],
                'status' => $status,
                'submitted_at' => $submittedAt,
            ]);
            $values = ['C1' => $employee->tenureAt($period->end_date)] + array_combine(['C2', 'C3', 'C4', 'C5'], $scores);
            foreach ($values as $code => $value) {
                AssessmentDetail::create(['assessment_id' => $assessment->id, 'criteria_id' => $criteria[$code]->id, 'raw_score' => $value]);
            }
        }

        // Skor akhir periode lalu (tanpa jejak lengkap), untuk perbandingan "vs semester lalu".
        $previousScores = ['Siti Rahmawati' => 0.8932, 'Ahmad Fauzi' => 0.8710, 'Bagas Saputra' => 0.8604, 'Lina Marlina' => 0.8275,
            'Dewi Lestari' => 0.8120, 'Hendra Wijaya' => 0.8013, 'Rian Pratama' => 0.7905, 'Putri Ayu Lestari' => 0.7740, 'Intan Permata' => 0.7552];
        $rank = 0;
        foreach ($previousScores as $name => $score) {
            RankingResult::create([
                'employee_id' => $employees[$name]->id, 'period_id' => $previousPeriod->id,
                'final_score' => $score, 'rank' => ++$rank, 'is_recommended' => $rank <= 3,
                'approval_status' => $rank <= 3 ? ($rank === 1 ? RankingResult::APPROVED : RankingResult::REJECTED) : null,
                'approved_by' => $rank <= 3 ? $users['direktur@talentrank.test'] : null,
                'approval_note' => $rank <= 3 ? 'Keputusan periode lalu.' : null,
                'approved_at' => $rank <= 3 ? '2026-06-25 09:00' : null,
            ]);
        }

        app(RankingService::class)->calculate($period);

        RankingResult::where('period_id', $period->id)->where('employee_id', $employees['Siti Rahmawati']->id)->update([
            'approval_status' => RankingResult::APPROVED,
            'approval_note' => 'Disetujui untuk posisi Supervisor Keuangan mulai Januari 2027.',
            'approved_by' => $users['direktur@talentrank.test'],
            'approved_at' => '2026-10-27 10:15',
        ]);
    }
}
