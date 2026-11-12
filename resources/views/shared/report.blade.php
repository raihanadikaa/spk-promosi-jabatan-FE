@php use App\Support\Format; @endphp
{{-- Laporan hasil ranking siap cetak. "Cetak / simpan PDF" memakai dialog cetak browser, jadi tanpa pustaka PDF. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan ranking {{ $period['name'] }} — TalentRank</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-canvas text-ink print:bg-white">
    <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-6 py-4 print:hidden" x-data>
        <a href="{{ $back }}" class="inline-flex items-center gap-1 text-sm font-semibold text-ink-muted hover:text-ink">
            <x-icon name="chevron-left" class="size-4" /> Kembali ke ranking
        </a>
        <x-btn icon="download" x-on:click="window.print()">Cetak / simpan PDF</x-btn>
    </div>

    <main class="mx-auto mb-10 max-w-5xl rounded-panel border border-line bg-surface p-8 shadow-sm print:m-0 print:max-w-none print:rounded-none print:border-0 print:p-0 print:shadow-none">
        <header class="flex items-start justify-between gap-6 border-b-2 border-ink pb-4">
            <div class="flex items-center gap-3">
                <x-logo />
                <div class="leading-tight">
                    <p class="text-md font-extrabold tracking-title">TalentRank</p>
                    <p class="text-xs text-ink-muted">Sistem Pendukung Keputusan Promosi Jabatan · metode SAW</p>
                </div>
            </div>
            <div class="text-right text-sm text-ink-muted">
                <p>Dicetak {{ Format::dateTime(now()->format('Y-m-d H:i')) }}</p>
                <p>oleh {{ auth()->user()->name }}</p>
            </div>
        </header>

        <h1 class="mt-6 text-xl font-bold tracking-title">Laporan hasil ranking promosi jabatan</h1>
        <dl class="mt-3 grid grid-cols-2 gap-x-8 gap-y-1 text-sm sm:grid-cols-4">
            <div><dt class="text-ink-muted">Periode</dt><dd class="font-semibold">{{ $period['name'] }}</dd></div>
            <div><dt class="text-ink-muted">Rentang</dt><dd class="font-semibold">{{ Format::date($period['start']) }} – {{ Format::date($period['end']) }}</dd></div>
            <div><dt class="text-ink-muted">Ranking dihitung</dt><dd class="font-semibold">{{ $period['calculated_at'] ? Format::dateTime($period['calculated_at']) : 'Belum dihitung' }}</dd></div>
            <div><dt class="text-ink-muted">Jumlah karyawan</dt><dd class="num font-semibold">{{ count($rows) }}</dd></div>
        </dl>

        <h2 class="mt-7 text-md font-bold">Kriteria dan bobot</h2>
        <table class="mt-2 w-full border-collapse text-sm">
            <thead>
                <tr class="border-b border-line-strong text-left text-xs uppercase tracking-wide text-ink-muted">
                    <th class="py-2 pr-3 font-semibold">Kode</th>
                    <th class="py-2 pr-3 font-semibold">Kriteria</th>
                    <th class="py-2 pr-3 font-semibold">Jenis</th>
                    <th class="py-2 text-right font-semibold">Bobot</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($criteria as $c)
                    <tr class="border-b border-line">
                        <td class="num py-1.5 pr-3 font-semibold">{{ $c['code'] }}</td>
                        <td class="py-1.5 pr-3">{{ $c['name'] }}</td>
                        <td class="py-1.5 pr-3">{{ $c['type'] === 'cost' ? 'Cost' : 'Benefit' }}</td>
                        <td class="num py-1.5 text-right">{{ $c['weight'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h2 class="mt-7 text-md font-bold">Hasil ranking</h2>
        <p class="mt-1 text-sm text-ink-muted">Kolom kriteria berisi nilai ternormalisasi (r). Skor akhir V = Σ bobot × r. {{ \App\Queries\PromotionData::RECOMMENDED_COUNT }} peringkat teratas direkomendasikan.</p>
        @if (empty($rows))
            <p class="mt-4 rounded-ctl border border-line bg-canvas px-4 py-6 text-center text-ink-muted">Ranking periode ini belum dihitung.</p>
        @else
            <table class="mt-3 w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b border-line-strong text-left text-xs uppercase tracking-wide text-ink-muted">
                        <th class="py-2 pr-2 font-semibold">#</th>
                        <th class="py-2 pr-3 font-semibold">Nama</th>
                        <th class="py-2 pr-3 font-semibold">Departemen / jabatan</th>
                        @foreach ($criteria as $c)<th class="py-2 pr-2 text-right font-semibold">{{ $c['code'] }}</th>@endforeach
                        <th class="py-2 pr-3 text-right font-semibold">Skor</th>
                        <th class="py-2 font-semibold">Keputusan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr class="break-inside-avoid border-b border-line align-top">
                            <td class="num py-1.5 pr-2 font-bold">{{ $row['rank'] }}</td>
                            <td class="py-1.5 pr-3">
                                <span class="font-semibold">{{ $row['name'] }}</span>
                                <span class="num block text-xs text-ink-muted">NIK {{ $row['nik'] }}</span>
                            </td>
                            <td class="py-1.5 pr-3">{{ $row['department'] }}<span class="block text-xs text-ink-muted">{{ $row['position'] }}</span></td>
                            @foreach ($criteria as $c)<td class="num py-1.5 pr-2 text-right">{{ Format::score($row['normalized'][$c['code']] ?? 0) }}</td>@endforeach
                            <td class="num py-1.5 pr-3 text-right font-bold">{{ Format::score($row['score']) }}</td>
                            <td class="py-1.5">
                                @if ($row['recommended'])
                                    {{ ['pending' => 'Menunggu keputusan', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'revision' => 'Review ulang HRD'][$row['approval_status']] ?? '–' }}
                                    @if ($row['approval_status'] === 'approved' && $row['proposed_position'])<span class="block text-xs text-ink-muted">Usulan: {{ $row['proposed_position'] }}</span>@endif
                                @else
                                    <span class="text-ink-muted">Tidak direkomendasikan</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="mt-12 grid break-inside-avoid grid-cols-2 gap-10 text-sm">
            @foreach (['Disiapkan oleh, HRD', 'Disetujui oleh, Direktur'] as $role)
                <div>
                    <p>{{ $role }}</p>
                    <div class="mt-16 border-t border-ink pt-1 text-ink-muted">Nama dan tanda tangan</div>
                </div>
            @endforeach
        </div>
    </main>
</body>
</html>
