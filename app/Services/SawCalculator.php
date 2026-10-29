<?php

namespace App\Services;

/**
 * Perhitungan Simple Additive Weighting (SAW).
 *
 * Normalisasi:
 *   benefit → r = x / max(x)
 *   cost    → r = min(x) / x
 * Skor akhir: V = Σ wj · rij
 */
class SawCalculator
{
    /**
     * @param  array<int, array{code: string, weight: float, type: string}>  $criteria  bobot dalam pecahan (0–1)
     * @param  array<int|string, array<string, float>>  $matrix  [alternatifId => [kodeKriteria => nilai mentah]]
     * @return array<int|string, array{normalized: array<string, float>, contributions: array<string, float>, score: float, rank: int}>
     */
    public function calculate(array $criteria, array $matrix): array
    {
        if ($matrix === []) {
            return [];
        }

        $bounds = [];
        foreach ($criteria as $c) {
            $column = array_column($matrix, $c['code']);
            $bounds[$c['code']] = ['max' => max($column), 'min' => min($column)];
        }

        $results = [];
        foreach ($matrix as $id => $row) {
            $normalized = [];
            $contributions = [];

            foreach ($criteria as $c) {
                $x = (float) $row[$c['code']];
                $b = $bounds[$c['code']];

                $r = $c['type'] === 'cost'
                    ? ($x > 0 ? $b['min'] / $x : 1.0)
                    : ($b['max'] > 0 ? $x / $b['max'] : 0.0);

                $normalized[$c['code']] = round($r, 4);
                $contributions[$c['code']] = round($r * $c['weight'], 4);
            }

            $results[$id] = [
                'normalized' => $normalized,
                'contributions' => $contributions,
                'score' => round(array_sum($contributions), 4),
                'rank' => 0,
            ];
        }

        uasort($results, fn ($a, $b) => $b['score'] <=> $a['score']);

        $rank = 0;
        foreach ($results as &$result) {
            $result['rank'] = ++$rank;
        }

        return $results;
    }
}
