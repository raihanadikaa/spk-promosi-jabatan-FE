@php use App\Support\Format; @endphp

<x-app-layout title="Persetujuan promosi" subtitle="Kandidat yang direkomendasikan sistem untuk {{ $period['name'] }}. Tinjau rincian skornya, lalu beri keputusan akhir.">
    <x-slot:actions>
        <x-btn variant="secondary" icon="download" :href="route('direktur.ranking.export')">Unduh CSV</x-btn>
    </x-slot:actions>

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan keputusan">
        <x-stat-card label="Menunggu keputusan" :value="$counts['pending']" :hint="$counts['pending'] ? 'Perlu Anda tinjau' : 'Tidak ada antrean'" icon="clock" tone="warn" />
        <x-stat-card label="Disetujui" :value="$counts['approved']" hint="Promosi disetujui" icon="check-circle" tone="ok" />
        <x-stat-card label="Ditolak" :value="$counts['rejected']" hint="Dengan catatan alasan" icon="x-circle" tone="bad" />
        <x-stat-card label="Review ulang HRD" :value="$counts['revision']" hint="Dikembalikan ke HRD" icon="refresh" tone="brand" />
    </section>

    @if (! $open)
        <div class="mb-4 flex items-start gap-3 rounded-panel border border-line bg-info-bg px-4 py-3 text-sm text-info">
            <x-icon name="info" class="mt-0.5 size-4 shrink-0" />
            <p>Periode ini sudah ditutup. Keputusan tidak bisa diubah lagi.</p>
        </div>
    @endif

    <x-filter-bar placeholder="Cari nama atau NIK" :search="$filters['q']" :total="$rows->total()"
                  :filters="[
                      ['name' => 'status', 'label' => 'Status', 'options' => ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'revision' => 'Review ulang'], 'value' => $filters['status']],
                  ]" />

    <x-panel flush>
        @if ($rows->isEmpty())
            <x-empty-state icon="trophy" title="Belum ada kandidat"
                           body="Kandidat muncul setelah HRD menghitung ranking. {{ \App\Queries\PromotionData::RECOMMENDED_COUNT }} peringkat teratas otomatis direkomendasikan." />
        @else
            <div class="overflow-x-auto">
                <table class="data-table min-w-[860px]">
                    <thead>
                        <tr>
                            <th scope="col">Kandidat</th>
                            <th scope="col" class="text-right">Peringkat</th>
                            <th scope="col" class="text-right">Skor</th>
                            <th scope="col">Jabatan sekarang</th>
                            <th scope="col">Usulan jabatan</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="grid size-9 shrink-0 place-items-center rounded-full bg-brand-soft text-sm font-bold text-brand" aria-hidden="true">{{ collect(explode(' ', $row['name']))->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') }}</span>
                                        <div class="min-w-0">
                                            <p class="font-semibold">{{ $row['name'] }}</p>
                                            <p class="text-sm text-ink-muted">{{ $row['department'] }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="num text-right font-semibold">#{{ $row['rank'] }}</td>
                                <td class="num text-right font-semibold">{{ Format::score($row['score']) }}</td>
                                <td>{{ $row['position'] }}</td>
                                <td>{{ $row['proposed_position'] ?? '–' }}</td>
                                <td><x-status :value="$row['approval_status'] === 'pending' ? 'waiting' : $row['approval_status']" /></td>
                                <td class="text-right">
                                    <x-btn size="sm" :variant="$row['approval_status'] === 'pending' && $open ? 'primary' : 'secondary'" :href="route('direktur.ranking.show', $row['id'])">
                                        {{ $row['approval_status'] === 'pending' && $open ? 'Tinjau' : 'Lihat' }}<span class="sr-only"> {{ $row['name'] }}</span>
                                    </x-btn>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $rows->links() }}
        @endif
    </x-panel>
</x-app-layout>
