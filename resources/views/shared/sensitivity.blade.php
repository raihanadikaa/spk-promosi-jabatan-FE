@php
    use App\Queries\PromotionData;
    use App\Services\SensitivityAnalysis;
    use App\Support\Format;
    use Illuminate\Support\Js;

    $top = PromotionData::RECOMMENDED_COUNT;
    $r = $result;
    $name = fn ($id) => $r['names'][$id] ?? 'Karyawan #' . $id;
    $pct = fn ($v) => rtrim(rtrim(number_format((float) $v, 1, ',', '.'), '0'), ',') . '%';
@endphp

<x-app-layout title="Analisis sensitivitas" subtitle="Uji seberapa kuat hasil ranking {{ $period['name'] }} bila bobot kriteria digeser. Hanya simulasi: bobot dan ranking tersimpan tidak berubah.">
    @include('partials.stale-banner')

    @if (! $r['ready'])
        <x-panel>
            <x-empty-state icon="scale" title="Analisis belum bisa dijalankan"
                           :body="array_sum($r['weights']) !== 100
                               ? 'Total bobot kriteria periode ini belum 100%. Perbaiki di halaman Kriteria & bobot.'
                               : 'Butuh minimal dua karyawan yang sudah diranking. Hitung ranking dulu setelah penilaian masuk.'" />
        </x-panel>
    @else
        @php
            $stablePct = $r['tested'] ? round($r['stable'] / $r['tested'] * 100) : 0;
            // Kriteria paling sensitif = titik kritis terkecil (naik atau turun).
            $critical = collect($r['thresholds'])->map(fn ($t) => min($t['down'] ?? PHP_INT_MAX, $t['up'] ?? PHP_INT_MAX))->sort();
            $minShift = $critical->first();
            $sensitiveCodes = $minShift !== PHP_INT_MAX ? $critical->filter(fn ($v) => $v === $minShift)->keys() : collect();
            $criteriaByCode = collect($r['criteria'])->keyBy('code');
        @endphp

        <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-label="Ringkasan sensitivitas">
            <x-stat-card label="Kestabilan {{ $top }} besar" :value="$r['stable'] . ' / ' . $r['tested'] . ' skenario'"
                         :hint="$stablePct . '% skenario tidak mengubah kandidat yang direkomendasikan'" icon="check-circle"
                         :tone="$stablePct >= 80 ? 'ok' : ($stablePct >= 50 ? 'warn' : 'bad')" />
            <x-stat-card label="Kriteria paling menentukan"
                         :value="match (true) {
                             $sensitiveCodes->isEmpty() => 'Tidak ada',
                             $sensitiveCodes->count() === count($r['criteria']) => 'Semua kriteria',
                             $sensitiveCodes->count() === 1 => $sensitiveCodes->first() . ' ' . $criteriaByCode[$sensitiveCodes->first()]['name'],
                             default => $sensitiveCodes->implode(', '),
                         }"
                         :hint="$sensitiveCodes->isEmpty() ? 'Tetap sama sampai geser ' . SensitivityAnalysis::MAX_SHIFT . ' poin' : 'Geser ' . $minShift . ' poin saja sudah mengubah ' . $top . ' besar'"
                         icon="sliders" tone="warn" />
            <x-stat-card label="Peserta dianalisis" :value="Format::number($r['participants'])" hint="Karyawan pada ranking terakhir" icon="users" tone="info"
                         class="sm:col-span-2 xl:col-span-1" />
        </section>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_19rem]">
            <x-panel title="Batas aman bobot" description="Rentang bobot tiap kriteria yang masih menghasilkan {{ $top }} kandidat yang sama (kriteria lain menyesuaikan agar total tetap 100%)." flush class="min-w-0">
                <div class="overflow-x-auto">
                    <table class="data-table min-w-[620px]">
                        <thead>
                            <tr>
                                <th scope="col">Kriteria</th>
                                <th scope="col" class="text-right">Bobot</th>
                                <th scope="col">Rentang aman</th>
                                <th scope="col">Kepekaan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($r['criteria'] as $c)
                                @php
                                    $t = $r['thresholds'][$c['code']];
                                    $low = $t['down'] !== null ? max(0, $c['weight'] - $t['down'] + 1) : null;
                                    $high = $t['up'] !== null ? min(100, $c['weight'] + $t['up'] - 1) : null;
                                    $min = min($t['down'] ?? PHP_INT_MAX, $t['up'] ?? PHP_INT_MAX);
                                    [$tone, $label] = match (true) {
                                        $min <= 5 => ['danger', 'Sangat peka'],
                                        $min <= 15 => ['warning', 'Peka'],
                                        default => ['success', 'Stabil'],
                                    };
                                    // posisi batang rentang aman pada skala 0–100
                                    $barFrom = $low ?? max(0, $c['weight'] - SensitivityAnalysis::MAX_SHIFT);
                                    $barTo = $high ?? min(100, $c['weight'] + SensitivityAnalysis::MAX_SHIFT);
                                @endphp
                                <tr>
                                    <td><span class="flex items-center gap-2 font-semibold"><x-criteria-dot :code="$c['code']" /> {{ $c['code'] }} {{ $c['name'] }}</span></td>
                                    <td class="num text-right font-semibold">{{ $c['weight'] }}%</td>
                                    <td class="w-full min-w-40">
                                        <p class="num text-sm">
                                            {{ $low !== null ? $low . '%' : ($c['weight'] - SensitivityAnalysis::MAX_SHIFT <= 0 ? '0%' : '≤ ' . ($c['weight'] - SensitivityAnalysis::MAX_SHIFT) . '%') }}
                                            –
                                            {{ $high !== null ? $high . '%' : ($c['weight'] + SensitivityAnalysis::MAX_SHIFT >= 100 ? '100%' : '≥ ' . ($c['weight'] + SensitivityAnalysis::MAX_SHIFT) . '%') }}
                                        </p>
                                        <div class="relative mt-1.5 h-2 rounded-full bg-line" role="img" aria-label="Rentang aman {{ $barFrom }} sampai {{ $barTo }} persen, bobot sekarang {{ $c['weight'] }} persen">
                                            <span class="absolute inset-y-0 rounded-full bg-ok/60" style="left: {{ $barFrom }}%; width: {{ max(1, $barTo - $barFrom) }}%"></span>
                                            <span class="absolute -top-0.5 h-3 w-0.5 rounded bg-ink" style="left: calc({{ $c['weight'] }}% - 1px)"></span>
                                        </div>
                                    </td>
                                    <td><x-badge :tone="$tone">{{ $label }}</x-badge></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="border-t border-line px-5 py-3 text-sm text-ink-muted">
                    Garis hitam = bobot sekarang. Diuji per 1 poin sampai ±{{ SensitivityAnalysis::MAX_SHIFT }} poin. Makin sempit rentangnya, makin besar pengaruh kriteria itu terhadap keputusan.
                </p>
            </x-panel>

            <x-panel title="Kandidat saat ini" description="{{ $top }} besar dengan bobot periode ini." class="min-w-0">
                <ol class="space-y-3">
                    @foreach ($r['baseline_top'] as $i => $id)
                        <li class="flex items-center gap-3">
                            <x-rank :value="$i + 1" />
                            <a href="{{ route($detailRoute, $id) }}" class="min-w-0 truncate font-semibold hover:text-brand hover:underline">{{ $name($id) }}</a>
                        </li>
                    @endforeach
                </ol>
                @if ($r['margin'] !== null)
                    <p @class(['mt-4 rounded-ctl px-3 py-2 text-sm', 'bg-warn-bg text-warn' => $r['margin'] < 0.01, 'bg-canvas text-ink-muted' => $r['margin'] >= 0.01])>
                        Selisih skor peringkat {{ $top }} dan {{ $top + 1 }}: <strong class="num">{{ Format::score($r['margin']) }}</strong>.
                        @if ($r['margin'] < 0.01)
                            Sangat tipis, jadi perubahan bobot sekecil apa pun bisa menukar kandidat terakhir.
                        @endif
                    </p>
                @endif
                <div class="mt-5 flex flex-wrap gap-x-4 gap-y-1.5 border-t border-line pt-4 text-sm text-ink-muted">
                    @foreach ($r['criteria'] as $c)
                        <span class="inline-flex items-center gap-1.5"><x-criteria-dot :code="$c['code']" /> {{ $c['code'] }} <span class="num font-semibold text-ink">{{ $c['weight'] }}%</span></span>
                    @endforeach
                </div>
            </x-panel>
        </div>

        <x-panel class="mt-6" title="Skenario pergeseran bobot" description="Setiap sel: bobot satu kriteria digeser sekian poin. Hijau berarti {{ $top }} kandidat yang direkomendasikan tetap sama." flush>
            <div class="overflow-x-auto">
                <table class="data-table min-w-[760px]">
                    <thead>
                        <tr>
                            <th scope="col">Kriteria</th>
                            @foreach (SensitivityAnalysis::DELTAS as $d)
                                <th scope="col" class="text-center">{{ $d > 0 ? '+' . $d : '−' . abs($d) }} poin</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($r['criteria'] as $c)
                            <tr>
                                <th scope="row" class="!normal-case !tracking-normal !text-sm !font-semibold !text-ink !bg-transparent text-left">
                                    <span class="flex items-center gap-2"><x-criteria-dot :code="$c['code']" /> {{ $c['code'] }} <span class="num font-normal text-ink-muted">({{ $c['weight'] }}%)</span></span>
                                </th>
                                @foreach (SensitivityAnalysis::DELTAS as $d)
                                    @php $s = $r['scenarios'][$c['code']][$d] ?? null; @endphp
                                    <td class="text-center">
                                        @if ($s === null)
                                            <span class="text-sm text-ink-faint" title="Bobot akan keluar dari rentang 0–100%">–</span>
                                        @else
                                            @php
                                                $tip = 'Bobot ' . $c['code'] . ' jadi ' . ($c['weight'] + $d) . '%. ' . $top . ' besar: '
                                                    . collect($s['top'])->map($name)->implode(', ') . '. ' . $s['moved'] . ' karyawan bergeser peringkat.';
                                            @endphp
                                            <span @class([
                                                    'num inline-flex min-w-24 flex-col items-center rounded-ctl px-2 py-1 text-xs',
                                                    'bg-ok-bg text-ok' => $s['same_set'],
                                                    'bg-bad-bg text-bad' => ! $s['same_set'],
                                                  ]) title="{{ $tip }}">
                                                <span class="font-bold">{{ $s['same_set'] ? ($s['same_order'] ? 'Tetap' : 'Urutan berubah') : 'Berubah' }}</span>
                                                <span>{{ $s['moved'] }} bergeser</span>
                                            </span>
                                            <span class="sr-only">{{ $tip }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="border-t border-line px-5 py-3 text-sm text-ink-muted">Arahkan kursor ke sel untuk melihat {{ $top }} besar pada skenario itu. "Urutan berubah" = orangnya sama, peringkatnya bertukar.</p>
        </x-panel>

        {{-- Simulasi bobot sendiri: GET supaya hasilnya bisa dibagikan lewat tautan. --}}
        @php
            $form = old('bobot') ?? request('bobot') ?? collect($r['criteria'])->pluck('weight', 'code')->all();
        @endphp
        <x-panel class="mt-6" title="Coba bobot sendiri" description="Masukkan bobot lain (total 100%) untuk melihat ranking yang akan terjadi. Tidak ada yang disimpan.">
            <form method="GET" action="{{ route($formRoute) }}" x-data="{ w: {{ Js::from(collect($form)->map(fn ($v) => (float) $v)) }}, get total() { return Object.values(this.w).reduce((a, b) => a + (parseFloat(b) || 0), 0) } }" novalidate>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    @foreach ($r['criteria'] as $c)
                        <div>
                            <label for="bobot-{{ $c['code'] }}" class="field-label flex items-center gap-2"><x-criteria-dot :code="$c['code']" /> {{ $c['code'] }} {{ $c['name'] }}</label>
                            <div class="relative">
                                <input id="bobot-{{ $c['code'] }}" type="number" name="bobot[{{ $c['code'] }}]" min="0" max="100" step="0.5" inputmode="decimal"
                                       x-model.number="w['{{ $c['code'] }}']" value="{{ $form[$c['code']] ?? $c['weight'] }}"
                                       class="field-input num pr-8 @error('bobot') !border-bad @enderror">
                                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-ink-muted">%</span>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <p class="num text-sm" :class="Math.abs(total - 100) < 0.01 ? 'text-ok' : 'text-bad font-semibold'">
                        Total <span x-text="total.toLocaleString('id-ID', { maximumFractionDigits: 2 })">100</span>%
                    </p>
                    <x-btn type="submit" icon="calculator" x-bind:disabled="Math.abs(total - 100) >= 0.01">Hitung simulasi</x-btn>
                    @if ($r['custom'])
                        <a href="{{ route($formRoute) }}" class="link text-sm">Kembali ke bobot periode</a>
                    @endif
                </div>
                @error('bobot')<p class="field-error">{{ $message }}</p>@enderror
            </form>

            @if ($c2 = $r['custom'])
                <div @class([
                        'mt-5 flex items-start gap-3 rounded-panel border px-4 py-3 text-sm',
                        'border-ok/30 bg-ok-bg text-ok' => $c2['same_set'],
                        'border-warn/30 bg-warn-bg text-warn' => ! $c2['same_set'],
                     ]) role="status">
                    <x-icon :name="$c2['same_set'] ? 'check-circle' : 'alert'" class="mt-0.5 size-4" />
                    <p>
                        @if ($c2['same_set'])
                            Dengan bobot ini {{ $top }} kandidat yang direkomendasikan <strong>tetap sama</strong>{{ $c2['same_order'] ? '' : ', hanya urutannya yang berubah' }}.
                        @else
                            Dengan bobot ini <strong>kandidat yang direkomendasikan berubah</strong>.
                        @endif
                        {{ Format::number($c2['moved']) }} dari {{ Format::number($r['participants']) }} karyawan bergeser peringkat (paling jauh {{ $c2['max_shift'] }} peringkat).
                    </p>
                </div>

                <div class="-mx-5 mt-5 overflow-x-auto border-t border-line">
                    <table class="data-table min-w-[560px]">
                        <thead>
                            <tr>
                                <th scope="col">Peringkat simulasi</th>
                                <th scope="col">Karyawan</th>
                                <th scope="col" class="text-right">Skor simulasi</th>
                                <th scope="col" class="text-right">Peringkat sekarang</th>
                                <th scope="col" class="text-right">Perubahan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($c2['rows'] as $row)
                                @php $move = $row['base_rank'] - $row['rank']; @endphp
                                <tr>
                                    <td><x-rank :value="$row['rank']" /></td>
                                    <td><a href="{{ route($detailRoute, $row['id']) }}" class="font-semibold hover:text-brand hover:underline">{{ $name($row['id']) }}</a></td>
                                    <td class="num text-right font-semibold">{{ Format::score($row['score']) }}</td>
                                    <td class="num text-right text-ink-muted">{{ $row['base_rank'] }}</td>
                                    <td class="num text-right">
                                        @if ($move === 0)
                                            <span class="text-ink-muted">tetap</span>
                                        @else
                                            <span @class(['inline-flex items-center gap-1 font-semibold', 'text-ok' => $move > 0, 'text-bad' => $move < 0])>
                                                <x-icon :name="$move > 0 ? 'arrow-up' : 'arrow-down'" class="size-3.5" />{{ abs($move) }}
                                                <span class="sr-only">{{ $move > 0 ? 'naik' : 'turun' }}</span>
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-panel>

        <p class="mt-3 text-sm text-ink-muted">
            Cara menggeser: bobot satu kriteria dinaikkan/diturunkan, selisihnya dibagi ke kriteria lain sebanding bobot asalnya sehingga total tetap 100%.
            Nilai mentah karyawan tidak diubah. Hasil dihitung dari ranking terakhir ({{ $period['calculated_at'] ? Format::dateTime($period['calculated_at']) : 'belum dihitung' }}).
        </p>
    @endif
</x-app-layout>
