@php
    $criteria = \App\Support\DemoData::criteria();
    $colors = ['C1' => 'bg-c1', 'C2' => 'bg-c2', 'C3' => 'bg-c3', 'C4' => 'bg-c4', 'C5' => 'bg-c5'];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — TalentRank</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface">
<div class="grid min-h-screen lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)]">

    <main class="flex flex-col px-6 py-8 sm:px-12">
        <div class="flex items-center gap-2.5">
            <x-logo />
            <span class="text-md font-extrabold tracking-title">TalentRank</span>
        </div>

        <div class="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center py-12">
            <h1 class="text-xl font-bold tracking-title">Masuk ke TalentRank</h1>
            <p class="mt-1 text-ink-muted">Gunakan email kantor yang terdaftar di HRD.</p>

            @if (session('error'))
                <div class="mt-6 flex gap-2.5 rounded-ctl bg-warn-bg px-3.5 py-3 text-sm text-warn" role="alert">
                    <x-icon name="clock" class="mt-px size-4" />
                    <p>{{ session('error') }}</p>
                </div>
            @elseif (session('success'))
                <div class="mt-6 flex gap-2.5 rounded-ctl bg-ok-bg px-3.5 py-3 text-sm text-ok" role="status">
                    <x-icon name="check-circle" class="mt-px size-4" />
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('login.attempt') }}" class="mt-8 space-y-5" novalidate
                  x-data="{ show: false, busy: false }" @submit="busy = true">
                @csrf

                <div>
                    <label for="email" class="field-label">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                           autocomplete="username" required autofocus
                           @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                           class="field-input @error('email') !border-bad @enderror">
                    @error('email')
                        <p id="email-error" class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="field-label">Password</label>
                    <div class="relative">
                        <input :type="show ? 'text' : 'password'" type="password" name="password" id="password"
                               autocomplete="current-password" required
                               @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                               class="field-input pr-11 @error('password') !border-bad @enderror">
                        <button type="button" @click="show = !show"
                                class="absolute inset-y-0 right-0 grid w-11 place-items-center rounded-r-ctl text-ink-muted hover:text-ink"
                                :aria-pressed="show.toString()" aria-label="Tampilkan password">
                            <x-icon name="eye" x-show="!show" />
                            <x-icon name="eye-off" x-show="show" x-cloak />
                        </button>
                    </div>
                    @error('password')
                        <p id="password-error" class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-2.5 text-base text-ink">
                    <input type="checkbox" name="remember" class="size-4 rounded border-line-strong text-brand focus:ring-brand">
                    Ingat saya di perangkat ini
                </label>

                <button type="submit" :disabled="busy"
                        class="flex h-11 w-full items-center justify-center gap-2 rounded-ctl bg-brand font-semibold text-white transition-colors duration-150 hover:bg-brand-hover active:translate-y-px disabled:opacity-70">
                    <svg x-show="busy" x-cloak class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                    <span x-text="busy ? 'Memeriksa…' : 'Masuk'">Masuk</span>
                </button>

                <p class="text-sm text-ink-muted">Lupa password? Hubungi admin sistem untuk reset akun.</p>
            </form>

            @if (app()->environment('local'))
                <details class="mt-10 rounded-ctl border border-dashed border-line-strong px-4 py-3 text-sm">
                    <summary class="cursor-pointer font-semibold text-ink-muted">Akun demo (hanya tampil di lokal)</summary>
                    <ul class="mt-2 space-y-1 text-ink-muted">
                        @foreach (['admin' => 'Admin', 'hrd' => 'HRD', 'atasan' => 'Atasan', 'direktur' => 'Direktur', 'karyawan' => 'Karyawan'] as $mail => $label)
                            <li class="flex justify-between gap-4"><span>{{ $label }}</span><span class="num text-ink">{{ $mail }}@talentrank.test</span></li>
                        @endforeach
                        <li class="pt-1">Password semua akun: <span class="font-semibold text-ink">password</span></li>
                    </ul>
                </details>
            @endif
        </div>

        <p class="text-sm text-ink-faint">© {{ date('Y') }} Kelompok 2 · Sistem Informasi, Politeknik Negeri Subang</p>
    </main>

    {{-- Panel kanan: memperlihatkan cara skor dibentuk, bukan ilustrasi dekoratif --}}
    <aside class="relative hidden overflow-hidden bg-brand text-white lg:order-first lg:flex lg:flex-col lg:justify-center lg:px-16 xl:px-24" aria-label="Cara skor dihitung">
        <div class="max-w-lg">
            <p class="text-md font-semibold text-white/70">Metode Simple Additive Weighting</p>
            <h2 class="mt-3 text-2xl font-bold leading-tight tracking-title">
                Promosi diputuskan dari lima kriteria yang sama untuk semua karyawan.
            </h2>

            <div class="mt-12">
                <div class="flex h-4 w-full gap-1 overflow-hidden rounded-full" aria-hidden="true">
                    @foreach ($criteria as $c)
                        <span class="{{ $colors[$c['code']] }} h-full" style="width: {{ $c['weight'] }}%"></span>
                    @endforeach
                </div>
                <dl class="mt-6 divide-y divide-white/15 border-y border-white/15">
                    @foreach ($criteria as $c)
                        <div class="flex items-center gap-3 py-3">
                            <span class="{{ $colors[$c['code']] }} size-3 shrink-0 rounded-[3px] ring-1 ring-white/30" aria-hidden="true"></span>
                            <dt class="flex-1 font-medium">{{ $c['name'] }}</dt>
                            <dd class="num font-semibold">{{ $c['weight'] }}%</dd>
                        </div>
                    @endforeach
                </dl>
                <p class="mt-6 text-white/75">
                    Atasan mengisi nilai, sistem menormalisasi dan menjumlahkan sesuai bobot,
                    lalu direktur memutuskan dari rincian yang sama.
                </p>
            </div>
        </div>
    </aside>
</div>
</body>
</html>
