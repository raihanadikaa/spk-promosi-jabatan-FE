@props(['history', 'currentPeriodId' => null, 'list' => true])
{{-- Tren skor & peringkat antar periode (SVG sederhana). Disertai daftar angka supaya tetap terbaca tanpa grafik. --}}
@php
    use App\Support\Format;

    $n = count($history);
    // Ukuran dasar kecil supaya teks tetap terbaca di panel sempit & HP; lebar tampil dibatasi agar tidak membesar berlebihan.
    $w = 340;
    $h = 150;
    $pad = ['l' => 34, 'r' => 10, 't' => 12, 'b' => 26];
    $scores = array_column($history, 'score');
    $lo = max(0, floor((min($scores ?: [0]) - 0.05) * 10) / 10);
    $hi = min(1, ceil((max($scores ?: [1]) + 0.05) * 10) / 10);
    if ($hi - $lo < 0.2) { $hi = min(1, $lo + 0.2); $lo = max(0, $hi - 0.2); }
    $x = fn ($i) => $n > 1 ? $pad['l'] + $i * ($w - $pad['l'] - $pad['r']) / ($n - 1) : ($w / 2);
    $y = fn ($v) => $pad['t'] + (1 - ($v - $lo) / max(0.0001, $hi - $lo)) * ($h - $pad['t'] - $pad['b']);
    $points = collect($history)->values()->map(fn ($p, $i) => round($x($i), 1) . ',' . round($y($p['score']), 1))->implode(' ');
    $ticks = [$lo, ($lo + $hi) / 2, $hi];
@endphp
<div {{ $attributes }}>
    @if ($n === 0)
        <p class="text-ink-muted">Belum ada riwayat skor.</p>
    @else
        @if ($n > 1)
            <svg viewBox="0 0 {{ $w }} {{ $h }}" class="h-auto w-full max-w-lg" role="img" aria-label="Tren skor dari {{ $history[0]['period'] }} sampai {{ $history[$n - 1]['period'] }}">
                @foreach ($ticks as $t)
                    <line x1="{{ $pad['l'] }}" x2="{{ $w - $pad['r'] }}" y1="{{ $y($t) }}" y2="{{ $y($t) }}" class="stroke-line" stroke-width="1" />
                    <text x="{{ $pad['l'] - 6 }}" y="{{ $y($t) + 4 }}" text-anchor="end" class="fill-ink-faint text-[11px]">{{ Format::number($t, 2) }}</text>
                @endforeach
                <polyline points="{{ $points }}" fill="none" class="stroke-brand" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />
                @foreach ($history as $i => $p)
                    <circle cx="{{ $x($i) }}" cy="{{ $y($p['score']) }}" r="{{ $p['period_id'] === $currentPeriodId ? 5.5 : 4 }}"
                            class="{{ $p['period_id'] === $currentPeriodId ? 'fill-brand' : 'fill-surface stroke-brand' }}" stroke-width="2">
                        <title>{{ $p['period'] }}: skor {{ Format::score($p['score']) }}, peringkat {{ $p['rank'] }} dari {{ $p['total'] }}</title>
                    </circle>
                    <text x="{{ $x($i) }}" y="{{ $h - 8 }}" text-anchor="{{ $i === 0 ? 'start' : ($i === $n - 1 ? 'end' : 'middle') }}" class="fill-ink-muted text-[11px]">{{ \Illuminate\Support\Str::limit($p['period'], $n > 3 ? 10 : 18) }}</text>
                @endforeach
            </svg>
        @endif
        @if ($list || $n === 1)
        <ul class="{{ $n > 1 ? 'mt-3' : '' }} divide-y divide-line text-sm">
            @foreach (array_reverse($history) as $p)
                <li class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 py-2">
                    <span class="font-semibold">{{ $p['period'] }} @if ($p['period_id'] === $currentPeriodId)<span class="font-normal text-ink-muted">(sekarang)</span>@endif</span>
                    <span class="num text-ink-muted"><strong class="text-ink">{{ Format::score($p['score']) }}</strong> · #{{ $p['rank'] }} dari {{ $p['total'] }}</span>
                </li>
            @endforeach
        </ul>
        @endif
    @endif
</div>
