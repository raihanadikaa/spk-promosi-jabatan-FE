@php
    use App\Support\Format;

    $rows = collect($ranking);
    $departments = $rows->pluck('department')->unique()->sort()->values();
    $isTeam = $scope === 'team';
@endphp

<x-app-layout :title="$isTeam ? 'Ranking tim' : 'Ranking ' . $period['name']"
              :subtitle="$isTeam
                  ? 'Posisi bawahan Anda di ranking seluruh karyawan. Angka peringkat sama dengan yang dilihat HRD dan direktur.'
                  : 'Diurutkan dari skor akhir SAW. ' . \App\Support\DemoData::RECOMMENDED_COUNT . ' teratas direkomendasikan untuk promosi.'">
    @unless ($isTeam)
        <x-slot:actions>
            <x-btn variant="secondary" icon="download" disabled title="Tersedia setelah backend tersambung">Unduh PDF</x-btn>
        </x-slot:actions>
    @endunless

    <div x-data="{ q: '', dept: '' }">
        @if ($rows->isEmpty())
            <x-panel>
                <x-empty-state icon="trophy" title="Belum ada ranking"
                               body="Ranking muncul setelah penilaian dikirim dan HRD menghitung skor periode ini." />
            </x-panel>
        @else
            <div class="mb-4 flex flex-wrap items-center gap-3">
                <div class="relative w-full sm:w-72">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-ink-faint" />
                    <label for="cari" class="sr-only">Cari nama karyawan</label>
                    <input id="cari" type="search" x-model="q" placeholder="Cari nama" class="field-input pl-9">
                </div>
                @if (! $isTeam && $departments->count() > 1)
                    <label for="dept" class="sr-only">Filter departemen</label>
                    <select id="dept" x-model="dept" class="field-input w-auto pr-9">
                        <option value="">Semua departemen</option>
                        @foreach ($departments as $d)
                            <option>{{ $d }}</option>
                        @endforeach
                    </select>
                @endif
                <x-criteria-legend class="lg:ml-auto" />
            </div>

            <x-panel flush>
                <div class="overflow-x-auto">
                    <table class="data-table min-w-[760px]">
                        <thead>
                            <tr>
                                <th scope="col" class="w-14">Peringkat</th>
                                <th scope="col">Karyawan</th>
                                <th scope="col" class="w-[30%]">Komposisi skor</th>
                                <th scope="col" class="text-right">Skor akhir</th>
                                <th scope="col" class="text-right">vs semester lalu</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php $delta = $row['previous_score'] !== null ? $row['score'] - $row['previous_score'] : null; @endphp
                                <tr x-show="(!q || {{ \Illuminate\Support\Js::from(mb_strtolower($row['name'])) }}.includes(q.toLowerCase())) && (!dept || dept === {{ \Illuminate\Support\Js::from($row['department']) }})"
                                    @class(['bg-brand-soft/40' => $row['recommended']])>
                                    <td><x-rank :value="$row['rank']" /></td>
                                    <td>
                                        @if ($detailRoute)
                                            <a href="{{ route($detailRoute, $row['id']) }}" class="font-semibold text-ink hover:text-brand hover:underline">{{ $row['name'] }}</a>
                                        @else
                                            <span class="font-semibold">{{ $row['name'] }}</span>
                                        @endif
                                        <p class="text-sm text-ink-muted">{{ $row['position'] }} · {{ $row['department'] }}</p>
                                    </td>
                                    <td><x-score-bar :parts="$row['contributions']" /></td>
                                    <td class="num text-right text-md font-bold">{{ Format::score($row['score']) }}</td>
                                    <td class="num text-right text-sm">
                                        @if ($delta === null)
                                            <span class="text-ink-faint">Baru</span>
                                        @else
                                            <span @class(['inline-flex items-center gap-0.5 font-semibold', 'text-ok' => $delta >= 0, 'text-bad' => $delta < 0])>
                                                <x-icon :name="$delta >= 0 ? 'arrow-up' : 'arrow-down'" class="size-3.5" />
                                                {{ Format::score(abs($delta)) }}
                                                <span class="sr-only">{{ $delta >= 0 ? 'naik' : 'turun' }}</span>
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($row['recommended'])
                                            <x-status :value="$row['approval_status'] === 'pending' ? 'waiting' : $row['approval_status']" />
                                        @else
                                            <span class="text-sm text-ink-faint">Tidak direkomendasikan</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-panel>
            <p class="mt-3 text-sm text-ink-muted">
                Hanya penilaian berstatus terkirim yang dihitung. Baris berlatar merah muda adalah kandidat yang direkomendasikan.
            </p>
        @endif
    </div>
</x-app-layout>
