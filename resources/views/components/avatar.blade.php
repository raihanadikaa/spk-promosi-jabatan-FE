@props(['user', 'size' => 'size-9', 'text' => 'text-sm'])
{{-- Avatar pengguna: foto → avatar siap pakai → inisial nama di atas warna khas orang itu. --}}
@php
    use App\Support\Avatars;

    $name = $user?->name ?? '?';
    $photo = $user?->avatarUrl();
    $preset = $user && Avatars::exists($user->avatar_preset) ? Avatars::PRESETS[$user->avatar_preset] : null;
    [$from, $to] = $preset ? [$preset['from'], $preset['to']] : Avatars::initialColors($name);
    $gid = 'av' . substr(md5($name . ($preset['motif'] ?? '') . $size), 0, 8);
@endphp
<span {{ $attributes->merge(['class' => "relative inline-grid shrink-0 place-items-center overflow-hidden rounded-full $size"]) }}>
    @if ($photo)
        <img src="{{ $photo }}" alt="" class="size-full object-cover" loading="lazy" decoding="async" width="128" height="128">
    @else
        <svg viewBox="0 0 64 64" class="absolute inset-0 size-full" aria-hidden="true">
            <defs>
                <linearGradient id="{{ $gid }}" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="{{ $from }}"/><stop offset="1" stop-color="{{ $to }}"/>
                </linearGradient>
            </defs>
            <rect width="64" height="64" fill="url(#{{ $gid }})"/>
            @if ($preset){!! Avatars::motif($preset['motif']) !!}@endif
        </svg>
        @unless ($preset)
            <span class="relative font-bold tracking-wide text-white {{ $text }}" aria-hidden="true">{{ Avatars::initials($name) }}</span>
        @endunless
    @endif
</span>
