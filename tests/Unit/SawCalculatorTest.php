<?php

namespace Tests\Unit;

use App\Services\SawCalculator;
use PHPUnit\Framework\TestCase;

class SawCalculatorTest extends TestCase
{
    private array $criteria = [
        ['code' => 'C1', 'weight' => 0.6, 'type' => 'benefit'],
        ['code' => 'C2', 'weight' => 0.4, 'type' => 'cost'],
    ];

    public function test_normalisasi_benefit_dan_cost_serta_skor_akhir(): void
    {
        $result = (new SawCalculator)->calculate($this->criteria, [
            'a' => ['C1' => 80, 'C2' => 2],
            'b' => ['C1' => 100, 'C2' => 4],
        ]);

        // benefit: x / max
        $this->assertSame(0.8, $result['a']['normalized']['C1']);
        $this->assertSame(1.0, $result['b']['normalized']['C1']);
        // cost: min / x
        $this->assertSame(1.0, $result['a']['normalized']['C2']);
        $this->assertSame(0.5, $result['b']['normalized']['C2']);
        // V = Σ w·r
        $this->assertSame(0.88, $result['a']['score']);
        $this->assertSame(0.8, $result['b']['score']);
    }

    public function test_ranking_urut_dari_skor_tertinggi(): void
    {
        $result = (new SawCalculator)->calculate($this->criteria, [
            'a' => ['C1' => 80, 'C2' => 2],
            'b' => ['C1' => 100, 'C2' => 4],
        ]);

        $this->assertSame(['a', 'b'], array_keys($result));
        $this->assertSame(1, $result['a']['rank']);
        $this->assertSame(2, $result['b']['rank']);
    }

    public function test_cost_bernilai_nol_tidak_membagi_dengan_nol(): void
    {
        $result = (new SawCalculator)->calculate($this->criteria, [
            'a' => ['C1' => 50, 'C2' => 0],
            'b' => ['C1' => 50, 'C2' => 3],
        ]);

        $this->assertSame(1.0, $result['a']['normalized']['C2']);
        $this->assertSame(0.0, $result['b']['normalized']['C2']);
    }

    public function test_tanpa_alternatif_mengembalikan_array_kosong(): void
    {
        $this->assertSame([], (new SawCalculator)->calculate($this->criteria, []));
    }
}
