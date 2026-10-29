@php
    use App\Support\Format;

    $raw = $employee['raw'] ?? [];
@endphp

<x-app-layout :title="'Penilaian ' . $employee['name']"
              :subtitle="$employee['position'] . ' · ' . $employee['department'] . ' · ' . $period['name']"
              :back="['url' => route('manager.assessments'), 'label' => 'Penilaian bawahan']">

    @if ($employee['status'] === 'submitted')
        <div class="mb-6 flex gap-3 rounded-panel border border-ok/25 bg-ok-bg px-4 py-3 text-ok" role="note">
            <x-icon name="lock" class="mt-0.5" />
            <p>Dikirim {{ Format::dateTime($employee['submitted_at']) }}. Nilai terkunci; minta HRD membuka kembali jika perlu koreksi.</p>
        </div>
    @elseif ($locked)
        <div class="mb-6 flex gap-3 rounded-panel border border-line bg-surface px-4 py-3 text-ink-muted" role="note">
            <x-icon name="lock" class="mt-0.5" />
            <p>Periode {{ $period['name'] }} tidak sedang berlangsung, jadi nilai tidak bisa diubah.</p>
        </div>
    @elseif ($employee['status'] === 'draft')
        <p class="mb-6 text-sm text-ink-muted">Draft terakhir disimpan {{ Format::dateTime($employee['updated_at']) }}.</p>
    @endif

    <form method="POST" action="{{ route('manager.assessments.save', $employee['id']) }}" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]" novalidate
          x-data="{ submitting: false }"
          @submit="if ($event.submitter?.value === 'submit' && !confirm({{ \Illuminate\Support\Js::from('Kirim penilaian ' . $employee['name'] . ' ke HRD? Setelah dikirim, nilai terkunci.') }})) { $event.preventDefault(); return } setTimeout(() => submitting = true)">
        @csrf
        @method('PUT')

        <x-panel flush>
            <fieldset @disabled($locked) class="divide-y divide-line">
                <legend class="sr-only">Nilai per kriteria</legend>
                @foreach ($criteria as $c)
                    @php
                        $code = $c['code'];
                        $error = $errors->first("scores.$code");
                        $value = old("scores.$code", isset($raw[$code]) ? (int) $raw[$code] : '');
                    @endphp
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
                            <div x-data="{ v: {{ \Illuminate\Support\Js::from($value) }} }">
                                <div class="relative">
                                    <input id="v-{{ $code }}" type="number" name="scores[{{ $code }}]" x-model="v"
                                           min="{{ $c['min'] }}" max="{{ $c['max'] }}" step="1" inputmode="numeric"
                                           aria-describedby="h-{{ $code }} r-{{ $code }}"
                                           @if ($error) aria-invalid="true" @endif
                                           :class="(v !== '' && (Number(v) < {{ $c['min'] }} || Number(v) > {{ $c['max'] }})) && '!border-bad'"
                                           @class(['field-input num pr-24 text-right disabled:bg-canvas disabled:text-ink-muted', '!border-bad' => $error])>
                                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-ink-muted">{{ $c['input'] === 'score' ? '/ 100' : $c['unit'] }}</span>
                                </div>
                                @if ($error)
                                    <p id="r-{{ $code }}" class="field-error text-right">{{ $error }}</p>
                                @else
                                    <p id="r-{{ $code }}" class="mt-1 text-right text-xs"
                                       :class="v !== '' && (Number(v) < {{ $c['min'] }} || Number(v) > {{ $c['max'] }}) ? 'font-semibold text-bad' : 'text-ink-faint'">
                                        Rentang {{ $c['min'] }}–{{ $c['max'] }}
                                    </p>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach

                <div class="px-5 py-5">
                    <label for="notes" class="field-label">Catatan untuk HRD <span class="font-normal text-ink-muted">(opsional)</span></label>
                    <textarea id="notes" name="notes" rows="3" maxlength="1000" class="field-input disabled:bg-canvas"
                              placeholder="Konteks yang tidak tergambar dari angka, mis. project besar yang diselesaikan">{{ old('notes', $employee['note']) }}</textarea>
                    @error('notes')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </fieldset>
        </x-panel>

        <div class="space-y-4 xl:sticky xl:top-8 xl:self-start">
            <x-panel title="Sebelum mengirim">
                <ul class="space-y-2.5 text-base text-ink-muted">
                    <li class="flex gap-2"><x-icon name="check" class="mt-0.5 size-4 text-ok" /> Nilai berdasarkan catatan kerja selama periode, bukan kesan pribadi.</li>
                    <li class="flex gap-2"><x-icon name="check" class="mt-0.5 size-4 text-ok" /> Kedisiplinan diisi dari rekap absensi.</li>
                    <li class="flex gap-2"><x-icon name="check" class="mt-0.5 size-4 text-ok" /> Skor akhir dihitung HRD setelah penilaian masuk, jadi tidak tampil di sini.</li>
                </ul>
            </x-panel>
            @unless ($locked)
                <div class="flex flex-col gap-2">
                    <x-btn type="submit" name="action" value="submit" icon="check" x-bind:disabled="submitting">Kirim ke HRD</x-btn>
                    <x-btn type="submit" name="action" value="draft" variant="secondary" x-bind:disabled="submitting">Simpan sebagai draft</x-btn>
                    <p class="text-sm text-ink-muted">Draft boleh belum lengkap. Kirim mewajibkan semua nilai terisi.</p>
                </div>
            @endunless
        </div>
    </form>
</x-app-layout>
