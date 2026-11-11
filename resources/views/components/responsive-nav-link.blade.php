@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full border-l-4 border-brand bg-brand-soft py-2 ps-3 pe-4 text-start text-base font-semibold text-brand focus:outline-none focus:text-brand-hover focus:bg-brand-soft focus:border-brand transition duration-150 ease-in-out'
            : 'block w-full border-l-4 border-transparent py-2 ps-3 pe-4 text-start text-base font-medium text-ink-muted hover:border-line-strong hover:bg-canvas hover:text-ink focus:outline-none focus:text-ink focus:bg-canvas focus:border-line-strong transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
