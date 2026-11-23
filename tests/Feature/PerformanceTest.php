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
        'hrd@talentrank.test' => ['/hrd/dashboard', '/hrd/karyawan', '/hrd/periode', '/hrd/kriteria', '/hrd/proses-saw', '/hrd/ranking', '/hrd/ranking/1', '/hrd/sensitivitas', '/hrd/log-aktivitas'],
        'atasan@talentrank.test' => ['/atasan/dashboard', '/atasan/penilaian', '/atasan/penilaian/7', '/atasan/ranking'],
        'direktur@talentrank.test' => ['/direktur/dashboard', '/direktur/ranking', '/direktur/sensitivitas'],
        'karyawan@talentrank.test' => ['/karyawan/dashboard'],
        'admin@talentrank.test' => ['/admin/dashboard', '/admin/pengguna', '/admin/log-aktivitas'],
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

    /** Ribuan karyawan: analisis sensitivitas & grafik tetap selesai dalam waktu wajar dan hasilnya utuh. */
    public function test_analisis_sensitivitas_dan_grafik_dengan_ribuan_karyawan(): void
    {
        $this->addEmployees(2000);
        $hrd = User::where('email', 'hrd@talentrank.test')->first();
        PromotionData::flush();
        $total = \App\Models\RankingResult::where('period_id', AssessmentPeriod::where('status', 'ongoing')->value('id'))->count();

        $start = microtime(true);
        $this->actingAs($hrd)->get('/hrd/sensitivitas')->assertOk()->assertSee('Batas aman bobot')->assertSee(number_format($total, 0, ',', '.'));
        $seconds = microtime(true) - $start;
        $this->assertLessThan(15, $seconds, "Analisis sensitivitas {$total} karyawan butuh " . round($seconds, 1) . ' detik');

        PromotionData::flush();
        $distribution = PromotionData::scoreDistribution(PromotionData::currentPeriod());
        $this->assertSame($total, array_sum(array_column($distribution['bins'], 'total')));
        $this->actingAs($hrd)->get('/hrd/dashboard')->assertOk()->assertSee('Sebaran skor akhir');
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
