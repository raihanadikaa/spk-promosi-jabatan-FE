@php use App\Support\Format; @endphp

<x-app-layout title="Skor saya"
              subtitle="{{ $period ? 'Hasil penilaian Anda untuk ' . $period['name'] . '.' : '' }} Hanya Anda, atasan, HRD, dan direktur yang bisa melihat halaman ini.">
    @if (! $employee)
        <x-panel><x-empty-state icon="user" title="Akun belum terhubung ke data karyawan" body="Hubungi HRD untuk menautkan akun Anda." /></x-panel>
    @elseif (! $result)
        <x-panel>
            <x-empty-state icon="clock" title="Skor Anda belum tersedia"
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
                            {{ Format::score(abs($delta)) }} dibanding periode lalu
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

        @if ($result['recommended'])
            <x-panel class="mt-6" title="Rekomendasi promosi">
                <x-status :value="$result['approval_status'] === 'pending' ? 'waiting' : $result['approval_status']" />
                <p class="mt-2 text-ink-muted">
                    @switch($result['approval_status'])
                        @case('approved') {{ $result['approval_note'] ?: 'Promosi Anda disetujui direktur.' }} @break
                        @case('rejected') Rekomendasi belum disetujui pada periode ini. Diskusikan catatan pengembangan dengan atasan Anda. @break
                        @default Anda masuk kandidat yang direkomendasikan. Keputusan ada di direktur.
                    @endswitch
                </p>
            </x-panel>
        @endif

        <x-panel class="mt-6" title="Rincian per kriteria" description="Nilai 1,0000 berarti Anda yang terbaik di kriteria itu pada periode ini." flush>
            <ul class="divide-y divide-line">
                @foreach ($criteria as $c)
                    @php $r = $result['normalized'][$c['code']] ?? 0; @endphp
                    <li class="grid gap-2 px-5 py-4 sm:grid-cols-[14rem_minmax(0,1fr)_6rem] sm:items-center sm:gap-6">
                        <div>
                            <p class="flex items-center gap-2 font-semibold"><x-criteria-dot :code="$c['code']" /> {{ $c['name'] }}</p>
                            <p class="num text-sm text-ink-muted">
                                Nilai Anda {{ Format::number($result['raw'][$c['code']] ?? 0, $c['input'] === 'auto' ? 1 : 0) }}{{ $c['input'] === 'score' ? '' : ' ' . $c['unit'] }}
                            </p>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-line" aria-hidden="true">
                            <div class="h-full rounded-full {{ ['C1' => 'bg-c1', 'C2' => 'bg-c2', 'C3' => 'bg-c3', 'C4' => 'bg-c4', 'C5' => 'bg-c5'][$c['code']] ?? 'bg-ink-muted' }}" style="width: {{ $r * 100 }}%"></div>
                        </div>
                        <p class="num font-semibold sm:text-right">{{ Format::score($r) }}</p>
                    </li>
                @endforeach
            </ul>
        </x-panel>

        <p class="mt-4 text-sm text-ink-muted">
            Ada yang tidak sesuai? Sampaikan ke {{ $employee->supervisor?->name ?? 'atasan Anda' }} atau HRD sebelum periode ditutup.
        </p>
    @endif
</x-app-layout>
