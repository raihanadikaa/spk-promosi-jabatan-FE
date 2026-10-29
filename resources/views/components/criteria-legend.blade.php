{{-- Legenda warna kriteria; tampilkan di dekat setiap bar komposisi skor. --}}
<ul {{ $attributes->merge(['class' => 'flex flex-wrap gap-x-4 gap-y-1.5 text-sm text-ink-muted']) }}>
    @foreach (\App\Support\DemoData::criteria() as $c)
        <li class="flex items-center gap-1.5">
            <x-criteria-dot :code="$c['code']" />
            {{ $c['name'] }} <span class="num text-ink-faint">{{ $c['weight'] }}%</span>
        </li>
    @endforeach
</ul>
