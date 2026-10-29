@props(['value'])
{{-- Pemetaan status data → badge. Teks selalu tampil, tidak hanya warna. --}}
@php
    $map = [
        'submitted' => ['success', 'Terkirim'],
        'draft' => ['warning', 'Draft'],
        'pending' => ['neutral', 'Belum dinilai'],
        'approved' => ['success', 'Disetujui'],
        'rejected' => ['danger', 'Ditolak'],
        'waiting' => ['warning', 'Menunggu keputusan'],
        'ongoing' => ['brand', 'Berlangsung'],
        'closed' => ['neutral', 'Ditutup'],
        'draft_period' => ['neutral', 'Draft'],
        'active' => ['success', 'Aktif'],
        'inactive' => ['neutral', 'Nonaktif'],
    ];
    [$tone, $label] = $map[$value] ?? ['neutral', $value];
@endphp
<x-badge :tone="$tone" {{ $attributes }}>{{ $label }}</x-badge>
