@php
    use App\Support\Format;

    $rows = collect($ranking);
    $recommended = $rows->where('recommended', true);
    $waiting = $recommended->where('approval_status', 'pending');
    $decided = $recommended->where('approval_status', '!=', 'pending');
@endphp

<x-app-layout title="Rekomendasi promosi {{ $period['name'] }}"
              subtitle="Sistem merekomendasikan {{ $recommended->count() }} kandidat dengan skor tertinggi. Keputusan akhir ada di Anda.">
    <x-slot:actions>
        <x-btn variant="secondary" :href="route('direktur.ranking')" icon="trophy">Ranking lengkap</x-btn>
    </x-slot:actions>

    <section aria-labelledby="menunggu">
        <h2 id="menunggu" class="mb-3 flex items-center gap-2 text-md font-bold">
            Menunggu keputusan Anda
            <span class="num rounded-ctl bg-warn-bg px-2 text-sm text-warn">{{ $waiting->count() }}</span>
        </h2>

        @if ($waiting->isEmpty())
            <x-panel><x-empty-state icon="check-circle" title="Tidak ada yang menunggu" body="Semua rekomendasi periode ini sudah Anda putuskan." /></x-panel>
        @else
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach ($waiting as $row)
                    <x-panel>
                        <div class="flex items-start gap-4">
                            <x-rank :value="$row['rank']" />
                            <div class="min-w-0 flex-1">
                                <p class="font-bold">{{ $row['name'] }}</p>
                                <p class="text-sm text-ink-muted">{{ $row['position'] }} · {{ $row['department'] }} · {{ Format::number($row['tenure'], 1) }} th</p>
                            </div>
                            <p class="num text-lg font-bold">{{ Format::score($row['score']) }}</p>
                        </div>
                        <x-score-bar :parts="$row['contributions']" class="mt-4" />
                        <div class="mt-4 flex items-center justify-between gap-3">
                            <p class="text-sm text-ink-muted">Dinilai oleh {{ $row['assessor'] }}</p>
                            <x-btn size="sm" :href="route('direktur.ranking.show', $row['id'])">Tinjau & putuskan<span class="sr-only"> untuk {{ $row['name'] }}</span></x-btn>
                        </div>
                    </x-panel>
                @endforeach
            </div>
            <x-criteria-legend class="mt-3" />
        @endif
    </section>

    @if ($decided->isNotEmpty())
        <x-panel class="mt-8" title="Sudah diputuskan" flush>
            <ul class="divide-y divide-line">
                @foreach ($decided as $row)
                    <li class="flex flex-wrap items-center gap-4 px-5 py-3.5">
                        <x-rank :value="$row['rank']" />
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('direktur.ranking.show', $row['id']) }}" class="font-semibold hover:text-brand hover:underline">{{ $row['name'] }}</a>
                            <p class="text-sm text-ink-muted">{{ $row['approval_note'] }}</p>
                        </div>
                        <x-status :value="$row['approval_status']" />
                        <p class="text-sm text-ink-muted">{{ Format::dateTime($row['approval_at']) }}</p>
                    </li>
                @endforeach
            </ul>
        </x-panel>
    @endif
</x-app-layout>
