@php
    use App\Support\Format;

    // Form tambah/ubah memakai satu drawer. Saat validasi gagal, drawer dibuka lagi dengan input lama.
    $editingId = old('_editing');
    $reopen = $errors->any() && old('_form') === 'employee';
@endphp

<x-app-layout title="Karyawan" subtitle="Karyawan aktif yang ikut dinilai, atasan penilainya, dan status penilaian periode berjalan.">
    <x-slot:actions>
        <x-btn icon="plus" x-on:click="$dispatch('employee-form', { mode: 'create' })">Tambah karyawan</x-btn>
    </x-slot:actions>

    <x-filter-bar placeholder="Cari nama atau NIK" :search="$filters['q']" :total="$employees->total()"
                  :filters="[
                      ['name' => 'department', 'label' => 'Departemen', 'options' => $departments, 'value' => $filters['department_id']],
                      ['name' => 'status', 'label' => 'Penilaian', 'options' => ['unfinished' => 'Belum selesai', 'pending' => 'Belum dinilai', 'draft' => 'Draft', 'submitted' => 'Terkirim'], 'value' => $filters['status']],
                  ]"
                  :toggles="[['name' => 'nonaktif', 'label' => 'Tampilkan nonaktif', 'checked' => $filters['with_inactive']]]" />

    <x-panel flush>
        @if ($employees->isEmpty())
            <x-empty-state icon="search" title="Tidak ada karyawan yang cocok" body="Coba kata kunci lain atau hapus filter departemen." />
        @else
            <div class="overflow-x-auto">
                <table class="data-table min-w-[900px]">
                    <thead>
                        <tr>
                            <th scope="col">Nama</th>
                            <th scope="col">Departemen</th>
                            <th scope="col">Jabatan</th>
                            <th scope="col" class="text-right">Masa kerja</th>
                            <th scope="col">Atasan penilai</th>
                            <th scope="col">Penilaian</th>
                            <th scope="col"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employees as $e)
                            <tr @class(['text-ink-muted' => ! $e['is_active']])>
                                <td>
                                    <p class="font-semibold">{{ $e['name'] }} @unless ($e['is_active'])<x-status value="inactive" class="ml-1" />@endunless</p>
                                    <p class="num text-sm text-ink-muted">NIK {{ $e['nik'] }}</p>
                                </td>
                                <td>{{ $e['department'] }}</td>
                                <td>{{ $e['position'] }} <span class="text-sm text-ink-muted">· level {{ $e['level'] }}</span></td>
                                <td class="num text-right">
                                    {{ Format::number($e['tenure'], 1) }} th
                                    <p class="text-sm text-ink-muted">sejak {{ Format::date($e['join_date']) }}</p>
                                </td>
                                <td @class(['text-ink-muted', '!text-bad font-medium' => ! $e['supervisor_id']])>{{ $e['assessor'] }}</td>
                                <td>
                                    <x-status :value="$e['status']" />
                                    @if ($e['status'] === 'submitted' && $e['assessment_id'])
                                        <form method="POST" action="{{ route('hrd.assessments.reopen', $e['assessment_id']) }}" class="mt-1"
                                              x-data @submit="if (!confirm({{ \Illuminate\Support\Js::from('Buka kembali penilaian ' . $e['name'] . ' supaya atasan bisa mengoreksi?') }})) $event.preventDefault()">
                                            @csrf
                                            <button type="submit" class="text-sm font-semibold text-brand hover:underline">Buka kembali</button>
                                        </form>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <x-btn size="sm" variant="ghost" icon="pencil"
                                           x-on:click="$dispatch('employee-form', { mode: 'edit', employee: {{ \Illuminate\Support\Js::from([
                                               'id' => $e['id'], 'name' => $e['name'], 'nik' => $e['nik'],
                                               'department_id' => $e['department_id'], 'position_id' => $e['position_id'],
                                               'supervisor_id' => $e['supervisor_id'], 'user_id' => $e['user_id'],
                                               'join_date' => $e['join_date'], 'is_active' => $e['is_active'],
                                           ]) }} })">
                                        Ubah<span class="sr-only"> {{ $e['name'] }}</span>
                                    </x-btn>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $employees->links() }}
        @endif
    </x-panel>

    {{-- Drawer tambah / ubah karyawan --}}
    <div x-data="{
            open: {{ $reopen ? 'true' : 'false' }},
            mode: {{ \Illuminate\Support\Js::from($editingId ? 'edit' : 'create') }},
            f: {{ \Illuminate\Support\Js::from($reopen ? [
                'id' => old('_editing'), 'name' => old('name'), 'nik' => old('nik'),
                'department_id' => old('department_id'), 'position_id' => old('position_id'),
                'supervisor_id' => old('supervisor_id'), 'user_id' => old('user_id'),
                'join_date' => old('join_date'), 'is_active' => (bool) old('is_active', true),
            ] : ['is_active' => true]) }},
            base: {{ \Illuminate\Support\Js::from(url('/hrd/karyawan')) }},
            show(detail) {
                this.mode = detail.mode;
                this.f = detail.employee ? { ...detail.employee } : { is_active: true };
                this.open = true;
                this.$nextTick(() => this.$refs.first.focus());
            },
         }"
         @employee-form.window="show($event.detail)" @keydown.escape.window="open = false">
        <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-ink/40" @click="open = false"></div>
        <aside x-show="open" x-cloak
               x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
               x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
               class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col bg-surface shadow-[0_0_40px_rgba(26,29,36,0.18)]"
               role="dialog" aria-modal="true" aria-labelledby="drawer-title">
            <form method="POST" :action="mode === 'edit' ? base + '/' + f.id : base" class="flex h-full flex-col" novalidate>
                @csrf
                <input type="hidden" name="_method" :value="mode === 'edit' ? 'PUT' : 'POST'">
                <input type="hidden" name="_form" value="employee">
                <input type="hidden" name="_editing" :value="mode === 'edit' ? f.id : ''">

                <header class="flex items-center justify-between border-b border-line px-6 py-4">
                    <h2 id="drawer-title" class="text-md font-bold" x-text="mode === 'edit' ? 'Ubah data karyawan' : 'Tambah karyawan'"></h2>
                    <button type="button" class="grid size-9 place-items-center rounded-ctl text-ink-muted hover:bg-canvas" @click="open = false" aria-label="Tutup">
                        <x-icon name="x" />
                    </button>
                </header>

                <div class="flex-1 space-y-5 overflow-y-auto px-6 py-5">
                    <div>
                        <label for="f-name" class="field-label">Nama lengkap</label>
                        <input id="f-name" x-ref="first" name="name" x-model="f.name" required maxlength="120" autocomplete="off" class="field-input">
                        @error('name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="f-nik" class="field-label">NIK</label>
                        <input id="f-nik" name="nik" x-model="f.nik" required inputmode="numeric" maxlength="16" class="field-input num">
                        @error('nik')<p class="field-error">{{ $message }}</p>@else<p class="field-help">10–16 digit angka.</p>@enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="f-dept" class="field-label">Departemen</label>
                            <select id="f-dept" name="department_id" x-model="f.department_id" required class="field-input">
                                <option value="">Pilih</option>
                                @foreach ($departments as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                            </select>
                            @error('department_id')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="f-pos" class="field-label">Jabatan</label>
                            <select id="f-pos" name="position_id" x-model="f.position_id" required class="field-input">
                                <option value="">Pilih</option>
                                @foreach ($positions as $p)<option value="{{ $p['id'] }}">{{ $p['name'] }}</option>@endforeach
                            </select>
                            @error('position_id')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label for="f-sup" class="field-label">Atasan penilai</label>
                        <select id="f-sup" name="supervisor_id" x-model="f.supervisor_id" class="field-input">
                            <option value="">Belum ditentukan</option>
                            @foreach ($managers as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                        </select>
                        @error('supervisor_id')<p class="field-error">{{ $message }}</p>@else<p class="field-help">Hanya atasan ini yang bisa mengisi nilai karyawan tersebut.</p>@enderror
                    </div>
                    <div>
                        <label for="f-join" class="field-label">Tanggal bergabung</label>
                        <input id="f-join" type="date" name="join_date" x-model="f.join_date" required max="{{ now()->toDateString() }}" class="field-input">
                        @error('join_date')<p class="field-error">{{ $message }}</p>@else<p class="field-help">Dipakai untuk menghitung kriteria masa kerja secara otomatis.</p>@enderror
                    </div>
                    <div>
                        <label for="f-user" class="field-label">Akun portal karyawan <span class="font-normal text-ink-muted">(opsional)</span></label>
                        <select id="f-user" name="user_id" x-model="f.user_id" class="field-input">
                            <option value="">Tidak ada</option>
                            @foreach ($portalAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} · {{ $acc->email }}{{ isset($linkedAccounts[$acc->id]) ? ' (sudah tertaut)' : '' }}</option>
                            @endforeach
                        </select>
                        @error('user_id')<p class="field-error">{{ $message }}</p>@else<p class="field-help">Supaya karyawan bisa melihat skornya sendiri.</p>@enderror
                    </div>
                    <label x-show="mode === 'edit'" class="flex items-start gap-2.5">
                        <input type="checkbox" name="is_active" value="1" x-model="f.is_active" class="mt-0.5 size-4 rounded border-line-strong text-brand focus:ring-brand">
                        <span>
                            <span class="font-semibold">Masih aktif bekerja</span>
                            <span class="block text-sm text-ink-muted">Karyawan nonaktif tidak muncul di daftar penilaian dan tidak ikut ranking.</span>
                        </span>
                    </label>
                    <input type="hidden" name="is_active" value="1" x-bind:disabled="mode === 'edit'">
                </div>

                <footer class="flex justify-end gap-2 border-t border-line px-6 py-4">
                    <x-btn variant="secondary" x-on:click="open = false">Batal</x-btn>
                    <x-btn type="submit"><span x-text="mode === 'edit' ? 'Simpan perubahan' : 'Simpan karyawan'"></span></x-btn>
                </footer>
            </form>
        </aside>
    </div>
</x-app-layout>
