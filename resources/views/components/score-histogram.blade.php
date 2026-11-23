@props(['distribution'])
{{-- Histogram sebaran skor akhir (10 kelompok). Tanpa pustaka grafik: batang CSS, angka tetap terbaca pembaca layar. --}}
@php
    use App\Support\Format;

    $bins = $distribution['bins'];
    $max = max(1, $distribution['max']);
    $total = array_sum(array_column($bins, 'total'));
    $cutoff = $distribution['cutoff'];
@endphp
<div {{ $attributes }}>
    @if ($total === 0)
        <x-empty-state icon="chart" title="Belum ada skor" body="Grafik muncul setelah ranking dihitung." />
    @else
        <figure>
            <div class="flex h-44 items-end gap-1.5 sm:gap-2" role="img"
                 aria-label="Sebaran skor {{ $total }} karyawan: {{ collect($bins)->filter(fn ($b) => $b['total'])->map(fn ($b) => Format::number($b['from'], 1) . '–' . Format::number($b['to'], 1) . ' sebanyak ' . $b['total'])->implode(', ') }}">
                @foreach ($bins as $b)
                    @php
                        $h = $b['total'] / $max * 100;
                        $recH = $b['total'] ? $b['recommended'] / $b['total'] * 100 : 0;
                    @endphp
                    <div class="group relative flex h-full min-w-0 flex-1 flex-col justify-end">
                        @if ($b['total'])
                            <span class="num mb-1 text-center text-xs font-semibold text-ink-muted">{{ $b['total'] }}</span>
                        @endif
                        <div class="relative flex w-full flex-col-reverse overflow-hidden rounded-t-[4px] bg-c1/70 transition-colors group-hover:bg-c1"
                             style="height: {{ $b['total'] ? max(2, $h) : 0 }}%"
                             title="Skor {{ Format::number($b['from'], 1) }}–{{ Format::number($b['to'], 1) }}: {{ $b['total'] }} karyawan{{ $b['recommended'] ? ', ' . $b['recommended'] . ' direkomendasikan' : '' }}">
                            @if ($b['recommended'])
                                <span class="block w-full bg-brand" style="height: {{ $recH }}%"></span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-1.5 flex gap-1.5 border-t border-line-strong pt-1.5 sm:gap-2" aria-hidden="true">
                @foreach ($bins as $i => $b)
                    <span class="num min-w-0 flex-1 text-center text-[11px] text-ink-faint">{{ $i % 2 === 0 ? Format::number($b['from'], 1) : '' }}</span>
                @endforeach
            </div>
            <figcaption class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-ink-muted">
                <span class="inline-flex items-center gap-1.5"><span class="inline-block size-2.5 rounded-[3px] bg-c1/70"></span> Karyawan</span>
                <span class="inline-flex items-center gap-1.5"><span class="inline-block size-2.5 rounded-[3px] bg-brand"></span> Direkomendasikan</span>
                @if ($cutoff !== null)
                    <span>Skor minimal rekomendasi <strong class="num text-ink">{{ Format::score($cutoff) }}</strong></span>
                @endif
            </figcaption>
        </figure>
    @endif
</div>
