@php
    use App\Support\Format;

    $rows = collect($ranking)->sortBy('id');
    $excluded = collect($employees)->where('status', '!=', 'submitted');
    $raws = $rows->pluck('raw');
    $dec = fn ($code) => $code === 'C1' ? 1 : 0;
@endphp

<x-app-layout title="Proses SAW" subtitle="Jejak perhitungan lengkap {{ $period['name'] }}: dari nilai mentah sampai skor akhir.">
    <x-slot:actions>
        <form method="POST" action="{{ route('demo.action') }}">
            @csrf
            <input type="hidden" name="action" value="saw.run">
            <x-btn type="submit" icon="refresh">Hitung ulang ranking</x-btn>
        </form>
    </x-slot:actions>

    @if ($excluded->isNotEmpty())
        <div class="mb-6 flex gap-3 rounded-panel border border-warn/30 bg-warn-bg px-4 py-3 text-warn" role="note">
            <x-icon name="alert" class="mt-0.5" />
            <p>
                <span class="font-semibold">{{ $excluded->count() }} karyawan belum ikut dihitung</span> karena penilaiannya belum terkirim:
                {{ $excluded->pluck('name')->join(', ', ' dan ') }}.
            </p>
        </div>
    @endif

    <div x-data="{ step: 1 }">
        {{-- Tiga langkah SAW: memang berurutan, jadi diberi nomor --}}
        <div class="mb-4 grid gap-2 sm:grid-cols-3" role="tablist" aria-label="Langkah perhitungan">
            @foreach ([1 => ['Matriks keputusan', 'Nilai mentah X'], 2 => ['Normalisasi', 'R = X ÷ max, atau min ÷ X'], 3 => ['Skor akhir', 'V = Σ bobot × R']] as $n => [$label, $hint])
                <button type="button" role="tab" :aria-selected="(step === {{ $n }}).toString()" @click="step = {{ $n }}"
                        class="flex items-center gap-3 rounded-panel border px-4 py-3 text-left transition-colors duration-150"
                        :class="step === {{ $n }} ? 'border-brand bg-surface' : 'border-line bg-surface/60 hover:bg-surface'">
                    <span class="num grid size-7 shrink-0 place-items-center rounded-full text-sm font-bold"
                          :class="step === {{ $n }} ? 'bg-brand text-white' : 'bg-canvas text-ink-muted'">{{ $n }}</span>
                    <span>
                        <span class="block font-semibold">{{ $label }}</span>
                        <span class="block text-sm text-ink-muted">{{ $hint }}</span>
                    </span>
                </button>
            @endforeach
        </div>

        <x-panel flush>
            <div class="overflow-x-auto">
                <table class="data-table min-w-[860px]">
                    <thead>
                        <tr>
                            <th scope="col">Karyawan</th>
                            @foreach ($criteria as $c)
                                <th scope="col" class="text-right">
                                    <span class="inline-flex items-center gap-1.5"><x-criteria-dot :code="$c['code']" /> {{ $c['name'] }}</span>
                                    <span class="block text-xs font-normal">{{ $c['type'] === 'cost' ? 'cost' : 'benefit' }} · {{ $c['weight'] }}%</span>
                                </th>
                            @endforeach
                            <th scope="col" class="text-right" x-show="step === 3">Skor V</th>
                            <th scope="col" class="text-right" x-show="step === 3">Peringkat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td class="font-semibold">{{ $row['name'] }}</td>
                                @foreach ($criteria as $c)
                                    @php $code = $c['code']; @endphp
                                    <td class="num text-right">
                                        <span x-show="step === 1">{{ Format::number($row['raw'][$code], $dec($code)) }}</span>
                                        <span x-show="step === 2" x-cloak>{{ Format::score($row['normalized'][$code]) }}</span>
                                        <span x-show="step === 3" x-cloak>{{ Format::score($row['contributions'][$code]) }}</span>
                                    </td>
                                @endforeach
                                <td class="num text-right font-bold" x-show="step === 3" x-cloak>{{ Format::score($row['score']) }}</td>
                                <td class="text-right" x-show="step === 3" x-cloak><x-rank :value="$row['rank']" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot x-show="step === 1">
                        <tr class="text-sm text-ink-muted">
                            <td class="px-4 py-2.5">Acuan normalisasi</td>
                            @foreach ($criteria as $c)
                                <td class="num px-4 py-2.5 text-right">
                                    {{ $c['type'] === 'cost' ? 'min' : 'max' }}
                                    <span class="font-semibold text-ink">{{ Format::number($c['type'] === 'cost' ? $raws->min($c['code']) : $raws->max($c['code']), $dec($c['code'])) }}</span>
                                </td>
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </div>
            <p class="border-t border-line px-5 py-3 text-sm text-ink-muted">
                <span x-show="step === 1">Nilai dari form atasan. Masa kerja dihitung dari tanggal bergabung sampai {{ Format::date($period['end']) }}.</span>
                <span x-show="step === 2" x-cloak>Benefit: nilai ÷ nilai tertinggi. Cost (kedisiplinan): nilai terendah ÷ nilai. Hasil selalu 0–1.</span>
                <span x-show="step === 3" x-cloak>Setiap kolom = nilai ternormalisasi × bobot. Skor V adalah jumlahnya; peringkat diurutkan dari V terbesar.</span>
            </p>
        </x-panel>
    </div>
</x-app-layout>
