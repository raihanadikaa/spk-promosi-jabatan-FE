@php
    use App\Support\Format;

    $daysLeft = Format::daysLeft($period['deadline']);
@endphp

<x-app-layout title="Halo, {{ strtok(auth()->user()->name, ' ') }}"
              subtitle="Anda menilai {{ $counts['total'] }} bawahan untuk {{ $period['name'] }}.">

    {{-- Tugas utama atasan: menyelesaikan penilaian sebelum batas waktu --}}
    <x-panel>
        <div class="flex flex-wrap items-center justify-between gap-6">
            <div class="min-w-0 flex-1">
                <p class="text-sm text-ink-muted">Penilaian terkirim</p>
                <p class="num mt-1 text-2xl font-bold tracking-title">{{ $counts['submitted'] }} <span class="text-lg font-semibold text-ink-muted">dari {{ $counts['total'] }}</span></p>
                <div class="mt-3 h-2 max-w-md overflow-hidden rounded-full bg-line" aria-hidden="true">
                    <div class="h-full rounded-full bg-ok" style="width: {{ $counts['submitted'] / max(1, $counts['total']) * 100 }}%"></div>
                </div>
                <p class="mt-3 text-ink-muted">
                    Batas pengisian <span class="font-semibold text-ink">{{ Format::date($period['deadline']) }}</span>
                    @if ($open && $daysLeft >= 0) ({{ $daysLeft }} hari lagi) @elseif ($open) <span class="font-semibold text-bad">(sudah lewat)</span> @endif
                </p>
            </div>
            @if (! $open)
                <div class="flex items-center gap-2 font-semibold text-ink-muted"><x-icon name="lock" /> Periode {{ $period['name'] }} tidak sedang berlangsung</div>
            @elseif ($counts['total'] === 0)
                <div class="max-w-xs text-ink-muted">Belum ada bawahan yang ditautkan ke akun Anda. Hubungi HRD.</div>
            @elseif ($next)
                <div class="w-full rounded-ctl bg-canvas p-4 sm:w-auto sm:min-w-72">
                    <p class="text-sm text-ink-muted">{{ $next['status'] === 'draft' ? 'Lanjutkan draft' : 'Berikutnya untuk dinilai' }}</p>
                    <p class="mt-0.5 font-semibold">{{ $next['name'] }}</p>
                    <x-btn :href="route('manager.assessments.edit', $next['id'])" icon="pencil" class="mt-3 w-full">
                        {{ $next['status'] === 'draft' ? 'Lanjutkan penilaian' : 'Mulai menilai' }}
                    </x-btn>
                </div>
            @else
                <div class="flex items-center gap-2 font-semibold text-ok"><x-icon name="check-circle" /> Semua penilaian sudah dikirim</div>
            @endif
        </div>
    </x-panel>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-panel title="Bawahan Anda" flush>
            <x-slot:actions><a href="{{ route('manager.assessments') }}" class="link text-sm">Semua penilaian</a></x-slot:actions>
            @if ($recent->isEmpty())
                <x-empty-state icon="users" title="Belum ada bawahan" />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($recent as $e)
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <div class="min-w-0">
                                <p class="truncate font-semibold">{{ $e['name'] }}</p>
                                <p class="text-sm text-ink-muted">{{ $e['position'] }}</p>
                            </div>
                            <x-status :value="$e['status']" />
                        </li>
                    @endforeach
                </ul>
                @if ($counts['total'] > $recent->count())
                    <div class="border-t border-line px-5 py-3">
                        <a href="{{ route('manager.assessments') }}" class="link text-sm">Lihat semua {{ $counts['total'] }} bawahan</a>
                    </div>
                @endif
            @endif
        </x-panel>

        <x-panel title="Posisi tim di ranking" description="Lima teratas dari tim Anda di ranking seluruh karyawan." flush>
            <x-slot:actions><a href="{{ route('manager.ranking') }}" class="link text-sm">Detail</a></x-slot:actions>
            @if (empty($ranking))
                <x-empty-state icon="trophy" title="Belum ada ranking" body="Ranking muncul setelah HRD menghitung skor periode ini." />
            @else
                <ol class="divide-y divide-line">
                    @foreach ($ranking as $row)
                        <li>@include('partials.ranking-row', ['row' => $row, 'href' => null])</li>
                    @endforeach
                </ol>
            @endif
        </x-panel>
    </div>
</x-app-layout>
