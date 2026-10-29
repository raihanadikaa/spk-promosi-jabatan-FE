@php
    use App\Support\Format;

    $daysLeft = Format::daysLeft($period['deadline']);
    $open = $period['status'] === 'ongoing';
@endphp

<x-app-layout title="Ringkasan {{ $period['name'] }}"
              subtitle="Batas pengisian nilai oleh atasan {{ Format::date($period['deadline']) }}{{ $open && $daysLeft >= 0 ? ', ' . $daysLeft . ' hari lagi' : '' }}.">
    <x-slot:actions>
        <x-btn variant="secondary" :href="route('hrd.ranking')" icon="trophy">Lihat ranking</x-btn>
        @if ($open)
            <form method="POST" action="{{ route('hrd.saw.run') }}">
                @csrf
                <x-btn type="submit" icon="refresh">Hitung ulang ranking</x-btn>
            </form>
        @endif
    </x-slot:actions>

    @include('partials.stale-banner')

    <x-stat-strip :items="[
        ['label' => 'Penilaian terkirim', 'value' => $counts['submitted'] . ' / ' . $counts['total'], 'hint' => 'karyawan aktif'],
        ['label' => 'Masih draft', 'value' => $counts['draft'], 'hint' => 'menunggu atasan'],
        ['label' => 'Belum dinilai', 'value' => $counts['pending']],
        ['label' => 'Direkomendasikan', 'value' => $approvals['recommended'], 'hint' => $approvals['approved'] . ' disetujui, ' . $approvals['pending'] . ' menunggu'],
    ]" />

    <div class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
        <x-panel title="Kandidat teratas" description="{{ $period['calculated_at'] ? 'Perhitungan terakhir ' . Format::dateTime($period['calculated_at']) . '.' : 'Belum dihitung.' }}" flush>
            <x-slot:actions>
                <a href="{{ route('hrd.ranking') }}" class="link text-sm">Semua ranking</a>
            </x-slot:actions>

            @if (empty($top))
                <x-empty-state icon="trophy" title="Ranking belum dihitung"
                               body="Tekan Hitung ulang ranking setelah minimal satu penilaian terkirim." />
            @else
                <ol class="divide-y divide-line">
                    @foreach ($top as $row)
                        <li>@include('partials.ranking-row', ['row' => $row, 'href' => route('hrd.ranking.show', $row['id'])])</li>
                    @endforeach
                </ol>
                <div class="border-t border-line px-5 py-3">
                    <x-criteria-legend />
                </div>
            @endif
        </x-panel>

        <x-panel title="Perlu ditindaklanjuti" flush>
            @if ($followUps->isEmpty())
                <x-empty-state icon="check-circle" title="Semua penilaian sudah terkirim" body="Ranking periode ini siap dihitung." />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($followUps as $e)
                        <li class="px-5 py-3.5">
                            <div class="flex items-start justify-between gap-3">
                                <p class="font-semibold text-ink">{{ $e['name'] }}</p>
                                <x-status :value="$e['status']" />
                            </div>
                            <p class="mt-0.5 text-sm text-ink-muted">
                                {{ $e['status'] === 'draft' ? 'Draft belum dikirim oleh' : 'Belum dinilai oleh' }} {{ $e['assessor'] }}
                            </p>
                        </li>
                    @endforeach
                </ul>
                @if ($counts['draft'] + $counts['pending'] > $followUps->count())
                    <div class="border-t border-line px-5 py-3">
                        <a href="{{ route('hrd.employees', ['status' => 'unfinished']) }}" class="link text-sm">
                            Lihat semua {{ $counts['draft'] + $counts['pending'] }} karyawan yang belum selesai
                        </a>
                    </div>
                @endif
            @endif
        </x-panel>
    </div>

    <x-panel class="mt-6" title="Progres penilaian per departemen" flush>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Departemen</th>
                        <th scope="col" class="w-[50%] min-w-40">Progres</th>
                        <th scope="col" class="text-right">Terkirim</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($departments as $d)
                        <tr>
                            <td class="font-semibold">{{ $d['department'] }}</td>
                            <td>
                                <div class="flex h-2 w-full gap-px overflow-hidden rounded-full bg-line" role="img" aria-label="{{ $d['submitted'] }} terkirim, {{ $d['draft'] }} draft, dari {{ $d['total'] }} karyawan">
                                    <span class="h-full bg-ok" style="width: {{ $d['submitted'] / max(1, $d['total']) * 100 }}%"></span>
                                    <span class="h-full bg-[#D9A441]" style="width: {{ $d['draft'] / max(1, $d['total']) * 100 }}%"></span>
                                </div>
                            </td>
                            <td class="num whitespace-nowrap text-right font-semibold">{{ $d['submitted'] }} / {{ $d['total'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-ink-muted">Belum ada karyawan aktif.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-panel>
</x-app-layout>
