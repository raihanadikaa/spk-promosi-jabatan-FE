@props(['items'])
<dl {{ $attributes->class([
    'grid grid-cols-1 gap-4',
    'sm:grid-cols-2' => count($items) >= 2,
    'xl:grid-cols-2' => count($items) === 2,
    'xl:grid-cols-3' => count($items) === 3,
    'xl:grid-cols-4' => count($items) >= 4,
]) }}>
    @foreach ($items as $item)
        <div class="min-w-0 rounded-panel border border-line bg-surface px-5 py-4 shadow-sm">
            <dt class="text-sm text-ink-muted">{{ $item['label'] }}</dt>
            <dd class="mt-1 flex flex-wrap items-baseline gap-x-2">
                <span class="num text-xl font-bold tracking-title text-ink">{{ $item['value'] }}</span>
                @isset($item['hint'])<span class="text-sm text-ink-muted">{{ $item['hint'] }}</span>@endisset
            </dd>
        </div>
    @endforeach
</dl>
