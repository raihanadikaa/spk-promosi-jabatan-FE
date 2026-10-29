@php
    use App\Support\Format;
    $submitted = collect($employees)->where('status', 'submitted')->count();
@endphp

<x-app-layout title="Periode penilaian" subtitle="Satu periode = satu siklus penilaian, satu set bobot, dan satu ranking.">
    <x-slot:actions>
        <x-btn icon="plus" x-on:click="$dispatch('open-period-form')">Buat periode</x-btn>
    </x-slot:actions>

    <div class="space-y-3">
        @foreach ($periods as $p)
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
                        </p>
                    </div>
                    @if ($p['status'] === 'ongoing')
                        <form method="POST" action="{{ route('demo.action') }}"
                              x-data @submit="if (!confirm({{ \Illuminate\Support\Js::from('Tutup periode ' . $p['name'] . '? Nilai dan bobot akan dikunci dan tidak bisa diubah lagi.') }})) $event.preventDefault()">
                            @csrf
                            <input type="hidden" name="action" value="period.close">
                            <x-btn type="submit" variant="secondary" icon="lock">Tutup periode</x-btn>
                        </form>
                    @endif
                </div>

                @if ($p['status'] === 'ongoing')
                    <div class="mt-5">
                        <div class="flex justify-between text-sm">
                            <span class="text-ink-muted">Penilaian terkirim</span>
                            <span class="num font-semibold">{{ $submitted }} / {{ count($employees) }}</span>
                        </div>
                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-line" role="img" aria-label="{{ $submitted }} dari {{ count($employees) }} penilaian terkirim">
                            <div class="h-full rounded-full bg-ok" style="width: {{ $submitted / count($employees) * 100 }}%"></div>
                        </div>
                        <p class="mt-3 text-sm text-ink-muted">Menutup periode mengunci semua nilai dan bobot. Pastikan ranking sudah final dan keputusan direktur sudah lengkap.</p>
                    </div>
                @endif
            </x-panel>
        @endforeach
    </div>

    {{-- Dialog buat periode --}}
    <div x-data="{ open: false }" @open-period-form.window="open = true; $nextTick(() => $refs.name.focus())" @keydown.escape.window="open = false">
        <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-40 grid place-items-center bg-ink/40 p-4" @click.self="open = false">
            <form method="POST" action="{{ route('demo.action') }}" x-show="open" x-transition.scale.95
                  class="w-full max-w-lg rounded-panel bg-surface shadow-[0_16px_48px_rgba(26,29,36,0.2)]"
                  role="dialog" aria-modal="true" aria-labelledby="period-title">
                @csrf
                <input type="hidden" name="action" value="period.create">
                <header class="border-b border-line px-6 py-4">
                    <h2 id="period-title" class="text-md font-bold">Buat periode penilaian</h2>
                    <p class="mt-0.5 text-sm text-ink-muted">Bobot kriteria disalin dari periode terakhir dan bisa diubah sebelum penilaian dimulai.</p>
                </header>
                <div class="space-y-5 px-6 py-5">
                    <div>
                        <label for="p-name" class="field-label">Nama periode</label>
                        <input id="p-name" x-ref="name" name="period_name" required maxlength="60" placeholder="Semester 1 2027" class="field-input">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="p-start" class="field-label">Mulai</label>
                            <input id="p-start" type="date" name="start_date" required class="field-input">
                        </div>
                        <div>
                            <label for="p-end" class="field-label">Selesai</label>
                            <input id="p-end" type="date" name="end_date" required class="field-input">
                        </div>
                    </div>
                    <div>
                        <label for="p-deadline" class="field-label">Batas penilaian atasan</label>
                        <input id="p-deadline" type="date" name="deadline" required class="field-input">
                    </div>
                </div>
                <footer class="flex justify-end gap-2 border-t border-line px-6 py-4">
                    <x-btn variant="secondary" x-on:click="open = false">Batal</x-btn>
                    <x-btn type="submit">Buat periode</x-btn>
                </footer>
            </form>
        </div>
    </div>
</x-app-layout>
