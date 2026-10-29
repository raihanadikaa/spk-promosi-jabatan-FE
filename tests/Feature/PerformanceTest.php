<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentDetail;
use App\Models\AssessmentPeriod;
use App\Models\Criterion;
use App\Models\Employee;
use App\Models\User;
use App\Queries\PromotionData;
use App\Services\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Anti N+1: jumlah query per halaman harus tetap sama walau jumlah karyawan bertambah banyak,
 * dan tetap di bawah batas wajar.
 */
class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const MAX_QUERIES = 35;

    private const PAGES = [
        'hrd@talentrank.test' => ['/hrd/dashboard', '/hrd/karyawan', '/hrd/periode', '/hrd/kriteria', '/hrd/proses-saw', '/hrd/ranking', '/hrd/ranking/1'],
        'atasan@talentrank.test' => ['/atasan/dashboard', '/atasan/penilaian', '/atasan/penilaian/7', '/atasan/ranking'],
        'direktur@talentrank.test' => ['/direktur/dashboard', '/direktur/ranking'],
        'karyawan@talentrank.test' => ['/karyawan/dashboard'],
        'admin@talentrank.test' => ['/admin/dashboard', '/admin/pengguna'],
    ];

    public function test_jumlah_query_tidak_tumbuh_mengikuti_jumlah_data(): void
    {
        $before = $this->measure();

        $this->addEmployees(60);

        $after = $this->measure();

        foreach ($before as $url => $count) {
            $this->assertLessThanOrEqual(self::MAX_QUERIES, $after[$url], "$url menjalankan {$after[$url]} query");
            $this->assertSame($count, $after[$url], "$url: query naik dari $count ke {$after[$url]} setelah data bertambah (indikasi N+1)");
        }
    }

    public function test_halaman_daftar_dipaginasi(): void
    {
        $this->addEmployees(40);
        $hrd = User::where('email', 'hrd@talentrank.test')->first();

        $this->actingAs($hrd)->get('/hrd/karyawan')->assertOk()->assertSee('dari 52', false);
        $this->actingAs($hrd)->get('/hrd/karyawan?page=4')->assertOk()->assertSee('46–52', false);
        $this->actingAs($hrd)->get('/hrd/ranking')->assertOk()->assertSee('dari 49', false);
        $this->actingAs($hrd)->get('/hrd/karyawan?q=Tambahan%2007')->assertOk()->assertSee('dari 1', false);
    }

    /** @return array<string, int> */
    private function measure(): array
    {
        $counts = [];
        foreach (self::PAGES as $email => $urls) {
            $user = User::where('email', $email)->first();
            foreach ($urls as $url) {
                PromotionData::flush();
                DB::flushQueryLog();
                DB::enableQueryLog();
                $this->actingAs($user)->get($url)->assertOk();
                $counts[$url] = count(DB::getQueryLog());
                DB::disableQueryLog();
            }
        }

        return $counts;
    }

    private function addEmployees(int $n): void
    {
        $period = AssessmentPeriod::where('status', 'ongoing')->first();
        $criteria = Criterion::all()->keyBy('code');
        $supervisor = User::where('email', 'atasan@talentrank.test')->value('id');
        $template = Employee::where('name', 'Ahmad Fauzi')->first();

        foreach (range(1, $n) as $i) {
            $e = Employee::create([
                'name' => sprintf('Tambahan %02d', $i), 'nik' => sprintf('99%08d', $i),
                'department_id' => $template->department_id, 'position_id' => $template->position_id,
                'supervisor_id' => $supervisor, 'join_date' => '2019-01-01',
            ]);
            $a = Assessment::create(['employee_id' => $e->id, 'period_id' => $period->id, 'assessor_id' => $supervisor, 'status' => 'submitted', 'submitted_at' => now()]);
            foreach (['C1' => 7, 'C2' => 60 + $i % 30, 'C3' => 70, 'C4' => 1 + $i % 5, 'C5' => $i % 4] as $code => $v) {
                AssessmentDetail::create(['assessment_id' => $a->id, 'criteria_id' => $criteria[$code]->id, 'raw_score' => $v]);
            }
        }

        app(RankingService::class)->calculate($period);
    }
}
