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
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-bold ' . $tones[$tone]]) }}>
    @if ($icon)<x-icon :name="$icon" class="size-3.5" />@endif
    {{ $slot }}
</span>
