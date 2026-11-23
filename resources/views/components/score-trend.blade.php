@props(['history', 'currentPeriodId' => null, 'list' => true])
{{--
    Tren skor & peringkat antar periode. Disertai daftar angka supaya tetap terbaca tanpa grafik.

    Grafik memenuhi lebar panel. Garis digambar di SVG yang meregang (preserveAspectRatio="none",
    garis tetap tipis lewat vector-effect), sedangkan titik, angka, dan label adalah HTML biasa
    yang diposisikan dengan persen — jadi ukuran tulisan selalu normal, tidak ikut membesar di layar lebar.
    Ruas naik berwarna hijau, turun merah, dan besar perubahannya tertulis di tengah ruas.
--}}
@php
    use App\Support\Format;
    use Illuminate\Support\Str;

    $history = array_values($history);
    $n = count($history);
    $scores = array_column($history, 'score');
    $lo = max(0, floor((min($scores ?: [0]) - 0.05) * 10) / 10);
    $hi = min(1, ceil((max($scores ?: [1]) + 0.05) * 10) / 10);
    if ($hi - $lo < 0.2) { $hi = min(1, $lo + 0.2); $lo = max(0, $hi - 0.2); }

    // Posisi dalam persen bidang grafik; sisa ruang di tepi untuk label angka & tooltip.
    $x = fn ($i) => $n > 1 ? 6 + $i * 88 / ($n - 1) : 50;
    $y = fn ($v) => 12 + (1 - ($v - $lo) / max(0.0001, $hi - $lo)) * 80;
    $ticks = [$hi, ($lo + $hi) / 2, $lo];

    $points = [];
    foreach ($history as $i => $p) {
        $delta = $i > 0 ? round($p['score'] - $history[$i - 1]['score'], 4) : null;
        $points[] = $p + [
            'x' => round($x($i), 2),
            'y' => round($y($p['score']), 2),
            'delta' => $delta,
            'trend' => $delta === null ? null : ($delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat')),
            'current' => $p['period_id'] === $currentPeriodId,
        ];
    }

    $last = $points[$n - 1] ?? null;
    $prev = $points[$n - 2] ?? null;
    $tone = ['up' => 'text-ok', 'down' => 'text-bad', 'flat' => 'text-ink-muted'];
    $stroke = ['up' => 'stroke-ok', 'down' => 'stroke-bad', 'flat' => 'stroke-ink-faint'];
    $pill = ['up' => 'bg-ok-bg text-ok', 'down' => 'bg-bad-bg text-bad', 'flat' => 'bg-info-bg text-info'];
    $word = ['up' => 'naik', 'down' => 'turun', 'flat' => 'tetap'];
    $arrow = ['up' => 'arrow-up', 'down' => 'arrow-down'];

    $summary = collect($points)->map(fn ($p) => $p['period'] . ' skor ' . Format::score($p['score'])
        . ($p['trend'] ? ' (' . $word[$p['trend']] . ($p['trend'] !== 'flat' ? ' ' . Format::score(abs($p['delta'])) : '') . ')' : ''))->implode(', ');
@endphp
<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if ($n === 0)
        <p class="text-ink-muted">Belum ada riwayat skor.</p>
    @else
        @if ($n > 1)
            {{-- Ringkasan perubahan terakhir: jelas naik/turun tanpa harus membaca grafik --}}
            <div class="mb-5 flex flex-wrap items-center gap-x-4 gap-y-2">
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-bold {{ $pill[$last['trend']] }}">
                    @if ($last['trend'] !== 'flat')<x-icon :name="$arrow[$last['trend']]" class="size-4" />@endif
                    {{ ucfirst($word[$last['trend']]) }}@if ($last['trend'] !== 'flat') <span class="num">{{ Format::score(abs($last['delta'])) }}</span>@endif
                </span>
                <span class="text-sm text-ink-muted">
                    dibanding {{ $prev['period'] }}
                    (<span class="num">{{ Format::score($prev['score']) }}</span> → <span class="num font-semibold text-ink">{{ Format::score($last['score']) }}</span>)
                </span>
                <span class="text-sm text-ink-muted">
                    Peringkat <span class="num">#{{ $prev['rank'] }}</span> → <span class="num font-semibold {{ $last['rank'] < $prev['rank'] ? 'text-ok' : ($last['rank'] > $prev['rank'] ? 'text-bad' : 'text-ink') }}">#{{ $last['rank'] }}</span>
                    <span class="num">dari {{ $last['total'] }}</span>
                </span>
            </div>

            <figure>
                <div class="relative ml-11 mr-1 h-52 sm:h-60" role="img" aria-label="Tren skor: {{ $summary }}">
                    {{-- Garis bantu & label sumbu Y --}}
                    @foreach ($ticks as $t)
                        <div class="absolute inset-x-0 border-t border-dashed border-line" style="top: {{ round($y($t), 2) }}%" aria-hidden="true">
                            <span class="num absolute -left-11 w-9 -translate-y-1/2 text-right text-xs text-ink-faint">{{ Format::number($t, 2) }}</span>
                        </div>
                    @endforeach

                    {{-- Area & ruas garis (meregang mengikuti lebar; ketebalan garis tetap) --}}
                    <svg class="tr-bar absolute inset-0 size-full overflow-visible" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                        <polygon points="{{ collect($points)->map(fn ($p) => $p['x'] . ',' . $p['y'])->implode(' ') }} {{ $last['x'] }},92 {{ $points[0]['x'] }},92" class="fill-brand/[0.06]" />
                        @foreach ($points as $i => $p)
                            @if ($i > 0)
                                <line x1="{{ $points[$i - 1]['x'] }}" y1="{{ $points[$i - 1]['y'] }}" x2="{{ $p['x'] }}" y2="{{ $p['y'] }}"
                                      class="{{ $stroke[$p['trend']] }}" stroke-width="3" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                            @endif
                        @endforeach
                    </svg>

                    {{-- Besar perubahan di tengah tiap ruas --}}
                    @if ($n <= 8)
                        @foreach ($points as $i => $p)
                            @if ($i > 0)
                                <div class="pointer-events-none absolute" aria-hidden="true"
                                     style="left: {{ ($points[$i - 1]['x'] + $p['x']) / 2 }}%; top: {{ ($points[$i - 1]['y'] + $p['y']) / 2 }}%; transform: translate(-50%, -50%)">
                                    <span class="tr-pop num inline-flex items-center gap-0.5 whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-bold shadow-sm ring-2 ring-surface {{ $pill[$p['trend']] }}" style="--i: {{ $i }}">
                                        @if ($p['trend'] !== 'flat')<x-icon :name="$arrow[$p['trend']]" class="size-3" />{{ $p['delta'] > 0 ? '+' : '−' }}{{ Format::score(abs($p['delta'])) }}@else ±0 @endif
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    @endif

                    {{-- Titik, angka skor, dan tooltip --}}
                    @foreach ($points as $i => $p)
                        <div class="group absolute" style="left: {{ $p['x'] }}%; top: {{ $p['y'] }}%; transform: translate(-50%, -50%)">
                            <span class="num pointer-events-none absolute bottom-full left-1/2 mb-1.5 -translate-x-1/2 whitespace-nowrap text-xs font-bold {{ $p['current'] ? 'text-brand' : 'text-ink' }}" aria-hidden="true">{{ Format::score($p['score']) }}</span>
                            <span @class([
                                'tr-pop block cursor-default rounded-full border-2 border-brand transition-transform duration-150 group-hover:scale-125',
                                'size-4 bg-brand ring-4 ring-brand/15' => $p['current'],
                                'size-3 bg-surface' => ! $p['current'],
                            ]) style="--i: {{ $i }}"></span>
                            <span @class([
                                'pointer-events-none absolute top-full z-10 mt-2 hidden whitespace-nowrap rounded-ctl bg-ink px-2.5 py-1.5 text-xs text-white shadow-lg group-hover:block',
                                'left-0' => $i === 0 && $n > 1,
                                'right-0' => $i === $n - 1 && $n > 1,
                                'left-1/2 -translate-x-1/2' => $i > 0 && $i < $n - 1,
                            ]) aria-hidden="true">
                                <span class="block font-semibold">{{ $p['period'] }}</span>
                                <span class="num">Skor {{ Format::score($p['score']) }} · #{{ $p['rank'] }} dari {{ $p['total'] }}</span>
                            </span>
                        </div>
                    @endforeach
                </div>

                {{-- Label periode (sumbu X) --}}
                <div class="relative ml-11 mr-1 mt-3 h-5" aria-hidden="true">
                    @foreach ($points as $i => $p)
                        {{-- Label ujung kiri/kanan rata ke tepi supaya tidak terpotong di layar sempit --}}
                        <span @class([
                            'absolute top-0 whitespace-nowrap text-xs',
                            'font-semibold text-ink' => $p['current'],
                            'text-ink-muted' => ! $p['current'],
                        ]) style="{{ $i === 0 && $n > 1 ? 'left: 0' : ($i === $n - 1 && $n > 1 ? 'right: 0' : 'left: ' . $p['x'] . '%; transform: translateX(-50%)') }}">{{ Str::limit($p['period'], $n > 4 ? 12 : 24) }}</span>
                    @endforeach
                </div>
            </figure>
        @endif
        @if ($list || $n === 1)
        <ul class="{{ $n > 1 ? 'mt-3' : '' }} divide-y divide-line text-sm">
            @foreach (array_reverse($points) as $p)
                <li class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 py-2">
                    <span class="font-semibold">{{ $p['period'] }} @if ($p['current'])<span class="font-normal text-ink-muted">(sekarang)</span>@endif</span>
                    <span class="num text-ink-muted">
                        @if ($p['trend'] && $p['trend'] !== 'flat')
                            <span class="mr-1 inline-flex items-center gap-0.5 font-semibold {{ $tone[$p['trend']] }}"><x-icon :name="$arrow[$p['trend']]" class="size-3" />{{ Format::score(abs($p['delta'])) }}</span>
                        @endif
                        <strong class="text-ink">{{ Format::score($p['score']) }}</strong> · #{{ $p['rank'] }} dari {{ $p['total'] }}
                    </span>
                </li>
            @endforeach
        </ul>
        @endif
    @endif
</div>
