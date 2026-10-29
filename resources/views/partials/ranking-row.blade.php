{{-- Satu baris kandidat: peringkat, identitas, komposisi skor, skor akhir. $href opsional. --}}
@php $tag = $href ?? null ? 'a' : 'div'; @endphp
<{{ $tag }} @if ($href ?? null) href="{{ $href }}" @endif
    class="grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-x-4 gap-y-2 px-5 py-3.5 {{ ($href ?? null) ? 'transition-colors duration-150 hover:bg-canvas/60' : '' }} md:grid-cols-[auto_minmax(0,15rem)_minmax(0,1fr)_5.5rem]">
    <x-rank :value="$row['rank']" />
    <div class="min-w-0">
        <p class="truncate font-semibold text-ink">{{ $row['name'] }}</p>
        <p class="truncate text-sm text-ink-muted">{{ $row['position'] }} · {{ $row['department'] }}</p>
    </div>
    <x-score-bar :parts="$row['contributions']" class="col-span-3 row-start-2 md:col-span-1 md:row-start-auto" />
    <span class="num text-right text-md font-bold text-ink">{{ \App\Support\Format::score($row['score']) }}</span>
</{{ $tag }}>
