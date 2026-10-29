@props([
    'search' => null,          // nilai pencarian saat ini
    'placeholder' => 'Cari',
    'filters' => [],           // [['name' => 'status', 'label' => 'Status', 'options' => [value => label], 'value' => ...]]
    'toggles' => [],           // [['name' => 'nonaktif', 'label' => 'Tampilkan nonaktif', 'checked' => bool]]
    'total' => null,           // jumlah hasil (opsional)
])
@php
    $active = filled($search) || collect($filters)->contains(fn ($f) => filled($f['value'] ?? null)) || collect($toggles)->contains('checked', true);
@endphp
{{-- Pencarian + filter lewat GET: hasilnya bisa di-bookmark dan tombol Back berfungsi. --}}
<form method="GET" class="mb-4 flex flex-wrap items-center gap-3" role="search" x-data>
    <div class="relative w-full sm:w-72">
        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-ink-faint" />
        <label for="filter-q" class="sr-only">{{ $placeholder }}</label>
        <input id="filter-q" type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="{{ $placeholder }}" class="field-input pl-9">
    </div>

    @foreach ($filters as $f)
        <label for="filter-{{ $f['name'] }}" class="sr-only">{{ $f['label'] }}</label>
        <select id="filter-{{ $f['name'] }}" name="{{ $f['name'] }}" class="field-input w-auto pr-9" x-on:change="$el.form.requestSubmit()">
            <option value="">{{ $f['label'] }}: semua</option>
            @foreach ($f['options'] as $value => $label)
                <option value="{{ $value }}" @selected((string) ($f['value'] ?? '') === (string) $value)>{{ $label }}</option>
            @endforeach
        </select>
    @endforeach

    @foreach ($toggles as $t)
        <label class="flex items-center gap-2 text-sm text-ink-muted">
            <input type="checkbox" name="{{ $t['name'] }}" value="1" @checked($t['checked']) x-on:change="$el.form.requestSubmit()"
                   class="size-4 rounded border-line-strong text-brand focus:ring-brand">
            {{ $t['label'] }}
        </label>
    @endforeach

    <x-btn type="submit" variant="secondary">Cari</x-btn>
    @if ($active)
        <a href="{{ url()->current() }}" class="link text-sm">Hapus filter</a>
    @endif
    @if ($total !== null)
        <p class="num text-sm text-ink-muted sm:ml-auto">{{ $total }} hasil</p>
    @endif
</form>
