@props(['value'])
{{-- Pemetaan status data → badge. Teks selalu tampil, tidak hanya warna. --}}
@php
    $map = [
        'submitted' => ['success', 'Terkirim'],
        'draft' => ['warning', 'Draft'],
        'pending' => ['neutral', 'Belum dinilai'],
        'approved' => ['success', 'Disetujui'],
        'rejected' => ['danger', 'Ditolak'],
        'revision' => ['warning', 'Review ulang HRD'],
        'waiting' => ['warning', 'Menunggu keputusan'],
        'ongoing' => ['brand', 'Berlangsung'],
        'closed' => ['neutral', 'Ditutup'],
        'draft_period' => ['neutral', 'Draft'],
        'active' => ['success', 'Aktif'],
        'inactive' => ['neutral', 'Nonaktif'],
    ];
    [$tone, $label] = $map[$value] ?? ['neutral', $value];
@endphp
<x-badge :tone="$tone" {{ $attributes }}>@if ($value === 'ongoing')<span class="tr-live inline-block size-1.5 rounded-full bg-current" aria-hidden="true"></span>@endif{{ __($label) }}</x-badge>
