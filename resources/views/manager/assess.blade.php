@php
    use App\Support\Format;

    $locked = $employee['status'] === 'submitted';
    $raw = $employee['raw'] ?? [];
    $limits = ['C2' => [0, 100], 'C3' => [0, 100], 'C4' => [0, 60], 'C5' => [0, 20]];
@endphp

<x-app-layout :title="'Penilaian ' . $employee['name']"
              :subtitle="$employee['position'] . ' · ' . $employee['department'] . ' · ' . $period['name']"
              :back="['url' => route('manager.assessments'), 'label' => 'Penilaian bawahan']">

    @if ($locked)
        <div class="mb-6 flex gap-3 rounded-panel border border-ok/25 bg-ok-bg px-4 py-3 text-ok" role="note">
            <x-icon name="lock" class="mt-0.5" />
            <p>Dikirim {{ Format::dateTime($employee['submitted_at']) }}. Nilai terkunci; hubungi HRD jika perlu koreksi.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('demo.action') }}" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]"
          x-data="{ submitting: false }"
          @submit="if ($event.submitter?.value === 'assessment.submit' && !confirm({{ \Illuminate\Support\Js::from('Kirim penilaian ' . $employee['name'] . ' ke HRD? Setelah dikirim, nilai tidak bisa diubah.') }})) { $event.preventDefault(); return } setTimeout(() => submitting = true)">
        @csrf
        <input type="hidden" name="redirect" value="{{ route('manager.assessments', absolute: false) }}">

        <x-panel flush>
            <fieldset @disabled($locked) class="divide-y divide-line">
                <legend class="sr-only">Nilai per kriteria</legend>
                @foreach ($criteria as $c)
                    @php $code = $c['code']; @endphp
                    <div class="grid gap-4 px-5 py-5 sm:grid-cols-[minmax(0,1fr)_11rem] sm:items-start">
                        <div>
                            <label for="v-{{ $code }}" class="flex items-center gap-2 font-semibold">
                                <x-criteria-dot :code="$code" /> {{ $c['name'] }}
                                <span class="num text-sm font-medium text-ink-faint">bobot {{ $c['weight'] }}%</span>
                            </label>
                            <p id="h-{{ $code }}" class="mt-1 text-sm text-ink-muted">{{ $c['help'] }}</p>
                        </div>

                        @if ($c['input'] === 'auto')
                            <div class="rounded-ctl bg-canvas px-3 py-2 text-right">
                                <span id="v-{{ $code }}" class="num font-semibold">{{ Format::number($employee['tenure'], 1) }}</span>
                                <span class="text-ink-muted">tahun</span>
                                <p class="text-xs text-ink-muted">otomatis</p>
                            </div>
                        @else
                            @php [$min, $max] = $limits[$code]; @endphp
                            <div x-data="{ v: {{ \Illuminate\Support\Js::from($raw[$code] ?? '') }} }">
                                <div class="relative">
                                    <input id="v-{{ $code }}" type="number" name="scores[{{ $code }}]" x-model.number="v"
                                           min="{{ $min }}" max="{{ $max }}" step="1" inputmode="numeric" required
                                           aria-describedby="h-{{ $code }} r-{{ $code }}"
                                           :aria-invalid="(v !== '' && (v < {{ $min }} || v > {{ $max }})).toString()"
                                           :class="v !== '' && (v < {{ $min }} || v > {{ $max }}) && '!border-bad'"
                                           class="field-input num pr-24 text-right disabled:bg-canvas disabled:text-ink-muted">
                                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-ink-muted">{{ $c['input'] === 'score' ? '/ 100' : $c['unit'] }}</span>
                                </div>
                                <p id="r-{{ $code }}" class="mt-1 text-right text-xs"
                                   :class="v !== '' && (v < {{ $min }} || v > {{ $max }}) ? 'font-semibold text-bad' : 'text-ink-faint'">
                                    Rentang {{ $min }}–{{ $max }}
                                </p>
                            </div>
                        @endif
                    </div>
                @endforeach

                <div class="px-5 py-5">
                    <label for="note" class="field-label">Catatan untuk HRD <span class="font-normal text-ink-muted">(opsional)</span></label>
                    <textarea id="note" name="note" rows="3" maxlength="1000" class="field-input disabled:bg-canvas"
                              placeholder="Konteks yang tidak tergambar dari angka, mis. project besar yang diselesaikan"></textarea>
                </div>
            </fieldset>
        </x-panel>

        <div class="space-y-4 xl:sticky xl:top-8 xl:self-start">
            <x-panel title="Sebelum mengirim">
                <ul class="space-y-2.5 text-base text-ink-muted">
                    <li class="flex gap-2"><x-icon name="check" class="mt-0.5 size-4 text-ok" /> Nilai berdasarkan catatan kerja selama periode, bukan kesan pribadi.</li>
                    <li class="flex gap-2"><x-icon name="check" class="mt-0.5 size-4 text-ok" /> Kedisiplinan diisi dari rekap absensi.</li>
                    <li class="flex gap-2"><x-icon name="check" class="mt-0.5 size-4 text-ok" /> Skor akhir dihitung HRD setelah semua penilaian masuk, jadi tidak tampil di sini.</li>
                </ul>
            </x-panel>
            @unless ($locked)
                <div class="flex flex-col gap-2">
                    <x-btn type="submit" name="action" value="assessment.submit" icon="check" x-bind:disabled="submitting">Kirim ke HRD</x-btn>
                    <x-btn type="submit" name="action" value="assessment.draft" variant="secondary" formnovalidate x-bind:disabled="submitting">Simpan sebagai draft</x-btn>
                </div>
            @endunless
        </div>
    </form>
</x-app-layout>
