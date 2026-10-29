@props(['title', 'subtitle' => null, 'back' => null])
@php
    $user = auth()->user();
    $role = $user->roleName();
    $menu = \App\Support\Navigation::for($role);
    $period = \App\Queries\PromotionData::currentPeriod();
    $roleLabel = \App\Queries\PromotionData::roleLabels()[$role] ?? $role;
    $initials = collect(explode(' ', $user->name))->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — TalentRank</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body x-data="{ nav: false }" @keydown.escape.window="nav = false">
<a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-ctl focus:bg-surface focus:px-4 focus:py-2">Lewati ke konten</a>

<div class="min-h-screen lg:pl-64">
    {{-- Sidebar: tetap di desktop, drawer di layar kecil --}}
    <div x-show="nav" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-ink/40 lg:hidden" @click="nav = false"></div>

    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-line bg-surface transition-transform duration-200 ease-out lg:translate-x-0"
           :class="nav && '!translate-x-0'"
           aria-label="Navigasi utama">
        <div class="flex h-16 items-center gap-2.5 px-5">
            <x-logo />
            <div class="leading-tight">
                <p class="text-md font-extrabold tracking-title text-ink">TalentRank</p>
                <p class="text-xs text-ink-muted">SPK promosi jabatan</p>
            </div>
            <button type="button" class="ml-auto grid size-9 place-items-center rounded-ctl text-ink-muted hover:bg-canvas lg:hidden" @click="nav = false" aria-label="Tutup menu">
                <x-icon name="x" />
            </button>
        </div>

        <div class="mx-3 mb-2 rounded-ctl bg-canvas px-3 py-2.5">
            @if ($period)
                <p class="text-xs text-ink-muted">{{ $period['status'] === 'ongoing' ? 'Periode berjalan' : 'Periode terakhir' }}</p>
                <p class="flex items-center justify-between gap-2 text-sm font-semibold text-ink">
                    {{ $period['name'] }}
                    <x-status :value="$period['status']" class="!px-1.5 !py-0 !text-xs" />
                </p>
            @else
                <p class="text-xs text-ink-muted">Periode</p>
                <p class="text-sm font-semibold text-ink">Belum ada periode</p>
            @endif
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-2">
            <ul class="space-y-0.5">
                @foreach ($menu as $item)
                    @php $active = request()->routeIs($item['match'] ?? $item['route']); @endphp
                    <li>
                        <a href="{{ route($item['route']) }}"
                           @class([
                               'relative flex h-10 items-center gap-3 rounded-ctl px-3 text-base transition-colors duration-150',
                               'bg-brand-soft font-semibold text-brand before:absolute before:-left-3 before:top-2 before:h-6 before:w-[3px] before:rounded-r before:bg-brand' => $active,
                               'text-ink-muted hover:bg-canvas hover:text-ink' => ! $active,
                           ])
                           @if ($active) aria-current="page" @endif>
                            <x-icon :name="$item['icon']" />
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="border-t border-line p-3">
            <div class="flex items-center gap-3 rounded-ctl px-2 py-2">
                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-ink text-sm font-bold text-white" aria-hidden="true">{{ $initials }}</span>
                <div class="min-w-0 flex-1 leading-tight">
                    <p class="truncate text-sm font-semibold text-ink">{{ $user->name }}</p>
                    <p class="text-xs text-ink-muted">{{ $roleLabel }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="grid size-9 place-items-center rounded-ctl text-ink-muted transition-colors hover:bg-bad-bg hover:text-bad" aria-label="Keluar" title="Keluar">
                        <x-icon name="log-out" />
                    </button>
                </form>
            </div>
            <a href="{{ route('account.password') }}" @class(['mt-1 flex items-center gap-2 rounded-ctl px-2 py-1.5 text-sm hover:bg-canvas hover:text-ink', 'font-semibold text-brand' => request()->routeIs('account.*'), 'text-ink-muted' => ! request()->routeIs('account.*')])>
                <x-icon name="lock" class="size-4" /> Ganti password
            </a>
        </div>
    </aside>

    {{-- Bar atas khusus layar kecil --}}
    <header class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-line bg-surface px-4 lg:hidden">
        <button type="button" class="grid size-10 place-items-center rounded-ctl text-ink hover:bg-canvas" @click="nav = true" aria-label="Buka menu" aria-controls="sidebar" :aria-expanded="nav">
            <x-icon name="menu" />
        </button>
        <x-logo class="size-7" />
        <span class="font-extrabold tracking-title">TalentRank</span>
    </header>

    <main id="konten" class="mx-auto max-w-[1200px] px-4 pb-16 pt-6 sm:px-6 lg:px-10 lg:pt-10">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                @if ($back)
                    <a href="{{ $back['url'] }}" class="mb-2 inline-flex items-center gap-1 text-sm font-semibold text-ink-muted hover:text-ink">
                        <x-icon name="chevron-left" class="size-4" /> {{ $back['label'] }}
                    </a>
                @endif
                <h1 class="text-xl font-bold tracking-title text-ink">{{ $title }}</h1>
                @if ($subtitle)<p class="mt-1 max-w-2xl text-ink-muted">{{ $subtitle }}</p>@endif
            </div>
            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>

        {{ $slot }}
    </main>
</div>

@include('partials.toast')
</body>
</html>
