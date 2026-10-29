@php
    use App\Support\Format;

    $temp = session('temporary_password');
    $reopen = $errors->hasAny(['name', 'email', 'role_id']);
    $editing = old('_editing');
@endphp

<x-app-layout title="Pengguna & role" subtitle="Akun yang bisa masuk ke TalentRank beserta hak aksesnya.">
    <x-slot:actions>
        <x-btn icon="plus" x-on:click="$dispatch('user-form', { mode: 'create' })">Tambah pengguna</x-btn>
    </x-slot:actions>

    @if ($temp)
        {{-- Password sementara hanya tampil sekali (flash), tidak disimpan di mana pun dalam bentuk asli. --}}
        <div class="mb-6 rounded-panel border border-ok/30 bg-ok-bg px-5 py-4 text-ok" role="status">
            <p class="font-semibold">Password sementara untuk {{ $temp['name'] }} ({{ $temp['email'] }})</p>
            <p class="mt-2 inline-block select-all rounded-ctl bg-surface px-3 py-1.5 font-mono text-md font-bold tracking-wide text-ink">{{ $temp['password'] }}</p>
            <p class="mt-2 text-sm">Serahkan secara langsung ke pengguna. Password ini tidak akan ditampilkan lagi; minta pengguna menggantinya lewat menu Ganti password.</p>
        </div>
    @endif

    <x-filter-bar placeholder="Cari nama atau email" :search="$filters['q'] ?? null" :total="$users->total()"
                  :filters="[
                      ['name' => 'role', 'label' => 'Role', 'value' => $filters['role'] ?? null,
                       'options' => $roles->mapWithKeys(fn ($r) => [$r->id => $roleLabels[$r->role_name] ?? $r->role_name])->all()],
                      ['name' => 'status', 'label' => 'Status', 'value' => $filters['status'] ?? null,
                       'options' => ['active' => 'Aktif', 'inactive' => 'Nonaktif']],
                  ]" />

    <x-panel flush>
        @if ($users->isEmpty())
            <x-empty-state icon="search" title="Tidak ada pengguna yang cocok" body="Coba kata kunci lain atau hapus filter." />
        @else
        <div class="overflow-x-auto">
            <table class="data-table min-w-[820px]">
                <thead>
                    <tr>
                        <th scope="col">Nama</th>
                        <th scope="col">Role</th>
                        <th scope="col">Status</th>
                        <th scope="col">Login terakhir</th>
                        <th scope="col"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $u)
                        @php $self = $u->is(auth()->user()); @endphp
                        <tr @class(['text-ink-muted' => ! $u->is_active])>
                            <td>
                                <p class="font-semibold text-ink">{{ $u->name }} @if ($self)<span class="text-sm font-normal text-ink-muted">(Anda)</span>@endif</p>
                                <p class="text-sm text-ink-muted">{{ $u->email }}</p>
                            </td>
                            <td>{{ $roleLabels[$u->role?->role_name ?? ''] ?? 'Tanpa role' }}</td>
                            <td><x-status :value="$u->is_active ? 'active' : 'inactive'" /></td>
                            <td class="num text-sm">{{ $u->last_login_at ? Format::dateTime($u->last_login_at->format('Y-m-d H:i')) : 'Belum pernah' }}</td>
                            <td>
                                <div class="flex justify-end gap-1">
                                    <x-btn size="sm" variant="ghost" icon="pencil"
                                           x-on:click="$dispatch('user-form', { mode: 'edit', user: {{ \Illuminate\Support\Js::from(['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role_id' => $u->role_id, 'self' => $self]) }} })">
                                        Ubah<span class="sr-only"> {{ $u->name }}</span>
                                    </x-btn>
                                    @unless ($self)
                                        <form method="POST" action="{{ route('admin.users.reset', $u) }}"
                                              x-data @submit="if (!confirm({{ \Illuminate\Support\Js::from('Reset password ' . $u->name . '? Sesi aktifnya akan dikeluarkan.') }})) $event.preventDefault()">
                                            @csrf
                                            <x-btn type="submit" size="sm" variant="ghost">Reset password<span class="sr-only"> {{ $u->name }}</span></x-btn>
                                        </form>
                                        <form method="POST" action="{{ route('admin.users.toggle', $u) }}"
                                              x-data @submit="if (!confirm({{ \Illuminate\Support\Js::from(($u->is_active ? 'Nonaktifkan ' : 'Aktifkan ') . $u->name . '?') }})) $event.preventDefault()">
                                            @csrf
                                            <x-btn type="submit" size="sm" :variant="$u->is_active ? 'danger' : 'secondary'">
                                                {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}<span class="sr-only"> {{ $u->name }}</span>
                                            </x-btn>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $users->links() }}
        @endif
    </x-panel>
    <p class="mt-3 text-sm text-ink-muted">Akun dinonaktifkan, bukan dihapus, supaya jejak penilaian dan keputusan tetap utuh.</p>

    {{-- Dialog tambah / ubah pengguna --}}
    <div x-data="{
            open: {{ $reopen ? 'true' : 'false' }},
            mode: {{ \Illuminate\Support\Js::from($editing ? 'edit' : 'create') }},
            f: {{ \Illuminate\Support\Js::from($reopen ? ['id' => $editing, 'name' => old('name'), 'email' => old('email'), 'role_id' => old('role_id')] : ['role_id' => '']) }},
            base: {{ \Illuminate\Support\Js::from(url('/admin/pengguna')) }},
            show(d) { this.mode = d.mode; this.f = d.user ? { ...d.user } : { role_id: '' }; this.open = true; this.$nextTick(() => this.$refs.name.focus()) },
         }"
         @user-form.window="show($event.detail)" @keydown.escape.window="open = false">
        <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-40 grid place-items-center bg-ink/40 p-4" @click.self="open = false">
            <form method="POST" :action="mode === 'edit' ? base + '/' + f.id : base" x-show="open" x-transition.scale.95 novalidate
                  class="w-full max-w-lg rounded-panel bg-surface shadow-[0_16px_48px_rgba(26,29,36,0.2)]"
                  role="dialog" aria-modal="true" aria-labelledby="user-title">
                @csrf
                <input type="hidden" name="_method" :value="mode === 'edit' ? 'PUT' : 'POST'">
                <input type="hidden" name="_editing" :value="mode === 'edit' ? f.id : ''">
                <header class="border-b border-line px-6 py-4">
                    <h2 id="user-title" class="text-md font-bold" x-text="mode === 'edit' ? 'Ubah pengguna' : 'Tambah pengguna'"></h2>
                    <p class="mt-0.5 text-sm text-ink-muted" x-show="mode === 'create'">Sistem membuat password sementara yang ditampilkan sekali setelah disimpan.</p>
                </header>
                <div class="space-y-5 px-6 py-5">
                    <div>
                        <label for="u-name" class="field-label">Nama</label>
                        <input id="u-name" x-ref="name" name="name" x-model="f.name" required maxlength="120" autocomplete="off" class="field-input">
                        @error('name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="u-email" class="field-label">Email kantor</label>
                        <input id="u-email" type="email" name="email" x-model="f.email" required maxlength="255" autocomplete="off" class="field-input">
                        @error('email')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <fieldset :disabled="f.self">
                        <legend class="field-label">Role</legend>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($roles as $role)
                                <label class="flex cursor-pointer items-center gap-2.5 rounded-ctl border border-line-strong px-3 py-2.5 has-[:checked]:border-brand has-[:checked]:bg-brand-soft">
                                    <input type="radio" name="role_id" value="{{ $role->id }}" x-model="f.role_id" required class="text-brand focus:ring-brand">
                                    {{ $roleLabels[$role->role_name] ?? $role->role_name }}
                                </label>
                            @endforeach
                        </div>
                        <p class="field-help" x-show="f.self">Role akun sendiri tidak bisa diubah.</p>
                        @error('role_id')<p class="field-error">{{ $message }}</p>@enderror
                    </fieldset>
                    <input type="hidden" name="role_id" :value="f.role_id" x-bind:disabled="!f.self">
                </div>
                <footer class="flex justify-end gap-2 border-t border-line px-6 py-4">
                    <x-btn variant="secondary" x-on:click="open = false">Batal</x-btn>
                    <x-btn type="submit"><span x-text="mode === 'edit' ? 'Simpan perubahan' : 'Tambah pengguna'"></span></x-btn>
                </footer>
            </form>
        </div>
    </div>
</x-app-layout>
