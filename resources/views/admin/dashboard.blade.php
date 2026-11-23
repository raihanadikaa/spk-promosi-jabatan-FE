@php
    use App\Support\Format;

@endphp

<x-app-layout title="Administrasi sistem" subtitle="Admin mengelola akun dan hak akses. Admin tidak ikut menilai maupun memutuskan promosi.">
    <x-slot:actions>
        <x-btn :href="route('admin.users')" icon="users">Kelola pengguna</x-btn>
    </x-slot:actions>

    <x-stat-strip :items="[
        ['label' => 'Akun aktif', 'value' => $activeUsers, 'hint' => 'dari ' . $totalUsers],
        ['label' => 'HRD', 'value' => $byRole['hrd'] ?? 0],
        ['label' => 'Atasan', 'value' => $byRole['manager'] ?? 0],
        ['label' => 'Direktur', 'value' => $byRole['direktur'] ?? 0],
    ]" />

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-panel title="Login terakhir" flush>
            @if ($recent->isEmpty())
                <x-empty-state icon="clock" title="Belum ada yang login" />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($recent as $u)
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <div class="min-w-0">
                                <p class="truncate font-semibold">{{ $u->name }}</p>
                                <p class="text-sm text-ink-muted">{{ $roles[$u->role?->role_name ?? ''] ?? 'Tanpa role' }}</p>
                            </div>
                            <p class="num text-sm text-ink-muted">{{ Format::dateTime($u->last_login_at?->format('Y-m-d H:i')) }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-panel>

        <x-panel title="Kebijakan keamanan yang berlaku">
            <ul class="space-y-3">
                @foreach ([
                    ['lock', 'Akun terkunci 5 menit setelah 5 kali gagal login.'],
                    ['clock', 'Sesi berakhir otomatis setelah 30 menit tidak aktif.'],
                    ['users', 'Setiap akun punya satu role; akses halaman dibatasi per role.'],
                    ['x-circle', 'Akun yang dinonaktifkan langsung dikeluarkan dari semua sesi.'],
                    ['eye-off', 'Password disimpan sebagai hash (bcrypt); admin hanya bisa mereset, tidak bisa melihat.'],
                ] as [$icon, $text])
                    <li class="flex gap-3 text-ink"><x-icon :name="$icon" class="mt-0.5 text-ink-muted" /> {{ $text }}</li>
                @endforeach
            </ul>
        </x-panel>
    </div>

    <x-panel class="mt-6" title="Aktivitas terbaru" description="Perubahan data dan login terakhir di sistem." flush>
        <x-slot:actions>
            <a href="{{ route('admin.activity') }}" class="link text-sm">Lihat semua log</a>
        </x-slot:actions>
        @if ($recentActivity->isEmpty())
            <x-empty-state icon="history" title="Belum ada aktivitas tercatat" />
        @else
            <ul class="divide-y divide-line">
                @foreach ($recentActivity as $log)
                    <li class="flex flex-wrap items-start justify-between gap-x-4 gap-y-1 px-5 py-3">
                        <p class="min-w-0 flex-1 text-ink"><span class="font-semibold">{{ $log->user_name ?? 'Sistem' }}</span> <span class="text-ink-muted">·</span> {{ $log->description }}</p>
                        <p class="num shrink-0 text-sm text-ink-muted">{{ Format::dateTime($log->created_at->format('Y-m-d H:i')) }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-panel>
</x-app-layout>
