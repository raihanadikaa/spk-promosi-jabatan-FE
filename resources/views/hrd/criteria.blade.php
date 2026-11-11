@php
    $initial = collect($criteria)->map(fn ($c) => [
        'code' => $c['code'],
        'weight' => (int) old("criteria.{$c['code']}.weight", $c['weight']),
        'type' => old("criteria.{$c['code']}.type", $c['type']),
    ])->values();
    $saved = collect($criteria)->map(fn ($c) => ['code' => $c['code'], 'weight' => $c['weight'], 'type' => $c['type']])->values();
    $colors = ['C1' => '#3F6E9A', 'C2' => '#21877A', 'C3' => '#C08A1E', 'C4' => '#86549B', 'C5' => '#5F8F4E'];
@endphp

<x-app-layout title="Kriteria & bobot" subtitle="Bobot berlaku untuk {{ $period['name'] }}. Total harus tepat 100% sebelum ranking bisa dihitung.">
    <x-slot:actions>
        @if ($structureEditable)
            <x-btn variant="secondary" icon="plus" x-on:click="$dispatch('criteria-form', { mode: 'create' })">Tambah kriteria</x-btn>
        @endif
        @if ($editable && $previousName)
            <form method="POST" action="{{ route('hrd.criteria.copy') }}"
                  x-data @submit="if (!confirm({{ \Illuminate\Support\Js::from('Ganti bobot sekarang dengan bobot ' . $previousName . '?') }})) $event.preventDefault()">
                @csrf
                <x-btn type="submit" variant="secondary" icon="refresh">Salin dari {{ $previousName }}</x-btn>
            </form>
        @endif
    </x-slot:actions>

    @unless ($structureEditable)
        <p class="mb-4 text-sm text-ink-muted">Menambah atau menghapus kriteria hanya bisa saat tidak ada periode yang berlangsung. Nama dan cara menilai tetap bisa diubah.</p>
    @endunless

    @unless ($editable)
        <div class="mb-6 flex gap-3 rounded-panel border border-line bg-surface px-4 py-3 text-ink-muted" role="note">
            <x-icon name="lock" class="mt-0.5" />
            <p>Periode {{ $period['name'] }} sudah ditutup; bobotnya dikunci. Buat periode baru untuk mengatur bobot berikutnya.</p>
        </div>
    @endunless

    <form method="POST" action="{{ route('hrd.criteria.update') }}"
          x-data="{ rows: {{ \Illuminate\Support\Js::from($initial) }}, get total() { return this.rows.reduce((s, r) => s + (Number(r.weight) || 0), 0) } }">
        @csrf
        @method('PUT')

        <x-panel flush>
            {{-- Pratinjau komposisi bobot, ikut berubah saat angka diketik --}}
            <div class="border-b border-line px-5 py-5">
                <div class="flex items-baseline justify-between">
                    <p class="font-semibold">Komposisi bobot</p>
                    <p class="num text-md font-bold" :class="total === 100 ? 'text-ok' : 'text-bad'" aria-live="polite">
                        <span x-text="total"></span>%
                        <span class="text-sm font-medium" x-text="total === 100 ? 'pas' : (total > 100 ? 'kelebihan ' + (total - 100) + '%' : 'kurang ' + (100 - total) + '%')"></span>
                    </p>
                </div>
                <div class="mt-3 flex h-3 w-full gap-px overflow-hidden rounded-full bg-line" aria-hidden="true">
                    @foreach ($criteria as $i => $c)
                        <span class="h-full transition-[width] duration-200" style="background: {{ $colors[$c['code']] ?? '#8A909C' }}" :style="{ width: Math.min(rows[{{ $i }}].weight, 100) + '%' }"></span>
                    @endforeach
                </div>
                @error('criteria')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <fieldset @disabled(! $editable)>
                <legend class="sr-only">Bobot dan jenis tiap kriteria</legend>
                <div class="overflow-x-auto">
                    <table class="data-table min-w-[720px]">
                        <thead>
                            <tr>
                                <th scope="col">Kriteria</th>
                                <th scope="col">Cara menilai</th>
                                <th scope="col">Jenis</th>
                                <th scope="col" class="w-36 text-right">Bobot</th>
                                <th scope="col"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($criteria as $i => $c)
                                <tr>
                                    <td>
                                        <span class="flex items-center gap-2 font-semibold"><x-criteria-dot :code="$c['code']" /> {{ $c['name'] }}</span>
                                        <span class="num text-sm text-ink-muted">{{ $c['code'] }} · {{ $c['unit'] }}</span>
                                    </td>
                                    <td class="max-w-sm text-sm text-ink-muted">{{ $c['help'] }}</td>
                                    <td>
                                        <label for="type-{{ $c['code'] }}" class="sr-only">Jenis {{ $c['name'] }}</label>
                                        <select id="type-{{ $c['code'] }}" name="criteria[{{ $c['code'] }}][type]" x-model="rows[{{ $i }}].type" class="field-input w-auto py-1.5 pr-9 text-sm disabled:bg-canvas">
                                            <option value="benefit">Benefit (makin besar makin baik)</option>
                                            <option value="cost">Cost (makin kecil makin baik)</option>
                                        </select>
                                    </td>
                                    <td>
                                        <label for="w-{{ $c['code'] }}" class="sr-only">Bobot {{ $c['name'] }} dalam persen</label>
                                        <div class="relative ml-auto w-28">
                                            <input id="w-{{ $c['code'] }}" type="number" name="criteria[{{ $c['code'] }}][weight]" min="0" max="100" step="1" required
                                                   x-model.number="rows[{{ $i }}].weight" class="field-input num pr-8 text-right disabled:bg-canvas">
                                            <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-ink-muted">%</span>
                                        </div>
                                        @error("criteria.{$c['code']}.weight")<p class="field-error text-right">{{ $message }}</p>@enderror
                                    </td>
                                    <td class="text-right">
                                        <x-btn size="sm" variant="ghost" icon="pencil"
                                               x-on:click="$dispatch('criteria-form', { mode: 'edit', item: {{ \Illuminate\Support\Js::from([
                                                   'id' => $c['id'], 'criteria_name' => $c['name'], 'description' => $c['help'], 'input_type' => $c['input'],
                                                   'unit' => $c['unit'], 'min_value' => $c['min'], 'max_value' => $c['max'],
                                               ]) }} })">
                                            Ubah<span class="sr-only"> {{ $c['name'] }}</span>
                                        </x-btn>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </fieldset>

            @if ($editable)
                <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-4">
                    <p class="text-sm text-ink-muted" x-show="total === 100">Mengubah bobot atau jenis mengubah ranking. Hitung ulang ranking setelah menyimpan.</p>
                    <p class="text-sm font-medium text-bad" x-show="total !== 100" x-cloak role="alert">Total bobot harus 100% sebelum bisa disimpan.</p>
                    <div class="flex gap-2">
                        <x-btn variant="ghost" x-on:click="rows = {{ \Illuminate\Support\Js::from($saved) }}">Kembalikan</x-btn>
                        <x-btn type="submit" x-bind:disabled="total !== 100">Simpan bobot</x-btn>
                    </div>
                </footer>
            @endif
        </x-panel>
    </form>

    {{-- Dialog tambah / ubah kriteria (di luar form bobot) --}}
    @php $reopenCriteria = $errors->any() && old('_form') === 'criterion'; @endphp
    <div x-data="{
            open: {{ $reopenCriteria ? 'true' : 'false' }},
            structure: {{ $structureEditable ? 'true' : 'false' }},
            f: {{ \Illuminate\Support\Js::from($reopenCriteria ? [
                'id' => old('_editing'), 'criteria_name' => old('criteria_name'), 'description' => old('description'), 'input_type' => old('input_type', 'score'),
                'unit' => old('unit'), 'min_value' => old('min_value'), 'max_value' => old('max_value'), 'attribute_type' => old('attribute_type', 'benefit'),
            ] : ['input_type' => 'score', 'attribute_type' => 'benefit', 'min_value' => 0, 'max_value' => 100, 'unit' => 'poin']) }},
            base: {{ \Illuminate\Support\Js::from(url('/hrd/kriteria')) }},
            get full() { return this.structure && this.f.input_type !== 'auto' },
            show(d) { this.f = d.item ? { ...d.item } : { input_type: 'score', attribute_type: 'benefit', min_value: 0, max_value: 100, unit: 'poin' }; this.open = true; this.$nextTick(() => this.$refs.name.focus()); },
         }"
         @criteria-form.window="show($event.detail)" @keydown.escape.window="open = false">
        <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-40 grid place-items-center overflow-y-auto bg-ink/40 p-4" @click.self="open = false">
            <form method="POST" :action="f.id ? base + '/' + f.id : base" novalidate
                  class="w-full max-w-lg rounded-panel bg-surface shadow-[0_16px_48px_rgba(26,29,36,0.2)]"
                  role="dialog" aria-modal="true" aria-labelledby="criteria-title">
                @csrf
                <input type="hidden" name="_method" :value="f.id ? 'PUT' : 'POST'">
                <input type="hidden" name="_form" value="criterion">
                <input type="hidden" name="_editing" :value="f.id ?? ''">
                <header class="border-b border-line px-6 py-4">
                    <h2 id="criteria-title" class="text-md font-bold" x-text="f.id ? 'Ubah kriteria' : 'Tambah kriteria'"></h2>
                    <p class="mt-0.5 text-sm text-ink-muted" x-show="!f.id">Kriteria baru mulai dengan bobot 0%. Setelah itu atur ulang bobot sampai total 100%.</p>
                </header>
                <div class="space-y-5 px-6 py-5">
                    <div>
                        <label for="c-name" class="field-label">Nama kriteria</label>
                        <input id="c-name" x-ref="name" name="criteria_name" x-model="f.criteria_name" required maxlength="60" class="field-input">
                        @error('criteria_name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="c-desc" class="field-label">Cara menilai</label>
                        <textarea id="c-desc" name="description" x-model="f.description" rows="2" required maxlength="255" class="field-input"></textarea>
                        @error('description')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <template x-if="full">
                        <div class="space-y-5">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="c-input" class="field-label">Bentuk nilai</label>
                                    <select id="c-input" name="input_type" x-model="f.input_type" class="field-input">
                                        <option value="score">Skor (mis. 0–100)</option>
                                        <option value="count">Jumlah (mis. kali, sertifikat)</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="c-unit" class="field-label">Satuan</label>
                                    <input id="c-unit" name="unit" x-model="f.unit" required maxlength="30" class="field-input">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="c-min" class="field-label">Nilai minimal</label>
                                    <input id="c-min" type="number" name="min_value" x-model="f.min_value" min="0" required class="field-input num">
                                </div>
                                <div>
                                    <label for="c-max" class="field-label">Nilai maksimal</label>
                                    <input id="c-max" type="number" name="max_value" x-model="f.max_value" min="1" required class="field-input num">
                                </div>
                            </div>
                            <div x-show="!f.id">
                                <label for="c-type" class="field-label">Jenis</label>
                                <select id="c-type" name="attribute_type" x-model="f.attribute_type" class="field-input">
                                    <option value="benefit">Benefit (makin besar makin baik)</option>
                                    <option value="cost">Cost (makin kecil makin baik)</option>
                                </select>
                            </div>
                        </div>
                    </template>
                    @foreach (['unit', 'min_value', 'max_value', 'input_type', 'attribute_type'] as $field)
                        @error($field)<p class="field-error">{{ $message }}</p>@enderror
                    @endforeach
                </div>
                <footer class="flex items-center gap-2 border-t border-line px-6 py-4">
                    <button type="submit" form="criteria-delete" x-show="f.id && full" class="mr-auto text-sm font-semibold text-bad hover:underline">Hapus kriteria</button>
                    <span class="ml-auto"></span>
                    <x-btn variant="secondary" x-on:click="open = false">Batal</x-btn>
                    <x-btn type="submit">Simpan</x-btn>
                </footer>
            </form>
            <form id="criteria-delete" method="POST" :action="base + '/' + f.id" class="hidden"
                  @submit="if (!confirm('Hapus kriteria ' + f.criteria_name + '?')) $event.preventDefault()">
                @csrf @method('DELETE')
            </form>
        </div>
    </div>
</x-app-layout>
