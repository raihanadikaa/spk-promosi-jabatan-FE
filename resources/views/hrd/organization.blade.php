@php
    use Illuminate\Support\Js;

    // Form tambah/ubah memakai satu dialog per jenis. Saat validasi gagal, dialog dibuka lagi dengan input lama.
    $reopen = $errors->any() && in_array(old('_form'), ['department', 'position'], true) ? old('_form') : null;
    if ($reopen === 'position') { $tab = 'jabatan'; }
@endphp

<x-app-layout title="Departemen & jabatan" subtitle="Struktur organisasi yang dipakai di data karyawan. Level jabatan menentukan usulan jabatan saat promosi.">
    <x-slot:actions>
        @if ($tab === 'jabatan')
            <x-btn icon="plus" x-on:click="$dispatch('org-form', { kind: 'position', mode: 'create' })">Tambah jabatan</x-btn>
        @else
            <x-btn icon="plus" x-on:click="$dispatch('org-form', { kind: 'department', mode: 'create' })">Tambah departemen</x-btn>
        @endif
    </x-slot:actions>

    <nav class="mb-4 flex gap-1 border-b border-line" aria-label="Jenis data">
        @foreach (['departemen' => 'Departemen', 'jabatan' => 'Jabatan'] as $key => $label)
            <a href="{{ route('hrd.organization', $key === 'jabatan' ? ['tab' => 'jabatan'] : []) }}"
               @class(['-mb-px border-b-2 px-4 py-2.5 text-base font-semibold transition-colors',
                       'border-brand text-brand' => $tab === $key,
                       'border-transparent text-ink-muted hover:text-ink' => $tab !== $key])
               @if ($tab === $key) aria-current="page" @endif>
                {{ $label }}
                <span class="num ml-1 text-sm font-medium text-ink-faint">{{ $key === 'jabatan' ? $positions->count() : $departments->count() }}</span>
            </a>
        @endforeach
    </nav>

    <x-panel flush>
        @php $items = $tab === 'jabatan' ? $positions : $departments; @endphp
        @if ($items->isEmpty())
            <x-empty-state icon="building" :title="$tab === 'jabatan' ? 'Belum ada jabatan' : 'Belum ada departemen'" body="Tambahkan dulu sebelum mendaftarkan karyawan." />
        @else
            <div class="overflow-x-auto">
                <table class="data-table min-w-[560px]">
                    <thead>
                        <tr>
                            <th scope="col">{{ $tab === 'jabatan' ? 'Nama jabatan' : 'Nama departemen' }}</th>
                            @if ($tab === 'jabatan')<th scope="col">Level</th>@endif
                            <th scope="col" class="text-right">Jumlah karyawan</th>
                            <th scope="col"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            @php
                                $name = $tab === 'jabatan' ? $item->position_name : $item->department_name;
                                $kind = $tab === 'jabatan' ? 'position' : 'department';
                                $destroy = $tab === 'jabatan' ? route('hrd.positions.destroy', $item) : route('hrd.departments.destroy', $item);
                            @endphp
                            <tr>
                                <td class="font-semibold">{{ $name }}</td>
                                @if ($tab === 'jabatan')<td class="num">Level {{ $item->level }}</td>@endif
                                <td class="num text-right">{{ $item->employees_count }} karyawan</td>
                                <td>
                                    <div class="flex justify-end gap-1">
                                        <x-btn size="sm" variant="ghost" icon="pencil"
                                               x-on:click="$dispatch('org-form', { kind: '{{ $kind }}', mode: 'edit', item: {{ Js::from(['id' => $item->id, 'name' => $name, 'level' => $item->level ?? null]) }} })">
                                            Ubah<span class="sr-only"> {{ $name }}</span>
                                        </x-btn>
                                        @if ($item->employees_count === 0)
                                            <form method="POST" action="{{ $destroy }}"
                                                  x-data @submit="if (!confirm({{ Js::from('Hapus ' . $name . '?') }})) $event.preventDefault()">
                                                @csrf @method('DELETE')
                                                <x-btn type="submit" size="sm" variant="ghost" icon="trash">Hapus<span class="sr-only"> {{ $name }}</span></x-btn>
                                            </form>
                                        @else
                                            <x-btn size="sm" variant="ghost" icon="trash" disabled title="Masih dipakai karyawan, tidak bisa dihapus">Hapus</x-btn>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-panel>
    <p class="mt-3 text-sm text-ink-muted">Departemen atau jabatan yang masih dipakai karyawan tidak bisa dihapus. Pindahkan dulu karyawannya di halaman Karyawan.</p>

    {{-- Dialog tambah / ubah --}}
    <div x-data="{
            open: {{ $reopen ? 'true' : 'false' }},
            kind: {{ Js::from($reopen ?? 'department') }},
            mode: {{ Js::from(old('_editing') ? 'edit' : 'create') }},
            f: {{ Js::from($reopen ? ['id' => old('_editing'), 'name' => old($reopen === 'position' ? 'position_name' : 'department_name'), 'level' => old('level')] : []) }},
            urls: {{ Js::from(['department' => url('/hrd/departemen'), 'position' => url('/hrd/jabatan')]) }},
            show(d) { this.kind = d.kind; this.mode = d.mode; this.f = d.item ? { ...d.item } : {}; this.open = true; this.$nextTick(() => this.$refs.name.focus()); },
         }"
         @org-form.window="show($event.detail)" @keydown.escape.window="open = false">
        <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-40 grid place-items-center bg-ink/40 p-4" @click.self="open = false">
            <form method="POST" :action="mode === 'edit' ? urls[kind] + '/' + f.id : urls[kind]" novalidate
                  class="w-full max-w-md rounded-panel bg-surface shadow-[0_16px_48px_rgba(26,29,36,0.2)]"
                  role="dialog" aria-modal="true" aria-labelledby="org-title">
                @csrf
                <input type="hidden" name="_method" :value="mode === 'edit' ? 'PUT' : 'POST'">
                <input type="hidden" name="_form" :value="kind">
                <input type="hidden" name="_editing" :value="mode === 'edit' ? f.id : ''">
                <header class="border-b border-line px-6 py-4">
                    <h2 id="org-title" class="text-md font-bold"
                        x-text="(mode === 'edit' ? 'Ubah ' : 'Tambah ') + (kind === 'position' ? 'jabatan' : 'departemen')"></h2>
                </header>
                <div class="space-y-5 px-6 py-5">
                    <div>
                        <label for="o-name" class="field-label" x-text="kind === 'position' ? 'Nama jabatan' : 'Nama departemen'"></label>
                        <input id="o-name" x-ref="name" :name="kind === 'position' ? 'position_name' : 'department_name'" x-model="f.name" required maxlength="100" class="field-input">
                        @error('department_name')<p class="field-error">{{ $message }}</p>@enderror
                        @error('position_name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <template x-if="kind === 'position'">
                        <div>
                            <label for="o-level" class="field-label">Level</label>
                            <input id="o-level" type="number" name="level" x-model="f.level" min="1" max="20" required class="field-input num w-32">
                            <p class="field-help">Makin tinggi makin senior. Usulan promosi = jabatan dengan level terdekat di atasnya.</p>
                        </div>
                    </template>
                    @error('level')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <footer class="flex justify-end gap-2 border-t border-line px-6 py-4">
                    <x-btn variant="secondary" x-on:click="open = false">Batal</x-btn>
                    <x-btn type="submit">Simpan</x-btn>
                </footer>
            </form>
        </div>
    </div>
</x-app-layout>
