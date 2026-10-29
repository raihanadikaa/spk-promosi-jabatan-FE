@php use App\Support\Format; @endphp

<x-app-layout title="Pengguna & role" subtitle="Akun yang bisa masuk ke TalentRank beserta hak aksesnya.">
    <x-slot:actions>
        <x-btn icon="plus" x-on:click="$dispatch('open-user-form')">Tambah pengguna</x-btn>
    </x-slot:actions>

    <x-panel flush>
        <div class="overflow-x-auto">
            <table class="data-table min-w-[760px]">
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
                        <tr @class(['text-ink-muted' => ! $u['active']])>
                            <td>
                                <p class="font-semibold text-ink">{{ $u['name'] }}</p>
                                <p class="text-sm text-ink-muted">{{ $u['email'] }}</p>
                            </td>
                            <td>{{ $roles[$u['role']] }}</td>
                            <td><x-status :value="$u['active'] ? 'active' : 'inactive'" /></td>
                            <td class="num text-sm">{{ Format::dateTime($u['last_login']) }}</td>
                            <td>
                                <div class="flex justify-end gap-1">
                                    <form method="POST" action="{{ route('demo.action') }}">
                                        @csrf
                                        <input type="hidden" name="action" value="user.reset">
                                        <x-btn type="submit" size="sm" variant="ghost">Reset password<span class="sr-only"> {{ $u['name'] }}</span></x-btn>
                                    </form>
                                    @if ($u['email'] !== auth()->user()->email)
                                        <form method="POST" action="{{ route('demo.action') }}">
                                            @csrf
                                            <input type="hidden" name="action" value="user.toggle">
                                            <x-btn type="submit" size="sm" :variant="$u['active'] ? 'danger' : 'secondary'">
                                                {{ $u['active'] ? 'Nonaktifkan' : 'Aktifkan' }}<span class="sr-only"> {{ $u['name'] }}</span>
                                            </x-btn>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-panel>
    <p class="mt-3 text-sm text-ink-muted">Akun dinonaktifkan, bukan dihapus, supaya jejak penilaian dan keputusan tetap utuh.</p>

    {{-- Dialog tambah pengguna --}}
    <div x-data="{ open: false }" @open-user-form.window="open = true; $nextTick(() => $refs.name.focus())" @keydown.escape.window="open = false">
        <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-40 grid place-items-center bg-ink/40 p-4" @click.self="open = false">
            <form method="POST" action="{{ route('demo.action') }}" x-show="open" x-transition.scale.95
                  class="w-full max-w-lg rounded-panel bg-surface shadow-[0_16px_48px_rgba(26,29,36,0.2)]"
                  role="dialog" aria-modal="true" aria-labelledby="user-title">
                @csrf
                <input type="hidden" name="action" value="user.create">
                <header class="border-b border-line px-6 py-4">
                    <h2 id="user-title" class="text-md font-bold">Tambah pengguna</h2>
                    <p class="mt-0.5 text-sm text-ink-muted">Link untuk membuat password dikirim ke email pengguna. Admin tidak menentukan password.</p>
                </header>
                <div class="space-y-5 px-6 py-5">
                    <div>
                        <label for="u-name" class="field-label">Nama</label>
                        <input id="u-name" x-ref="name" name="name" required maxlength="120" autocomplete="off" class="field-input">
                    </div>
                    <div>
                        <label for="u-email" class="field-label">Email kantor</label>
                        <input id="u-email" type="email" name="email" required maxlength="255" autocomplete="off" class="field-input">
                    </div>
                    <fieldset>
                        <legend class="field-label">Role</legend>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($roles as $value => $label)
                                <label class="flex cursor-pointer items-center gap-2.5 rounded-ctl border border-line-strong px-3 py-2.5 has-[:checked]:border-brand has-[:checked]:bg-brand-soft">
                                    <input type="radio" name="role" value="{{ $value }}" required class="text-brand focus:ring-brand" @checked($value === 'karyawan')>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                </div>
                <footer class="flex justify-end gap-2 border-t border-line px-6 py-4">
                    <x-btn variant="secondary" x-on:click="open = false">Batal</x-btn>
                    <x-btn type="submit">Tambah pengguna</x-btn>
                </footer>
            </form>
        </div>
    </div>
</x-app-layout>
