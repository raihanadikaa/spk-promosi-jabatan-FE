@props(['tone' => 'neutral', 'icon' => null])
@php
    $tones = [
        'success' => 'bg-ok-bg text-ok',
        'warning' => 'bg-warn-bg text-warn',
        'danger' => 'bg-bad-bg text-bad',
        'neutral' => 'bg-info-bg text-info',
        'brand' => 'bg-brand-soft text-brand',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 whitespace-nowrap rounded-ctl px-2 py-0.5 text-sm font-semibold ' . $tones[$tone]]) }}>
    @if ($icon)<x-icon :name="$icon" class="size-3.5" />@endif
    {{ $slot }}
</span>
