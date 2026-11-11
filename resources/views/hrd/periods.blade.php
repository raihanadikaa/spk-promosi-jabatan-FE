@php
    use App\Support\Format;
    $hasOngoing = collect($periods)->contains('status', 'ongoing');
    $reopen = $errors->hasAny(['period_name', 'start_date', 'end_date', 'assessment_deadline']);
@endphp

<x-app-layout title="Periode penilaian" subtitle="Satu periode = satu siklus penilaian, satu set bobot, dan satu ranking. Hanya satu periode yang boleh berlangsung.">
    <x-slot:actions>
        <x-btn icon="plus" x-on:click="$dispatch('open-period-form')">Buat periode</x-btn>
    </x-slot:actions>

    @if (empty($periods))
        <x-panel><x-empty-state icon="calendar" title="Belum ada periode" body="Buat periode pertama untuk mulai menilai karyawan." /></x-panel>
    @endif

    <div class="space-y-3">
        @foreach ($periods as $p)
            @php
                $s = $stats[$p['id']] ?? collect();
                $submitted = (int) ($s['submitted'] ?? 0);
                $draft = (int) ($s['draft'] ?? 0);
            @endphp
            <x-panel>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="text-md font-bold">{{ $p['name'] }}</h2>
                            <x-status :value="$p['status']" />
                        </div>
                        <p class="mt-1 text-ink-muted">
                            {{ Format::date($p['start']) }} – {{ Format::date($p['end']) }}
                            · batas penilaian {{ Format::date($p['deadline']) }}
                            @if ($p['calculated_at']) · ranking dihitung {{ Format::dateTime($p['calculated_at']) }} @endif
                        </p>
                    </div>

                    @if ($p['status'] === 'ongoing')
                        <form method="POST" action="{{ route('hrd.periods.close', $p['id']) }}"
                              x-data @submit="if (!confirm({{ \Illuminate\Support\Js::from('Tutup periode ' . $p['name'] . '? Nilai, bobot, dan keputusan akan dikunci.') }})) $event.preventDefault()">
                            @csrf
                            <x-btn type="submit" variant="secondary" icon="lock">Tutup periode</x-btn>
                        </form>
                    @elseif ($p['status'] === 'draft')
                        <div class="flex flex-wrap gap-2">
                            <x-btn variant="ghost" icon="pencil"
                                   x-on:click="$dispatch('open-period-form', {{ \Illuminate\Support\Js::from(['id' => $p['id'], 'period_name' => $p['name'], 'start_date' => $p['start'], 'end_date' => $p['end'], 'assessment_deadline' => $p['deadline']]) }})">
                                Ubah<span class="sr-only"> {{ $p['name'] }}</span>
                            </x-btn>
                            <form method="POST" action="{{ route('hrd.periods.destroy', $p['id']) }}"
                                  x-data @submit="if (!confirm({{ \Illuminate\Support\Js::from('Hapus periode draft ' . $p['name'] . '? Bobotnya ikut terhapus.') }})) $event.preventDefault()">
                                @csrf @method('DELETE')
                                <x-btn type="submit" variant="ghost" icon="trash">Hapus<span class="sr-only"> {{ $p['name'] }}</span></x-btn>
                            </form>
                            <form method="POST" action="{{ route('hrd.periods.activate', $p['id']) }}">
                                @csrf
                                <x-btn type="submit" icon="check" :disabled="$hasOngoing"
                                       :title="$hasOngoing ? 'Tutup periode yang sedang berlangsung terlebih dahulu' : null">Mulai periode</x-btn>
                            </form>
                        </div>
                    @endif
                </div>

                @if ($p['status'] !== 'draft')
                    <div class="mt-5">
                        <div class="flex justify-between text-sm">
                            <span class="text-ink-muted">Penilaian terkirim{{ $draft ? ", $draft masih draft" : '' }}</span>
                            <span class="num font-semibold">{{ $submitted }} / {{ $p['status'] === 'ongoing' ? $activeEmployees : $submitted + $draft }}</span>
                        </div>
                        @php $den = max(1, $p['status'] === 'ongoing' ? $activeEmployees : $submitted + $draft); @endphp
                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-line" aria-hidden="true">
                            <div class="h-full rounded-full bg-ok" style="width: {{ min(100, $submitted / $den * 100) }}%"></div>
                        </div>
                    </div>
                @endif

                @if ($p['status'] === 'ongoing')
                    <p class="mt-3 text-sm text-ink-muted">Syarat menutup: ranking sudah dihitung dan semua rekomendasi sudah diputuskan direktur.</p>
                @elseif ($p['status'] === 'draft')
                    <p class="mt-3 text-sm text-ink-muted">Atur bobot kriteria sebelum memulai. Atasan baru bisa mengisi nilai setelah periode dimulai.</p>
                @endif
            </x-panel>
        @endforeach
    </div>

    {{-- Dialog buat periode --}}
    <div x-data="{
            open: {{ $reopen ? 'true' : 'false' }},
            f: {{ \Illuminate\Support\Js::from($reopen ? ['id' => old('_editing'), 'period_name' => old('period_name'), 'start_date' => old('start_date'), 'end_date' => old('end_date'), 'assessment_deadline' => old('assessment_deadline')] : (object) []) }},
            base: {{ \Illuminate\Support\Js::from(url('/hrd/periode')) }},
         }"
         @open-period-form.window="f = $event.detail ? { ...$event.detail } : {}; open = true; $nextTick(() => $refs.name.focus())" @keydown.escape.window="open = false">
        <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-40 grid place-items-center bg-ink/40 p-4" @click.self="open = false">
            <form method="POST" :action="f.id ? base + '/' + f.id : base" x-show="open" x-transition.scale.95 novalidate
                  class="w-full max-w-lg rounded-panel bg-surface shadow-[0_16px_48px_rgba(26,29,36,0.2)]"
                  role="dialog" aria-modal="true" aria-labelledby="period-title">
                @csrf
                <input type="hidden" name="_method" :value="f.id ? 'PUT' : 'POST'">
                <input type="hidden" name="_editing" :value="f.id ?? ''">
                <header class="border-b border-line px-6 py-4">
                    <h2 id="period-title" class="text-md font-bold" x-text="f.id ? 'Ubah periode draft' : 'Buat periode penilaian'"></h2>
                    <p class="mt-0.5 text-sm text-ink-muted" x-text="f.id ? 'Hanya periode yang belum dimulai yang bisa diubah.' : 'Dibuat sebagai draft. Bobot kriteria disalin dari periode terakhir.'"></p>
                </header>
                <div class="space-y-5 px-6 py-5">
                    <div>
                        <label for="p-name" class="field-label">Nama periode</label>
                        <input id="p-name" x-ref="name" name="period_name" x-model="f.period_name" required maxlength="60" placeholder="Semester 1 2027" class="field-input">
                        @error('period_name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="p-start" class="field-label">Mulai</label>
                            <input id="p-start" type="date" name="start_date" x-model="f.start_date" required class="field-input">
                            @error('start_date')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="p-end" class="field-label">Selesai</label>
                            <input id="p-end" type="date" name="end_date" x-model="f.end_date" required class="field-input">
                            @error('end_date')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label for="p-deadline" class="field-label">Batas penilaian atasan</label>
                        <input id="p-deadline" type="date" name="assessment_deadline" x-model="f.assessment_deadline" required class="field-input">
                        @error('assessment_deadline')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
                <footer class="flex justify-end gap-2 border-t border-line px-6 py-4">
                    <x-btn variant="secondary" x-on:click="open = false">Batal</x-btn>
                    <x-btn type="submit"><span x-text="f.id ? 'Simpan perubahan' : 'Buat periode'"></span></x-btn>
                </footer>
            </form>
        </div>
    </div>
</x-app-layout>
