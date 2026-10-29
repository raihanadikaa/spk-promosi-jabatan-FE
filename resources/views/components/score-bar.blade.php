@props(['parts', 'height' => 'h-2'])
{{-- Bar komposisi skor SAW: tiap segmen = kontribusi wj·rij satu kriteria. Skala penuh = 1.0 --}}
@php
    $colors = ['C1' => 'bg-c1', 'C2' => 'bg-c2', 'C3' => 'bg-c3', 'C4' => 'bg-c4', 'C5' => 'bg-c5'];
    $names = \App\Queries\PromotionData::criteriaNames();
    $total = array_sum($parts);
    $summary = collect($parts)->map(fn ($v, $k) => $names[$k] . ' ' . number_format($v, 3, ',', '.'))->implode(', ');
@endphp
<div {{ $attributes->merge(['class' => "flex w-full gap-px overflow-hidden rounded-full bg-line $height"]) }}
     role="img" aria-label="Skor {{ number_format($total, 3, ',', '.') }}. {{ $summary }}">
    @foreach ($parts as $code => $value)
        <span class="{{ $colors[$code] }} h-full" style="width: {{ $value * 100 }}%" title="{{ $names[$code] }}: {{ number_format($value, 4, ',', '.') }}"></span>
    @endforeach
</div>
