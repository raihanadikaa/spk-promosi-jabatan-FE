@php use App\Support\Format; @endphp

<x-app-layout title="Karyawan" subtitle="Data karyawan yang ikut dinilai pada periode berjalan, beserta atasan penilainya.">
    <x-slot:actions>
        <x-btn icon="plus" x-on:click="$dispatch('open-employee-form')">Tambah karyawan</x-btn>
    </x-slot:actions>

    <div>

        <form method="GET" class="mb-4 flex flex-wrap items-center gap-3" role="search">
            <div class="relative w-full sm:w-72">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-ink-faint" />
                <label for="q" class="sr-only">Cari nama atau NIK</label>
                <input id="q" type="search" name="q" value="{{ $q }}" maxlength="100" placeholder="Cari nama atau NIK" class="field-input pl-9">
            </div>
            <label for="department" class="sr-only">Departemen</label>
            <select id="department" name="department" class="field-input w-auto pr-9" x-on:change="$el.form.requestSubmit()">
                <option value="">Semua departemen</option>
                @foreach ($departments as $d)
                    <option @selected($department === $d)>{{ $d }}</option>
                @endforeach
            </select>
            <x-btn type="submit" variant="secondary">Terapkan</x-btn>
            @if ($q || $department)
                <a href="{{ route('hrd.employees') }}" class="link text-sm">Hapus filter</a>
            @endif
            <p class="num text-sm text-ink-muted sm:ml-auto">{{ count($employees) }} karyawan</p>
        </form>

        <x-panel flush>
            @if (empty($employees))
                <x-empty-state icon="search" title="Tidak ada karyawan yang cocok" body="Coba kata kunci lain atau hapus filter departemen." />
            @else
                <div class="overflow-x-auto">
                    <table class="data-table min-w-[820px]">
                        <thead>
                            <tr>
                                <th scope="col">Nama</th>
                                <th scope="col">Departemen</th>
                                <th scope="col">Jabatan</th>
                                <th scope="col" class="text-right">Masa kerja</th>
                                <th scope="col">Atasan penilai</th>
                                <th scope="col">Penilaian</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($employees as $e)
                                <tr>
                                    <td>
                                        <p class="font-semibold">{{ $e['name'] }}</p>
                                        <p class="num text-sm text-ink-muted">NIK {{ $e['nik'] }}</p>
                                    </td>
                                    <td>{{ $e['department'] }}</td>
                                    <td>{{ $e['position'] }} <span class="text-sm text-ink-muted">· level {{ $e['level'] }}</span></td>
                                    <td class="num text-right">
                                        {{ Format::number($e['tenure'], 1) }} th
                                        <p class="text-sm text-ink-muted">sejak {{ Format::date($e['join_date']) }}</p>
                                    </td>
                                    <td class="text-ink-muted">{{ $e['assessor'] }}</td>
                                    <td><x-status :value="$e['status']" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-panel>

        {{-- Drawer tambah karyawan --}}
        <div x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }" @open-employee-form.window="open = true; $nextTick(() => $refs.first.focus())" @keydown.escape.window="open = false">
            <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-ink/40" @click="open = false"></div>
            <aside x-show="open" x-cloak
                   x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                   x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                   class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col bg-surface shadow-[0_0_40px_rgba(26,29,36,0.18)]"
                   role="dialog" aria-modal="true" aria-labelledby="drawer-title">
                <form method="POST" action="{{ route('demo.action') }}" class="flex h-full flex-col">
                    @csrf
                    <input type="hidden" name="action" value="employee.create">
                    <header class="flex items-center justify-between border-b border-line px-6 py-4">
                        <h2 id="drawer-title" class="text-md font-bold">Tambah karyawan</h2>
                        <button type="button" class="grid size-9 place-items-center rounded-ctl text-ink-muted hover:bg-canvas" @click="open = false" aria-label="Tutup">
                            <x-icon name="x" />
                        </button>
                    </header>
                    <div class="flex-1 space-y-5 overflow-y-auto px-6 py-5">
                        <div>
                            <label for="f-name" class="field-label">Nama lengkap</label>
                            <input id="f-name" x-ref="first" name="name" required maxlength="120" autocomplete="off" class="field-input">
                        </div>
                        <div>
                            <label for="f-nik" class="field-label">NIK</label>
                            <input id="f-nik" name="nik" required inputmode="numeric" pattern="[0-9]{10,16}" maxlength="16" class="field-input num">
                            <p class="field-help">10–16 digit angka.</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="f-dept" class="field-label">Departemen</label>
                                <select id="f-dept" name="department" required class="field-input">
                                    @foreach ($departments as $d)<option>{{ $d }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label for="f-pos" class="field-label">Jabatan</label>
                                <select id="f-pos" name="position" required class="field-input">
                                    @foreach ($positions as $p)<option>{{ $p['name'] }}</option>@endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="f-join" class="field-label">Tanggal bergabung</label>
                            <input id="f-join" type="date" name="join_date" required max="{{ now()->toDateString() }}" class="field-input">
                            <p class="field-help">Dipakai untuk menghitung kriteria masa kerja secara otomatis.</p>
                        </div>
                    </div>
                    <footer class="flex justify-end gap-2 border-t border-line px-6 py-4">
                        <x-btn variant="secondary" x-on:click="open = false">Batal</x-btn>
                        <x-btn type="submit">Simpan karyawan</x-btn>
                    </footer>
                </form>
            </aside>
        </div>
    </div>
</x-app-layout>
