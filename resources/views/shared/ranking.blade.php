@php
    use App\Support\Format;

    $isTeam = $scope === 'team';
@endphp

<x-app-layout :title="$isTeam ? 'Ranking tim' : 'Ranking ' . $period['name']"
              :subtitle="$isTeam
                  ? 'Posisi bawahan Anda di ranking seluruh karyawan. Angka peringkat sama dengan yang dilihat HRD dan direktur.'
                  : 'Diurutkan dari skor akhir SAW. ' . \App\Queries\PromotionData::RECOMMENDED_COUNT . ' teratas direkomendasikan untuk promosi.'">
    @if ($exportRoute && $hasAny)
        <x-slot:actions>
            <x-btn variant="secondary" icon="download" :href="route($exportRoute)">Unduh CSV</x-btn>
        </x-slot:actions>
    @endif

    @include('partials.stale-banner')

    @if (! $hasAny)
        <x-panel>
            <x-empty-state icon="trophy" title="Belum ada ranking"
                           body="Ranking muncul setelah penilaian dikirim dan HRD menghitung skor periode ini." />
        </x-panel>
    @else
        <x-filter-bar placeholder="Cari nama atau NIK" :search="$filters['q']" :total="$ranking->total()"
                      :filters="array_values(array_filter([
                          $isTeam ? null : ['name' => 'department', 'label' => 'Departemen', 'options' => $departments, 'value' => $filters['department_id']],
                          ['name' => 'status', 'label' => 'Status', 'value' => $filters['status'], 'options' => [
                              'recommended' => 'Direkomendasikan', 'pending' => 'Menunggu keputusan', 'approved' => 'Disetujui',
                              'rejected' => 'Ditolak', 'not_recommended' => 'Tidak direkomendasikan',
                          ]],
                      ]))" />

        <x-panel flush>
            @if ($ranking->isEmpty())
                <x-empty-state icon="search" title="Tidak ada yang cocok" body="Coba kata kunci lain atau hapus filter." />
            @else
                <div class="overflow-x-auto">
                    <table class="data-table min-w-[760px]">
                        <thead>
                            <tr>
                                <th scope="col" class="w-14">Peringkat</th>
                                <th scope="col">Karyawan</th>
                                <th scope="col" class="w-[30%]">Komposisi skor</th>
                                <th scope="col" class="text-right">Skor akhir</th>
                                <th scope="col" class="text-right">vs periode lalu</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ranking as $row)
                                @php $delta = $row['previous_score'] !== null ? $row['score'] - $row['previous_score'] : null; @endphp
                                <tr @class(['bg-brand-soft/40' => $row['recommended']])>
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
                {{ $ranking->links() }}
            @endif
        </x-panel>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-ink-muted">Hanya penilaian terkirim yang dihitung. Baris berlatar merah muda adalah kandidat yang direkomendasikan.</p>
            <x-criteria-legend />
        </div>
    @endif
</x-app-layout>
