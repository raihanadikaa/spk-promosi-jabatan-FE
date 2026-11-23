@props(['title', 'subtitle' => null, 'back' => null])
@php
    $user = auth()->user();
    $role = $user->roleName();
    $menu = \App\Support\Navigation::for($role);
    $period = \App\Queries\PromotionData::currentPeriod();
    $roleLabel = \App\Queries\PromotionData::roleLabels()[$role] ?? $role;
    $initials = collect(explode(' ', $user->name))->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
    $unreadCount = $user->unreadNotifications()->count();
    $unread = $unreadCount ? $user->unreadNotifications()->latest()->limit(6)->get() : collect();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-pref-url="{{ route('account.preferences') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>TalentRank</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body x-data="{ nav: false }" @keydown.escape.window="nav = false">
<a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-ctl focus:bg-surface focus:px-4 focus:py-2">{{ __('Lewati ke konten') }}</a>

<div class="min-h-screen bg-canvas lg:pl-64">
    {{-- Sidebar: tetap di desktop, drawer di layar kecil --}}
    <div x-show="nav" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-ink/40 lg:hidden" @click="nav = false"></div>

    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-line bg-surface shadow-sm transition-transform duration-200 ease-out lg:translate-x-0"
           :class="nav && '!translate-x-0'"
           aria-label="{{ __('Navigasi utama') }}">
        <div class="flex h-16 items-center gap-2.5 px-5">
            <x-logo data-logo-anchor />
            <div class="leading-tight">
                <p class="text-md font-extrabold tracking-title text-ink" data-word-anchor>TalentRank</p>
                <p class="text-xs text-ink-muted">{{ __('SPK promosi jabatan') }}</p>
            </div>
            <button type="button" class="ml-auto grid size-9 place-items-center rounded-ctl text-ink-muted hover:bg-canvas lg:hidden" @click="nav = false" aria-label="{{ __('Tutup menu') }}">
                <x-icon name="x" />
            </button>
        </div>

        <div class="mx-3 mb-2 rounded-ctl bg-canvas px-3 py-2.5">
            @if ($period)
                <p class="text-xs text-ink-muted">{{ $period['status'] === 'ongoing' ? __('Periode berjalan') : __('Periode terakhir') }}</p>
                <p class="flex items-center justify-between gap-2 text-sm font-semibold text-ink">
                    {{ $period['name'] }}
                    <x-status :value="$period['status']" class="!px-1.5 !py-0 !text-xs" />
                </p>
            @else
                <p class="text-xs text-ink-muted">{{ __('Periode') }}</p>
                <p class="text-sm font-semibold text-ink">{{ __('Belum ada periode') }}</p>
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
                            {{ __($item['label']) }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

    </aside>

    {{-- Bar atas: menu (HP), lalu notifikasi & profil di kanan — pola yang umum di aplikasi. --}}
    <header class="sticky top-0 z-20 flex h-14 items-center gap-2 border-b border-line bg-surface/85 px-4 backdrop-blur sm:px-6 lg:px-10">
        <button type="button" class="-ml-2 grid size-10 place-items-center rounded-ctl text-ink hover:bg-canvas lg:hidden" @click="nav = true" aria-label="{{ __('Buka menu') }}" aria-controls="sidebar" :aria-expanded="nav">
            <x-icon name="menu" />
        </button>
        <a href="{{ $user->dashboardUrl() }}" class="flex items-center gap-2 lg:hidden">
            <x-logo class="size-7" data-logo-anchor />
            <span class="font-extrabold tracking-title" data-word-anchor>TalentRank</span>
        </a>

        <div class="ml-auto flex items-center gap-1">
            <x-theme-toggle compact class="lg:hidden" />

            {{-- Notifikasi: pengingat penilaian, permintaan review ulang, dsb. --}}
            <div class="relative" x-data="{ bell: false }" @click.outside="bell = false" @keydown.escape="bell = false">
                <button type="button" @click="bell = !bell" :aria-expanded="bell" aria-controls="notif-panel"
                        class="relative grid size-10 place-items-center rounded-ctl text-ink-muted transition-colors hover:bg-canvas hover:text-ink"
                        aria-label="{{ __('Notifikasi') }}{{ $unreadCount ? ', ' . __(':n belum dibaca', ['n' => $unreadCount]) : '' }}" title="{{ __('Notifikasi') }}">
                    <x-icon name="bell" @class(['tr-bell' => $unreadCount]) />
                    @if ($unreadCount)
                        <span class="num absolute right-1 top-1 grid min-w-[1.15rem] place-items-center rounded-full bg-bad px-1 text-[10px] font-bold leading-[1.15rem] text-white ring-2 ring-surface" aria-hidden="true">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                    @endif
                </button>
                <div id="notif-panel" x-show="bell" x-cloak
                     x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="opacity-0 -translate-y-1 scale-[.98]"
                     x-transition:leave="transition duration-100 ease-in" x-transition:leave-end="opacity-0 -translate-y-1"
                     class="absolute right-0 top-12 z-50 w-[min(22rem,calc(100vw-2rem))] origin-top-right overflow-hidden rounded-panel border border-line bg-surface shadow-[0_12px_32px_rgba(15,23,42,0.18)]">
                    <div class="flex items-center justify-between border-b border-line px-4 py-3">
                        <p class="font-bold text-ink">{{ __('Notifikasi') }}</p>
                        @if ($unreadCount)<span class="num rounded-full bg-bad-bg px-2 py-0.5 text-xs font-bold text-bad">{{ __(':n baru', ['n' => $unreadCount]) }}</span>@endif
                    </div>
                    <div class="max-h-[60vh] overflow-y-auto">
                        @forelse ($unread as $n)
                            <a href="{{ route('notifications.open', $n->id) }}" class="flex gap-3 border-b border-line px-4 py-3 last:border-b-0 hover:bg-canvas">
                                <span class="mt-1.5 size-2 shrink-0 rounded-full bg-brand" aria-hidden="true"></span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-ink">{{ \App\Support\NotificationText::title($n) }}</span>
                                    <span class="mt-0.5 line-clamp-3 block text-xs text-ink-muted">{{ \App\Support\NotificationText::body($n) }}</span>
                                    <span class="mt-1 block text-xs text-ink-faint">{{ $n->created_at->diffForHumans() }}</span>
                                </span>
                            </a>
                        @empty
                            <div class="px-4 py-8 text-center">
                                <x-icon name="check-circle" class="mx-auto size-6 text-ok" />
                                <p class="mt-2 text-sm text-ink-muted">{{ __('Tidak ada notifikasi baru.') }}</p>
                            </div>
                        @endforelse
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-line bg-canvas px-4 py-2.5">
                        <a href="{{ route('notifications.index') }}" class="text-xs font-semibold text-brand hover:underline">{{ __('Lihat semua notifikasi') }}</a>
                        @if ($unreadCount)
                            <form method="POST" action="{{ route('notifications.read-all') }}">
                                @csrf
                                <button type="submit" class="text-xs font-semibold text-ink-muted hover:text-ink hover:underline">{{ __('Tandai semua dibaca') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Menu profil --}}
            <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="profile-menu"
                        class="flex items-center gap-2 rounded-full p-0.5 transition-colors hover:bg-canvas sm:pr-2" title="{{ __('Akun saya') }}">
                    <x-avatar :user="$user" size="size-8" text="text-xs" />
                    <span class="hidden max-w-[10rem] truncate text-sm font-semibold text-ink sm:block">{{ strtok($user->name, ' ') }}</span>
                    <x-icon name="chevron-down" class="hidden size-4 text-ink-faint transition-transform sm:block" x-bind:class="open && 'rotate-180'" />
                    <span class="sr-only">{{ __('Buka menu akun') }}</span>
                </button>
                <div id="profile-menu" x-show="open" x-cloak
                     x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="opacity-0 -translate-y-1 scale-[.98]"
                     x-transition:leave="transition duration-100 ease-in" x-transition:leave-end="opacity-0 -translate-y-1"
                     class="absolute right-0 top-12 z-50 w-64 origin-top-right overflow-hidden rounded-panel border border-line bg-surface shadow-[0_12px_32px_rgba(15,23,42,0.18)]">
                    <div class="flex items-center gap-3 border-b border-line px-4 py-3.5">
                        <x-avatar :user="$user" size="size-11" />
                        <div class="min-w-0 leading-tight">
                            <p class="truncate font-semibold text-ink">{{ $user->name }}</p>
                            <p class="truncate text-xs text-ink-muted">{{ $user->email }}</p>
                            <p class="mt-1 text-xs font-semibold text-brand">{{ __('role:' . $role) }}</p>
                        </div>
                    </div>
                    <nav class="p-1.5 text-sm" aria-label="Menu akun">
                        @foreach ([['profil', 'user', __('Profil saya')], ['keamanan', 'lock', __('Password & sesi')], ['tampilan', 'palette', __('Tampilan')]] as [$t, $icon, $label])
                            <a href="{{ route('account.settings', ['tab' => $t]) }}" class="flex items-center gap-2.5 rounded-ctl px-2.5 py-2 text-ink hover:bg-canvas">
                                <x-icon :name="$icon" class="size-4 text-ink-muted" /> {{ $label }}
                            </a>
                        @endforeach
                    </nav>
                    <div class="border-t border-line p-1.5 text-sm">
                        <a href="{{ route('notifications.index') }}" class="flex items-center gap-2.5 rounded-ctl px-2.5 py-2 text-ink hover:bg-canvas">
                            <x-icon name="bell" class="size-4 text-ink-muted" /> {{ __('Notifikasi') }}
                            @if ($unreadCount)<span class="num ml-auto rounded-full bg-bad px-1.5 text-[10px] font-bold leading-4 text-white">{{ $unreadCount }}</span>@endif
                        </a>
                        <x-theme-toggle class="!px-2.5 !py-2 !text-ink [&_svg]:text-ink-muted" />
                        <div class="flex items-center gap-2.5 px-2.5 py-1.5 text-ink">
                            <x-icon name="globe" class="size-4 text-ink-muted" /> {{ __('Bahasa') }}
                            @include('partials.locale-switch', ['class' => 'ml-auto'])
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="border-t border-line p-1.5" data-logout>
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2.5 rounded-ctl px-2.5 py-2 text-sm font-semibold text-bad hover:bg-bad-bg">
                            <x-icon name="log-out" class="size-4" /> {{ __('Keluar') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main id="konten" class="mx-auto max-w-[98rem] px-4 pb-16 pt-6 sm:px-6 lg:px-10 lg:pt-8">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                @if ($back)
                    <a href="{{ $back['url'] }}" class="mb-2 inline-flex items-center gap-1 text-sm font-semibold text-ink-muted hover:text-ink">
                        <x-icon name="chevron-left" class="size-4" /> {{ $back['label'] }}
                    </a>
                @endif
                <div class="mb-1.5 flex items-center gap-1.5 text-xs text-ink-faint">
                    <span>{{ __('SPK Promosi') }}</span>
                    <x-icon name="chevron-right" class="size-3" />
                    <span class="font-semibold text-brand">{{ __($title) }}</span>
                </div>
                <h1 class="text-xl font-bold tracking-title text-ink">{{ __($title) }}</h1>
                @if ($subtitle)<p class="mt-1 max-w-2xl text-ink-muted">{{ __($subtitle) }}</p>@endif
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
