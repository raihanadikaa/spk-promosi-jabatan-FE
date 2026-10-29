@php
    use App\Support\Format;

    $dec = fn ($code) => $code === 'C1' ? 1 : 0;
    $open = $period['status'] === 'ongoing';
@endphp

<x-app-layout title="Proses SAW" subtitle="Jejak perhitungan {{ $period['name'] }}: dari nilai mentah sampai skor akhir.{{ $period['calculated_at'] ? ' Terakhir dihitung ' . Format::dateTime($period['calculated_at']) . '.' : '' }}">
    @if ($open)
        <x-slot:actions>
            <form method="POST" action="{{ route('hrd.saw.run') }}">
                @csrf
                <x-btn type="submit" icon="refresh">Hitung ulang ranking</x-btn>
            </form>
        </x-slot:actions>
    @endif

    @include('partials.stale-banner')

    @if ($excluded > 0 && $ranked > 0)
        <div class="mb-6 flex gap-3 rounded-panel border border-line bg-surface px-4 py-3 text-ink-muted" role="note">
            <x-icon name="info" class="mt-0.5" />
            <p>
                <span class="font-semibold text-ink">{{ $excluded }} karyawan aktif belum ada di perhitungan ini</span>
                karena penilaiannya belum terkirim atau terkirim setelah perhitungan terakhir.
                <a href="{{ route('hrd.employees', ['status' => 'pending']) }}" class="link">Lihat yang belum dinilai</a>
            </p>
        </div>
    @endif

    @if ($ranked === 0)
        <x-panel>
            <x-empty-state icon="calculator" title="Belum ada hasil perhitungan"
                           body="{{ $open ? 'Tekan Hitung ulang ranking setelah minimal satu penilaian terkirim.' : 'Periode ini ditutup tanpa perhitungan ranking.' }}" />
        </x-panel>
    @else
        <x-filter-bar placeholder="Cari nama atau NIK" :search="$q" :total="$ranking->total()" />

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
                @if ($ranking->isEmpty())
                    <x-empty-state icon="search" title="Tidak ada yang cocok" body="Coba kata kunci lain." />
                @else
                    <div class="overflow-x-auto">
                        <table class="data-table min-w-[860px]">
                            <thead>
                                <tr>
                                    <th scope="col">Karyawan</th>
                                    @foreach ($criteria as $c)
                                        <th scope="col" class="text-right">
                                            <span class="inline-flex items-center gap-1.5"><x-criteria-dot :code="$c['code']" /> {{ $c['name'] }}</span>
                                            <span class="block text-xs font-normal">{{ $c['type'] }} · {{ $c['weight'] }}%</span>
                                        </th>
                                    @endforeach
                                    <th scope="col" class="text-right" x-show="step === 3" x-cloak>Skor V</th>
                                    <th scope="col" class="text-right" x-show="step === 3" x-cloak>Peringkat</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($ranking as $row)
                                    <tr>
                                        <td class="font-semibold">{{ $row['name'] }}</td>
                                        @foreach ($criteria as $c)
                                            @php $code = $c['code']; @endphp
                                            <td class="num text-right">
                                                <span x-show="step === 1">{{ Format::number($row['raw'][$code] ?? 0, $dec($code)) }}</span>
                                                <span x-show="step === 2" x-cloak>{{ Format::score($row['normalized'][$code] ?? 0) }}</span>
                                                <span x-show="step === 3" x-cloak>{{ Format::score($row['contributions'][$code] ?? 0) }}</span>
                                            </td>
                                        @endforeach
                                        <td class="num text-right font-bold" x-show="step === 3" x-cloak>{{ Format::score($row['score']) }}</td>
                                        <td class="text-right" x-show="step === 3" x-cloak><x-rank :value="$row['rank']" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot x-show="step === 1">
                                <tr class="text-sm text-ink-muted">
                                    <td class="px-4 py-2.5">Acuan normalisasi (semua peserta)</td>
                                    @foreach ($criteria as $c)
                                        <td class="num px-4 py-2.5 text-right">
                                            {{ $c['type'] === 'cost' ? 'min' : 'max' }}
                                            <span class="font-semibold text-ink">{{ Format::number($bounds[$c['code']] ?? 0, $dec($c['code'])) }}</span>
                                        </td>
                                    @endforeach
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    {{ $ranking->links() }}
                @endif
                <p class="border-t border-line px-5 py-3 text-sm text-ink-muted">
                    <span x-show="step === 1">Nilai dari form atasan. Masa kerja dihitung dari tanggal bergabung sampai {{ Format::date($period['end']) }}.</span>
                    <span x-show="step === 2" x-cloak>Benefit: nilai ÷ nilai tertinggi. Cost: nilai terendah ÷ nilai. Hasil selalu 0–1. Tersimpan di tabel normalized_scores.</span>
                    <span x-show="step === 3" x-cloak>Setiap kolom = nilai ternormalisasi × bobot. Skor V adalah jumlahnya; peringkat diurutkan dari V terbesar.</span>
                </p>
            </x-panel>
        </div>
    @endif
</x-app-layout>
