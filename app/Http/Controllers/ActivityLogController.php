<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Queries\PromotionData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Log aktivitas (dibaca HRD dan admin): siapa melakukan apa dan kapan.
 * Hanya baca; log tidak bisa diubah atau dihapus dari aplikasi.
 */
class ActivityLogController extends Controller
{
    public const PER_PAGE = 25;

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        return view('shared.activity', [
            'logs' => $this->query($filters)->with('period:id,period_name')->paginate(self::PER_PAGE)->withQueryString(),
            'filters' => $filters,
            'categories' => ActivityLog::CATEGORIES,
            'periods' => collect(PromotionData::periods())->pluck('name', 'id')->all(),
            'exportRoute' => str_replace('activity', 'activity.export', $request->route()->getName()),
        ]);
    }

    /** CSV log sesuai filter yang sedang dipakai; dibaca per 500 baris. */
    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $safe = fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : (string) $v; // cegah formula injection di Excel

        return response()->streamDownload(function () use ($filters, $safe) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Waktu', 'Pengguna', 'Kategori', 'Aksi', 'Keterangan', 'Periode', 'Alamat IP'], ';');
            $this->query($filters)->with('period:id,period_name')->chunk(500, function ($logs) use ($out, $safe) {
                foreach ($logs as $log) {
                    fputcsv($out, array_map($safe, [
                        $log->created_at->format('Y-m-d H:i:s'),
                        $log->user_name ?? 'Sistem',
                        ActivityLog::CATEGORIES[$log->category] ?? $log->category,
                        $log->action,
                        $log->description,
                        $log->period?->period_name ?? '',
                        $log->ip_address ?? '',
                    ]), ';');
                }
            });
            fclose($out);
        }, 'log-aktivitas-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        $v = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', 'string', 'in:' . implode(',', array_keys(ActivityLog::CATEGORIES))],
            'periode' => ['nullable', 'integer'],
            'dari' => ['nullable', 'date_format:Y-m-d'],
            'sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dari'],
        ], ['sampai.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.']);

        return [
            'q' => $v['q'] ?? null,
            'kategori' => $v['kategori'] ?? null,
            'periode' => isset($v['periode']) ? (int) $v['periode'] : null,
            'dari' => $v['dari'] ?? null,
            'sampai' => $v['sampai'] ?? null,
        ];
    }

    private function query(array $f): Builder
    {
        $like = '%' . addcslashes((string) $f['q'], '%_\\') . '%';

        return ActivityLog::query()
            ->when(filled($f['q']), fn ($q) => $q->where(fn ($w) => $w->where('description', 'like', $like)->orWhere('user_name', 'like', $like)))
            ->when($f['kategori'], fn ($q, $c) => $q->where('category', $c))
            ->when($f['periode'], fn ($q, $p) => $q->where('period_id', $p))
            ->when($f['dari'], fn ($q, $d) => $q->where('created_at', '>=', $d . ' 00:00:00'))
            ->when($f['sampai'], fn ($q, $d) => $q->where('created_at', '<=', $d . ' 23:59:59'))
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
