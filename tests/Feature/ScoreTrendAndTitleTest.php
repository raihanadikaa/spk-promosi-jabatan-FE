<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Grafik tren skor menandai naik/turun dengan jelas, dan judul tab browser selalu "TalentRank". */
class ScoreTrendAndTitleTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function history(array $scores): array
    {
        return collect($scores)->values()->map(fn ($s, $i) => [
            'period_id' => $i + 1, 'period' => 'Periode ' . ($i + 1), 'score' => $s, 'rank' => 10 - $i, 'total' => 20,
        ])->all();
    }

    public function test_tren_naik_ditandai_hijau_dengan_selisihnya(): void
    {
        $view = $this->blade('<x-score-trend :history="$h" :current-period-id="2" />', ['h' => $this->history([0.7, 0.8123])]);

        $view->assertSee('Naik')->assertSee('+0,1123')->assertSee('stroke-ok', false)->assertDontSee('stroke-bad', false);
        $view->assertSee('Periode 1')->assertSee('Periode 2')->assertSee('(sekarang)');
    }

    public function test_tren_turun_dan_tetap(): void
    {
        $this->blade('<x-score-trend :history="$h" />', ['h' => $this->history([0.9, 0.75])])
            ->assertSee('Turun')->assertSee('−0,1500')->assertSee('stroke-bad', false);

        $this->blade('<x-score-trend :history="$h" />', ['h' => $this->history([0.8, 0.8])])
            ->assertSee('Tetap')->assertSee('±0');
    }

    public function test_satu_periode_dan_kosong_tetap_aman(): void
    {
        $this->blade('<x-score-trend :history="$h" />', ['h' => $this->history([0.8])])->assertSee('0,8000')->assertDontSee('Naik');
        $this->blade('<x-score-trend :history="$h" />', ['h' => []])->assertSee('Belum ada riwayat skor.');
    }

    public function test_judul_tab_selalu_talentrank(): void
    {
        $this->get('/login')->assertSee('<title>TalentRank</title>', false);

        foreach (['hrd@talentrank.test' => '/hrd/dashboard', 'karyawan@talentrank.test' => '/karyawan/dashboard', 'direktur@talentrank.test' => '/direktur/ranking/laporan'] as $email => $url) {
            $this->actingAs(User::where('email', $email)->first())->get($url)->assertOk()->assertSee('<title>TalentRank</title>', false);
        }
    }
}
