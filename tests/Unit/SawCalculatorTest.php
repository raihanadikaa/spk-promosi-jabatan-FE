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
        // Nilai terkecil 0 → kolom digeser +1: (0+1)/(3+1). Sebelumnya 0,0 (semua di atas 0 dianggap sama buruknya).
        $this->assertSame(0.25, $result['b']['normalized']['C2']);
    }

    public function test_cost_nol_tetap_membedakan_nilai_kecil_dan_besar(): void
    {
        $result = (new SawCalculator)->calculate([['code' => 'C1', 'weight' => 1.0, 'type' => 'cost']], [
            'nol' => ['C1' => 0], 'satu' => ['C1' => 1], 'tiga_puluh' => ['C1' => 30],
        ]);

        $this->assertSame(1.0, $result['nol']['normalized']['C1']);
        $this->assertSame(0.5, $result['satu']['normalized']['C1']);
        $this->assertSame(round(1 / 31, 4), $result['tiga_puluh']['normalized']['C1']);
        $this->assertSame(['nol', 'satu', 'tiga_puluh'], array_keys($result));
    }

    public function test_cost_tanpa_nilai_nol_memakai_rumus_standar(): void
    {
        $this->assertSame(0.0, SawCalculator::costOffset(2));
        $this->assertSame(1.0, SawCalculator::costOffset(0));

        $result = (new SawCalculator)->calculate([['code' => 'C1', 'weight' => 1.0, 'type' => 'cost']], [
            'a' => ['C1' => 2], 'b' => ['C1' => 8],
        ]);
        $this->assertSame(0.25, $result['b']['normalized']['C1']); // 2/8, tidak berubah
    }

    public function test_tanpa_alternatif_mengembalikan_array_kosong(): void
    {
        $this->assertSame([], (new SawCalculator)->calculate($this->criteria, []));
    }
}
