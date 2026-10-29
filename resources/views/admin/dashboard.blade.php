@php
    use App\Support\Format;

    $users = collect($users);
    $active = $users->where('active', true);
    $byRole = $active->countBy('role');
@endphp

<x-app-layout title="Administrasi sistem" subtitle="Admin mengelola akun dan hak akses. Admin tidak ikut menilai maupun melihat skor karyawan.">
    <x-slot:actions>
        <x-btn :href="route('admin.users')" icon="users">Kelola pengguna</x-btn>
    </x-slot:actions>

    <x-stat-strip :items="[
        ['label' => 'Akun aktif', 'value' => $active->count(), 'hint' => 'dari ' . $users->count()],
        ['label' => 'HRD', 'value' => $byRole['hrd'] ?? 0],
        ['label' => 'Atasan', 'value' => $byRole['manager'] ?? 0],
        ['label' => 'Direktur', 'value' => $byRole['direktur'] ?? 0],
    ]" />

    <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
        <x-panel title="Login terakhir" flush>
            <ul class="divide-y divide-line">
                @foreach ($users->sortByDesc('last_login')->take(6) as $u)
                    <li class="flex items-center justify-between gap-3 px-5 py-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold">{{ $u['name'] }}</p>
                            <p class="text-sm text-ink-muted">{{ $roles[$u['role']] }}</p>
                        </div>
                        <p class="num text-sm text-ink-muted">{{ Format::dateTime($u['last_login']) }}</p>
                    </li>
                @endforeach
            </ul>
        </x-panel>

        <x-panel title="Kebijakan keamanan yang berlaku">
            <ul class="space-y-3">
                @foreach ([
                    ['lock', 'Akun terkunci 5 menit setelah 5 kali gagal login.'],
                    ['clock', 'Sesi berakhir otomatis setelah 30 menit tidak aktif.'],
                    ['users', 'Setiap akun hanya punya satu role; akses halaman dibatasi per role.'],
                    ['eye-off', 'Password disimpan dalam bentuk hash (bcrypt), tidak bisa dilihat admin.'],
                ] as [$icon, $text])
                    <li class="flex gap-3 text-ink"><x-icon :name="$icon" class="mt-0.5 text-ink-muted" /> {{ $text }}</li>
                @endforeach
            </ul>
        </x-panel>
    </div>
</x-app-layout>
