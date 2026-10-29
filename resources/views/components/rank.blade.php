@props(['value'])
@php $top = $value <= \App\Queries\PromotionData::RECOMMENDED_COUNT; @endphp
<span {{ $attributes->merge(['class' => 'num inline-grid size-7 shrink-0 place-items-center rounded-ctl text-sm font-bold ' . ($top ? 'bg-brand text-white' : 'bg-canvas text-ink-muted')]) }}>{{ $value }}</span>
