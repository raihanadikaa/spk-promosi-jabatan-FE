@props(['code'])
@php
    $colors = ['C1' => 'bg-c1', 'C2' => 'bg-c2', 'C3' => 'bg-c3', 'C4' => 'bg-c4', 'C5' => 'bg-c5'];
@endphp
<span {{ $attributes->merge(['class' => 'inline-block size-2.5 shrink-0 rounded-[3px] ' . ($colors[$code] ?? 'bg-line')]) }} aria-hidden="true"></span>
