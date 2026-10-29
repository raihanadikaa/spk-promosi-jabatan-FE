@php
    use App\Support\Format;

    $delta = $row['previous_score'] !== null ? $row['score'] - $row['previous_score'] : null;
    $status = $row['recommended'] ? ($row['approval_status'] === 'pending' ? 'waiting' : $row['approval_status']) : null;
@endphp

<x-app-layout :title="$row['name']" :subtitle="$row['position'] . ' · ' . $row['department'] . ' · bergabung ' . Format::date($row['join_date'])" :back="$back">
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div class="space-y-6">
            {{-- Skor akhir + komposisinya --}}
            <x-panel>
                <div class="flex flex-wrap items-end justify-between gap-6">
                    <div>
                        <p class="text-sm text-ink-muted">Skor akhir SAW</p>
                        <p class="num mt-1 text-2xl font-bold tracking-title">{{ Format::score($row['score']) }}</p>
                    </div>
                    <dl class="flex gap-8 text-right">
                        <div>
                            <dt class="text-sm text-ink-muted">Peringkat</dt>
                            <dd class="num text-lg font-bold">{{ $row['rank'] }} <span class="text-base font-medium text-ink-muted">dari {{ $total }}</span></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-ink-muted">Semester lalu</dt>
                            <dd class="num text-lg font-bold">
                                {{ Format::score($row['previous_score']) }}
                                @if ($delta !== null)
                                    <span @class(['text-sm font-semibold', 'text-ok' => $delta >= 0, 'text-bad' => $delta < 0])>
                                        {{ $delta >= 0 ? '+' : '−' }}{{ Format::score(abs($delta)) }}
                                    </span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
                <x-score-bar :parts="$row['contributions']" height="h-3" class="mt-6" />
                <x-criteria-legend class="mt-3" />
            </x-panel>

            {{-- Jejak perhitungan: mentah → normalisasi → × bobot → kontribusi --}}
            <x-panel title="Rincian perhitungan" description="Setiap angka di bawah bisa dicek ulang dengan rumus SAW." flush>
                <div class="overflow-x-auto">
                    <table class="data-table min-w-[640px]">
                        <thead>
                            <tr>
                                <th scope="col">Kriteria</th>
                                <th scope="col" class="text-right">Nilai mentah</th>
                                <th scope="col">Normalisasi</th>
                                <th scope="col" class="text-right">Bobot</th>
                                <th scope="col" class="text-right">Kontribusi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($criteria as $c)
                                @php
                                    $code = $c['code'];
                                    $x = $row['raw'][$code] ?? 0;
                                    $bound = $bounds[$code] ?? 0; // max (benefit) / min (cost) seluruh peserta
                                @endphp
                                <tr>
                                    <td>
                                        <span class="flex items-center gap-2 font-semibold"><x-criteria-dot :code="$code" /> {{ $c['name'] }}</span>
                                        <span class="text-sm text-ink-muted">{{ $c['type'] === 'cost' ? 'Cost, makin kecil makin baik' : 'Benefit' }}</span>
                                    </td>
                                    <td class="num text-right">{{ Format::number($x, $code === 'C1' ? 1 : 0) }} <span class="text-sm text-ink-muted">{{ $c['unit'] === 'skor 0–100' ? '' : $c['unit'] }}</span></td>
                                    <td class="num text-sm text-ink-muted">
                                        @if ($c['type'] === 'cost')
                                            {{ Format::number($bound, 0) }} ÷ {{ Format::number($x, 0) }}
                                        @else
                                            {{ Format::number($x, $code === 'C1' ? 1 : 0) }} ÷ {{ Format::number($bound, $code === 'C1' ? 1 : 0) }}
                                        @endif
                                        = <span class="font-semibold text-ink">{{ Format::score($row['normalized'][$code]) }}</span>
                                    </td>
                                    <td class="num text-right">× {{ $c['weight'] }}%</td>
                                    <td class="num text-right font-semibold">{{ Format::score($row['contributions'][$code]) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-right font-semibold">Skor akhir (jumlah kontribusi)</td>
                                <td class="num px-4 py-3 text-right text-md font-bold">{{ Format::score($row['score']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-panel>
        </div>

        {{-- Kolom keputusan --}}
        <div class="space-y-6">
            <x-panel title="Status rekomendasi">
                @if (! $row['recommended'])
                    <p class="text-ink-muted">Tidak masuk {{ \App\Queries\PromotionData::RECOMMENDED_COUNT }} besar, sehingga tidak diajukan untuk promosi periode ini.</p>
                @elseif ($row['approval_status'] !== 'pending')
                    <x-status :value="$status" />
                    <p class="mt-3 text-ink">{{ $row['approval_note'] ?: 'Tanpa catatan.' }}</p>
                    <p class="mt-2 text-sm text-ink-muted">Diputuskan {{ $row['approval_by'] ? 'oleh ' . $row['approval_by'] . ', ' : '' }}{{ Format::dateTime($row['approval_at']) }}</p>
                @elseif ($canDecide)
                    <form method="POST" action="{{ route('direktur.ranking.decide', $row['id']) }}" class="space-y-4" novalidate
                          x-data @submit="if (!confirm($event.submitter?.value === 'approve'
                                ? {{ \Illuminate\Support\Js::from('Setujui promosi ' . $row['name'] . '? Keputusan tidak bisa diubah.') }}
                                : {{ \Illuminate\Support\Js::from('Tolak rekomendasi promosi ' . $row['name'] . '?') }})) $event.preventDefault()">
                        @csrf
                        <p class="text-ink-muted">Direkomendasikan sistem berdasarkan peringkat. Keputusan akhir ada di Anda.</p>
                        <div>
                            <label for="note" class="field-label">Catatan keputusan</label>
                            <textarea id="note" name="note" rows="4" maxlength="1000"
                                      @error('note') aria-invalid="true" aria-describedby="note-error" @enderror
                                      class="field-input @error('note') !border-bad @enderror"
                                      placeholder="Mis. posisi tujuan dan tanggal efektif">{{ old('note') }}</textarea>
                            @error('note')
                                <p id="note-error" class="field-error">{{ $message }}</p>
                            @else
                                <p class="field-help">Wajib diisi jika menolak.</p>
                            @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <x-btn type="submit" name="decision" value="reject" variant="danger" icon="x">Tolak</x-btn>
                            <x-btn type="submit" name="decision" value="approve" variant="success" icon="check">Setujui promosi</x-btn>
                        </div>
                    </form>
                @else
                    <x-status :value="$status" />
                    <p class="mt-3 text-ink-muted">{{ $period['status'] === 'ongoing' ? 'Menunggu keputusan direktur.' : 'Periode ditutup tanpa keputusan.' }}</p>
                @endif
            </x-panel>

            <x-panel title="Data penilaian">
                <dl class="space-y-3 text-base">
                    <div class="flex justify-between gap-4"><dt class="text-ink-muted">NIK</dt><dd class="num">{{ $row['nik'] }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-muted">Penilai</dt><dd>{{ $row['assessor'] }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-muted">Dikirim</dt><dd>{{ Format::dateTime($row['submitted_at']) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-muted">Masa kerja</dt><dd class="num">{{ Format::number($row['tenure'], 1) }} tahun</dd></div>
                </dl>
            </x-panel>
        </div>
    </div>
</x-app-layout>
