@props(['variant' => 'primary', 'href' => null, 'icon' => null, 'size' => 'md'])
@php
    $base = 'inline-flex select-none items-center justify-center gap-2 whitespace-nowrap rounded-ctl font-semibold transition-colors duration-150 active:translate-y-px disabled:pointer-events-none disabled:opacity-50';
    $sizes = [
        'sm' => 'h-8 px-3 text-sm',
        'md' => 'h-10 px-4 text-base',
    ];
    $variants = [
        'primary' => 'bg-brand text-white hover:bg-brand-hover',
        'secondary' => 'border border-line-strong bg-surface text-ink hover:bg-canvas',
        'ghost' => 'text-ink-muted hover:bg-canvas hover:text-ink',
        'danger' => 'border border-bad/40 bg-surface text-bad hover:bg-bad-bg',
        'success' => 'bg-ok text-white hover:bg-ok/90',
    ];
    $classes = "$base {$sizes[$size]} {$variants[$variant]}";
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-icon :name="$icon" class="size-4" />@endif
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>
        @if ($icon)<x-icon :name="$icon" class="size-4" />@endif
        {{ $slot }}
    </button>
@endif
