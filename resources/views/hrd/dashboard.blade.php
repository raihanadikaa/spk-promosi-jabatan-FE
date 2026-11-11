@php
    use App\Support\Format;

    $daysLeft = Format::daysLeft($period['deadline']);
    $open = $period['status'] === 'ongoing';
    $criteriaCount = count(\App\Queries\PromotionData::criteria());
@endphp

<x-app-layout title="Dashboard HRD"
              subtitle="Pantau progres penilaian {{ $period['name'] }} dan lanjutkan pekerjaan prioritas.">
    <x-slot:actions>
        <x-btn variant="secondary" :href="route('hrd.employees', ['status' => 'unfinished'])" icon="clipboard">Monitor penilaian</x-btn>
        @if ($open)
            <form method="POST" action="{{ route('hrd.saw.run') }}">
                @csrf
                <x-btn type="submit" icon="calculator">Hitung ulang ranking</x-btn>
            </form>
        @endif
    </x-slot:actions>

    @include('partials.stale-banner')

    <section class="grid gap-6 rounded-panel border border-[#C7D2FE] bg-gradient-to-br from-[#0B1F33] via-[#17324D] to-[#1E4B6D] p-5 text-white shadow-md sm:p-6 lg:grid-cols-[minmax(0,1fr)_minmax(18rem,.85fr)] lg:items-center"
             aria-label="Periode aktif dan progres penilaian">
        <div>
            <x-status :value="$period['status']" />
            <h2 class="mt-3 text-lg font-bold tracking-title text-white">{{ $period['name'] }}</h2>
            <p class="mt-1 text-sm text-[#B8CADB]">{{ Format::date($period['start']) }} – {{ Format::date($period['end']) }}</p>
            <p class="mt-4 text-sm text-[#B8CADB]">
                Batas pengisian <span class="font-semibold text-white">{{ Format::date($period['deadline']) }}</span>
                @if ($open && $daysLeft >= 0)
                    <span class="text-[#B8CADB]">({{ $daysLeft }} hari lagi)</span>
                @elseif ($open)
                    <span class="font-semibold text-[#FCA5A5]">(sudah lewat)</span>
                @endif
            </p>
        </div>

        <div class="rounded-panel bg-white/10 p-4 sm:p-5">
            <div class="mb-2 flex items-center justify-between gap-3">
                <span class="text-sm font-semibold text-white">Progres penilaian</span>
                <strong class="num text-lg font-bold text-white">{{ $submittedPercent }}%</strong>
            </div>
            <div class="h-2.5 overflow-hidden rounded-full bg-white/15" role="img" aria-label="{{ $counts['submitted'] }} dari {{ $counts['total'] }} penilaian terkirim">
                <span class="block h-full rounded-full bg-ok transition-[width] duration-200" style="width: {{ $submittedPercent }}%"></span>
            </div>
            <p class="mt-2 text-sm text-[#B8CADB]">{{ $counts['submitted'] }} dari {{ $counts['total'] }} karyawan sudah mengirim penilaian</p>
        </div>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-label="Ringkasan penilaian">
        <article class="flex min-w-0 items-start gap-4 rounded-panel border border-line bg-surface p-5 shadow-sm">
            <span class="grid size-11 shrink-0 place-items-center rounded-ctl bg-info-bg text-info" aria-hidden="true">
                <x-icon name="users" />
            </span>
            <div class="min-w-0">
                <p class="text-sm text-ink-muted">Karyawan dinilai</p>
                <p class="num mt-1 text-xl font-bold tracking-title text-ink">{{ $counts['submitted'] }} / {{ $counts['total'] }}</p>
                <p class="mt-1 text-sm text-ink-muted">{{ $counts['draft'] + $counts['pending'] }} penilaian belum dikirim</p>
            </div>
        </article>

        <article class="flex min-w-0 items-start gap-4 rounded-panel border border-line bg-surface p-5 shadow-sm">
            <span class="grid size-11 shrink-0 place-items-center rounded-ctl bg-warn-bg text-warn" aria-hidden="true">
                <x-icon name="calculator" />
            </span>
            <div class="min-w-0">
                <p class="text-sm text-ink-muted">Status kalkulasi</p>
                <p class="mt-1 text-lg font-bold tracking-title text-ink">{{ $period['calculated_at'] ? 'Sudah dihitung' : 'Belum dijalankan' }}</p>
                <p class="mt-1 text-sm text-ink-muted">{{ $period['calculated_at'] ? Format::dateTime($period['calculated_at']) : 'Menunggu kelengkapan penilaian' }}</p>
            </div>
        </article>

        <article class="flex min-w-0 items-start gap-4 rounded-panel border border-line bg-surface p-5 shadow-sm sm:col-span-2 xl:col-span-1">
            <span class="grid size-11 shrink-0 place-items-center rounded-ctl bg-brand-soft text-brand" aria-hidden="true">
                <x-icon name="sliders" />
            </span>
            <div class="min-w-0">
                <p class="text-sm text-ink-muted">Total kriteria</p>
                <p class="num mt-1 text-xl font-bold tracking-title text-ink">{{ $criteriaCount }}</p>
                <p class="mt-1 text-sm text-ink-muted">Bobot ditetapkan per periode</p>
            </div>
        </article>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(20rem,.9fr)]">
        <x-panel title="Aksi cepat" description="Lanjutkan tahapan proses penilaian.">
            <div class="grid gap-2">
                <a href="{{ route('hrd.periods') }}" class="group flex items-center gap-3 rounded-ctl border border-line p-3 transition-colors hover:border-line-strong hover:bg-canvas">
                    <span class="grid size-10 shrink-0 place-items-center rounded-ctl bg-info-bg text-info" aria-hidden="true"><x-icon name="calendar" /></span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold text-ink">Buka / tutup periode</span>
                        <span class="mt-0.5 block text-sm text-ink-muted">Kelola siklus penilaian</span>
                    </span>
                    <x-icon name="chevron-right" class="text-ink-faint transition-transform group-hover:translate-x-0.5" />
                </a>
                <a href="{{ route('hrd.criteria') }}" class="group flex items-center gap-3 rounded-ctl border border-line p-3 transition-colors hover:border-line-strong hover:bg-canvas">
                    <span class="grid size-10 shrink-0 place-items-center rounded-ctl bg-brand-soft text-brand" aria-hidden="true"><x-icon name="sliders" /></span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold text-ink">Kelola kriteria</span>
                        <span class="mt-0.5 block text-sm text-ink-muted">Periksa kriteria dan bobot</span>
                    </span>
                    <x-icon name="chevron-right" class="text-ink-faint transition-transform group-hover:translate-x-0.5" />
                </a>
                <a href="{{ route('hrd.saw') }}" class="group flex items-center gap-3 rounded-ctl border border-line p-3 transition-colors hover:border-line-strong hover:bg-canvas">
                    <span class="grid size-10 shrink-0 place-items-center rounded-ctl bg-warn-bg text-warn" aria-hidden="true"><x-icon name="calculator" /></span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold text-ink">Proses perhitungan SAW</span>
                        <span class="mt-0.5 block text-sm text-ink-muted">Tinjau matriks dan hasil kalkulasi</span>
                    </span>
                    <x-icon name="chevron-right" class="text-ink-faint transition-transform group-hover:translate-x-0.5" />
                </a>
            </div>
        </x-panel>

        <x-panel title="Perlu ditindaklanjuti" description="Prioritaskan penilaian yang belum dikirim." flush>
            <x-slot:actions>
                <a href="{{ route('hrd.employees', ['status' => 'unfinished']) }}" class="link text-sm">Lihat semua</a>
            </x-slot:actions>

            @if ($followUps->isEmpty())
                <x-empty-state icon="check-circle" title="Semua penilaian sudah terkirim" body="Ranking periode ini siap dihitung." />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($followUps as $e)
                        <li class="flex items-start justify-between gap-3 px-5 py-3.5">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-ink">{{ $e['name'] }}</p>
                                <p class="mt-0.5 text-sm text-ink-muted">
                                    {{ $e['status'] === 'draft' ? 'Draft belum dikirim oleh' : 'Belum dinilai oleh' }} {{ $e['assessor'] }}
                                </p>
                            </div>
                            <x-status :value="$e['status']" />
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

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(20rem,.85fr)]">
        <x-panel title="Kandidat promosi teratas" description="{{ $period['calculated_at'] ? 'Hasil perhitungan terakhir ' . Format::dateTime($period['calculated_at']) . '.' : 'Ranking muncul setelah HRD menghitung nilai periode ini.' }}" flush>
            <x-slot:actions>
                <a href="{{ route('hrd.ranking') }}" class="link text-sm">Lihat ranking</a>
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

        <x-panel title="Progres per departemen" description="Status pengiriman penilaian di setiap departemen." flush>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Departemen</th>
                            <th scope="col" class="w-[45%] min-w-32">Progres</th>
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
    </div>
</x-app-layout>
