@php use App\Support\Format; @endphp

<x-app-layout title="Skor saya" subtitle="Hasil penilaian Anda untuk {{ $period['name'] }}. Hanya Anda, atasan, HRD, dan direktur yang bisa melihat halaman ini.">
    @if (! $employee)
        <x-panel><x-empty-state icon="user" title="Akun belum terhubung ke data karyawan" body="Hubungi HRD untuk menautkan akun Anda." /></x-panel>
    @elseif (! $result)
        <x-panel>
            <x-empty-state icon="clock" title="Penilaian Anda belum selesai"
                           body="Skor muncul setelah atasan mengirim penilaian dan HRD menghitung ranking periode ini." />
        </x-panel>
    @else
        @php $delta = $result['previous_score'] !== null ? $result['score'] - $result['previous_score'] : null; @endphp

        <x-panel>
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div>
                    <p class="text-sm text-ink-muted">Skor akhir</p>
                    <p class="num mt-1 text-2xl font-bold tracking-title">{{ Format::score($result['score']) }}</p>
                    @if ($delta !== null)
                        <p @class(['mt-1 inline-flex items-center gap-1 text-sm font-semibold', 'text-ok' => $delta >= 0, 'text-bad' => $delta < 0])>
                            <x-icon :name="$delta >= 0 ? 'arrow-up' : 'arrow-down'" class="size-3.5" />
                            {{ Format::score(abs($delta)) }} dibanding semester lalu
                        </p>
                    @endif
                </div>
                <div class="text-right">
                    <p class="text-sm text-ink-muted">Peringkat</p>
                    <p class="num mt-1 text-xl font-bold">{{ $result['rank'] }} <span class="text-md font-medium text-ink-muted">dari {{ $total }}</span></p>
                </div>
            </div>
            <x-score-bar :parts="$result['contributions']" height="h-3" class="mt-6" />
            <x-criteria-legend class="mt-3" />
        </x-panel>

        <x-panel class="mt-6" title="Rincian per kriteria" description="Nilai 1,0000 berarti Anda yang terbaik di kriteria itu pada periode ini." flush>
            <ul class="divide-y divide-line">
                @foreach (\App\Support\DemoData::criteria() as $c)
                    @php $r = $result['normalized'][$c['code']]; @endphp
                    <li class="grid gap-2 px-5 py-4 sm:grid-cols-[14rem_minmax(0,1fr)_6rem] sm:items-center sm:gap-6">
                        <div>
                            <p class="flex items-center gap-2 font-semibold"><x-criteria-dot :code="$c['code']" /> {{ $c['name'] }}</p>
                            <p class="num text-sm text-ink-muted">
                                Nilai Anda {{ Format::number($result['raw'][$c['code']], $c['code'] === 'C1' ? 1 : 0) }}{{ $c['input'] === 'score' ? '' : ' ' . $c['unit'] }}
                            </p>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-line" aria-hidden="true">
                            <div class="h-full rounded-full bg-{{ strtolower($c['code']) }}" style="width: {{ $r * 100 }}%"></div>
                        </div>
                        <p class="num font-semibold sm:text-right">{{ Format::score($r) }}</p>
                    </li>
                @endforeach
            </ul>
        </x-panel>

        <p class="mt-4 text-sm text-ink-muted">
            Ada yang tidak sesuai? Sampaikan ke {{ $employee['assessor'] }} (atasan penilai) atau HRD sebelum periode ditutup.
        </p>
    @endif
</x-app-layout>
