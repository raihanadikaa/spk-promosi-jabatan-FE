@props(['label', 'value', 'hint' => null, 'icon' => 'info', 'tone' => 'info'])
{{-- Kartu ringkasan dengan kotak ikon, sama dengan kartu di dashboard HRD. tone: info | ok | warn | bad | brand --}}
@php
    $tones = [
        'info' => 'bg-info-bg text-info',
        'ok' => 'bg-ok-bg text-ok',
        'warn' => 'bg-warn-bg text-warn',
        'bad' => 'bg-bad-bg text-bad',
        'brand' => 'bg-brand-soft text-brand',
    ];
@endphp
<article {{ $attributes->merge(['class' => 'flex min-w-0 items-start gap-4 rounded-panel border border-line bg-surface p-5 shadow-sm']) }}>
    <span class="grid size-11 shrink-0 place-items-center rounded-ctl {{ $tones[$tone] ?? $tones['info'] }}" aria-hidden="true">
        <x-icon :name="$icon" />
    </span>
    <div class="min-w-0">
        <p class="text-sm text-ink-muted">{{ $label }}</p>
        <p class="num mt-1 text-xl font-bold tracking-title text-ink">{{ $value }}</p>
        @if ($hint)<p class="mt-1 text-sm text-ink-muted">{{ $hint }}</p>@endif
    </div>
</article>
