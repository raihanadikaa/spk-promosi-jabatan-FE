<?php

namespace App\Http\Controllers\Concerns;

use App\Queries\PromotionData;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Halaman ranking, detail kandidat, dan unduhan CSV yang dipakai bersama HRD, atasan, dan direktur.
 */
trait ShowsRanking
{
    protected function noPeriodView(): View
    {
        return view('shared.no-period');
    }

    /** Validasi parameter pencarian/filter ranking dari query string. */
    protected function rankingFilters(Request $request): array
    {
        $v = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:recommended,pending,approved,rejected,not_recommended'],
        ]);

        return ['q' => $v['q'] ?? null, 'department_id' => isset($v['department']) ? (int) $v['department'] : null, 'status' => $v['status'] ?? null];
    }

    protected function rankingView(Request $request, ?string $detailRoute, string $scope, ?array $employeeIds = null, ?string $exportRoute = null): View
    {
        $period = PromotionData::currentPeriod();
        if (! $period) {
            return $this->noPeriodView();
        }

        $filters = $this->rankingFilters($request);
        if ($employeeIds !== null) {
            $filters['employee_ids'] = $employeeIds;
        }

        return view('shared.ranking', [
            'ranking' => PromotionData::rankingPage($period, $filters),
            'hasAny' => PromotionData::rankingCount($period) > 0,
            'filters' => $filters,
            'departments' => PromotionData::departments(),
            'period' => $period,
            'detailRoute' => $detailRoute,
            'exportRoute' => $exportRoute,
            'scope' => $scope,
            'stale' => PromotionData::staleReasons($period),
        ]);
    }

    protected function candidateView(int $employeeId, array $back, bool $canDecide): View
    {
        $period = PromotionData::currentPeriod() ?? abort(404);
        $row = PromotionData::rankingRow($period, $employeeId) ?? abort(404);

        return view('shared.candidate', [
            'row' => $row,
            'criteria' => PromotionData::criteria($period['id']),
            'period' => $period,
            'total' => PromotionData::rankingCount($period),
            'bounds' => PromotionData::bounds($period),
            'back' => $back,
            'canDecide' => $canDecide && $row['approval_status'] === 'pending' && PromotionData::isOpen($period),
        ]);
    }

    /** CSV seluruh ranking; dibaca per 200 baris supaya memori tetap kecil walau datanya banyak. */
    protected function exportCsv(): StreamedResponse
    {
        $period = PromotionData::currentPeriod() ?? abort(404);
        $criteria = PromotionData::criteria($period['id']);
        $filename = 'ranking-' . str($period['name'])->slug() . '.csv';
        $safe = fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : (string) $v; // cegah formula injection di Excel
        $labels = ['pending' => 'Menunggu keputusan', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];

        return response()->streamDownload(function () use ($period, $criteria, $safe, $labels) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM supaya Excel membaca UTF-8
            fputcsv($out, array_merge(
                ['Peringkat', 'Nama', 'NIK', 'Departemen', 'Jabatan'],
                array_map(fn ($c) => $c['name'] . ' (kontribusi)', $criteria),
                ['Skor akhir', 'Status rekomendasi'],
            ), ';');

            PromotionData::rankingQuery($period)->chunk(200, function ($results) use ($out, $period, $criteria, $safe, $labels) {
                foreach (PromotionData::rankingRows($results, $period) as $row) {
                    fputcsv($out, array_map($safe, array_merge(
                        [$row['rank'], $row['name'], $row['nik'], $row['department'], $row['position']],
                        array_map(fn ($c) => number_format($row['contributions'][$c['code']] ?? 0, 4, ',', ''), $criteria),
                        [number_format($row['score'], 4, ',', ''), $labels[$row['approval_status']] ?? 'Tidak direkomendasikan'],
                    )), ';');
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
