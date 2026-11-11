@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center border-b-2 border-brand px-1 pt-1 text-sm font-semibold leading-5 text-brand focus:outline-none focus:border-brand transition duration-150 ease-in-out'
            : 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium leading-5 text-ink-muted hover:border-line-strong hover:text-ink focus:outline-none focus:border-line-strong focus:text-ink transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
