@php
    use App\Support\Format;

    $counts = collect($employees)->countBy('status');
    $recommended = collect($ranking)->where('recommended', true);
    $approved = $recommended->where('approval_status', 'approved')->count();
    $daysLeft = Format::daysLeft($period['deadline']);

    $byDept = collect($employees)->groupBy('department');
    $followUps = collect($employees)->whereIn('status', ['draft', 'pending'])->sortBy('status');
@endphp

<x-app-layout title="Ringkasan {{ $period['name'] }}"
              subtitle="Batas pengisian nilai oleh atasan {{ Format::date($period['deadline']) }}{{ $daysLeft >= 0 ? ', ' . $daysLeft . ' hari lagi' : '' }}.">
    <x-slot:actions>
        <x-btn variant="secondary" :href="route('hrd.ranking')" icon="trophy">Lihat ranking</x-btn>
        <form method="POST" action="{{ route('demo.action') }}">
            @csrf
            <input type="hidden" name="action" value="saw.run">
            <x-btn type="submit" icon="refresh">Hitung ulang ranking</x-btn>
        </form>
    </x-slot:actions>

    <x-stat-strip :items="[
        ['label' => 'Penilaian terkirim', 'value' => ($counts['submitted'] ?? 0) . ' / ' . count($employees), 'hint' => 'karyawan'],
        ['label' => 'Masih draft', 'value' => $counts['draft'] ?? 0, 'hint' => 'menunggu atasan'],
        ['label' => 'Belum dinilai', 'value' => $counts['pending'] ?? 0],
        ['label' => 'Direkomendasikan', 'value' => $recommended->count(), 'hint' => $approved . ' sudah disetujui'],
    ]" />

    <div class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
        <x-panel title="Kandidat teratas" description="Dihitung dari {{ $counts['submitted'] ?? 0 }} penilaian yang sudah terkirim." flush>
            <x-slot:actions>
                <a href="{{ route('hrd.ranking') }}" class="link text-sm">Semua ranking</a>
            </x-slot:actions>

            <ol class="divide-y divide-line">
                @foreach (collect($ranking)->take(5) as $row)
                    <li>
                        @include('partials.ranking-row', ['row' => $row, 'href' => route('hrd.ranking.show', $row['id'])])
                    </li>
                @endforeach
            </ol>
            <div class="border-t border-line px-5 py-3">
                <x-criteria-legend />
            </div>
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
            @endif
        </x-panel>
    </div>

    <x-panel class="mt-6" title="Progres penilaian per departemen" flush>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Departemen</th>
                        <th scope="col" class="hidden sm:table-cell">Atasan penilai</th>
                        <th scope="col" class="w-[40%] min-w-40">Progres</th>
                        <th scope="col" class="text-right">Terkirim</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($byDept as $dept => $list)
                        @php
                            $done = $list->where('status', 'submitted')->count();
                            $draft = $list->where('status', 'draft')->count();
                            $total = $list->count();
                        @endphp
                        <tr>
                            <td class="font-semibold">{{ $dept }}</td>
                            <td class="hidden text-ink-muted sm:table-cell">{{ $list->first()['assessor'] }}</td>
                            <td>
                                <div class="flex h-2 w-full gap-px overflow-hidden rounded-full bg-line" role="img" aria-label="{{ $done }} terkirim, {{ $draft }} draft, dari {{ $total }} karyawan">
                                    <span class="h-full bg-ok" style="width: {{ $done / $total * 100 }}%"></span>
                                    <span class="h-full bg-[#D9A441]" style="width: {{ $draft / $total * 100 }}%"></span>
                                </div>
                            </td>
                            <td class="num whitespace-nowrap text-right font-semibold">{{ $done }} / {{ $total }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-panel>
</x-app-layout>
