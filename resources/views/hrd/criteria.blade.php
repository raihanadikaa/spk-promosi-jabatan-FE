@php
    $initial = collect($criteria)->map(fn ($c) => ['code' => $c['code'], 'weight' => $c['weight'], 'type' => $c['type']])->values();
    $colors = ['C1' => '#3F6E9A', 'C2' => '#21877A', 'C3' => '#C08A1E', 'C4' => '#86549B', 'C5' => '#5F8F4E'];
@endphp

<x-app-layout title="Kriteria & bobot" subtitle="Bobot berlaku untuk {{ $period['name'] }}. Total harus tepat 100% sebelum ranking bisa dihitung.">
    <form method="POST" action="{{ route('demo.action') }}"
          x-data="{ rows: {{ \Illuminate\Support\Js::from($initial) }}, get total() { return this.rows.reduce((s, r) => s + (Number(r.weight) || 0), 0) } }">
        @csrf
        <input type="hidden" name="action" value="weights.save">

        <x-panel flush>
            {{-- Pratinjau komposisi bobot, ikut berubah saat angka diketik --}}
            <div class="border-b border-line px-5 py-5">
                <div class="flex items-baseline justify-between">
                    <p class="font-semibold">Komposisi bobot</p>
                    <p class="num text-md font-bold" :class="total === 100 ? 'text-ok' : 'text-bad'">
                        <span x-text="total"></span>%
                        <span class="text-sm font-medium" x-text="total === 100 ? 'pas' : (total > 100 ? 'kelebihan ' + (total - 100) + '%' : 'kurang ' + (100 - total) + '%')"></span>
                    </p>
                </div>
                <div class="mt-3 flex h-3 w-full gap-px overflow-hidden rounded-full bg-line" aria-hidden="true">
                    @foreach ($criteria as $i => $c)
                        <span class="h-full transition-[width] duration-200" style="background: {{ $colors[$c['code']] }}" :style="{ width: Math.min(rows[{{ $i }}].weight, 100) + '%' }"></span>
                    @endforeach
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="data-table min-w-[720px]">
                    <thead>
                        <tr>
                            <th scope="col">Kriteria</th>
                            <th scope="col">Cara menilai</th>
                            <th scope="col">Jenis</th>
                            <th scope="col" class="w-36 text-right">Bobot</th>
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
                                    <select id="type-{{ $c['code'] }}" name="criteria[{{ $c['code'] }}][type]" x-model="rows[{{ $i }}].type" class="field-input w-auto py-1.5 pr-9 text-sm">
                                        <option value="benefit">Benefit (makin besar makin baik)</option>
                                        <option value="cost">Cost (makin kecil makin baik)</option>
                                    </select>
                                </td>
                                <td>
                                    <label for="w-{{ $c['code'] }}" class="sr-only">Bobot {{ $c['name'] }} dalam persen</label>
                                    <div class="relative ml-auto w-28">
                                        <input id="w-{{ $c['code'] }}" type="number" name="criteria[{{ $c['code'] }}][weight]" min="0" max="100" step="1" required
                                               x-model.number="rows[{{ $i }}].weight" class="field-input num pr-8 text-right">
                                        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-ink-muted">%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-4">
                <p class="text-sm text-ink-muted" x-show="total === 100">Mengubah bobot akan mengubah ranking. Hitung ulang ranking setelah menyimpan.</p>
                <p class="text-sm font-medium text-bad" x-show="total !== 100" x-cloak role="alert">Total bobot harus 100% sebelum bisa disimpan.</p>
                <div class="flex gap-2">
                    <x-btn variant="ghost" x-on:click="rows = {{ \Illuminate\Support\Js::from($initial) }}">Kembalikan</x-btn>
                    <x-btn type="submit" x-bind:disabled="total !== 100">Simpan bobot</x-btn>
                </div>
            </footer>
        </x-panel>
    </form>
</x-app-layout>
