@props(['icon' => 'info', 'title', 'body' => null])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center px-6 py-12 text-center']) }}>
    <span class="grid size-10 place-items-center rounded-panel bg-canvas text-ink-muted"><x-icon :name="$icon" /></span>
    <p class="mt-3 font-semibold text-ink">{{ $title }}</p>
    @if ($body)<p class="mt-1 max-w-sm text-sm text-ink-muted">{{ $body }}</p>@endif
    @if (! $slot->isEmpty())<div class="mt-4">{{ $slot }}</div>@endif
</div>
