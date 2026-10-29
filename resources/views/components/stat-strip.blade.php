@props(['items'])
{{-- Strip ringkasan angka dalam satu panel dengan pemisah garis — bukan kartu-kartu identik. --}}
<dl {{ $attributes->merge(['class' => 'grid grid-cols-2 overflow-hidden rounded-panel border border-line bg-surface lg:grid-cols-4']) }}>
    @foreach ($items as $i => $item)
        <div @class([
            'px-5 py-4',
            'border-t border-line lg:border-t-0' => $i >= 2,
            'border-l border-line' => $i % 2 === 1,
            'lg:border-l' => $i > 0,
        ])>
            <dt class="text-sm text-ink-muted">{{ $item['label'] }}</dt>
            <dd class="mt-1 flex flex-wrap items-baseline gap-x-2">
                <span class="num text-xl font-bold tracking-title text-ink">{{ $item['value'] }}</span>
                @isset($item['hint'])<span class="text-sm text-ink-muted">{{ $item['hint'] }}</span>@endisset
            </dd>
        </div>
    @endforeach
</dl>
