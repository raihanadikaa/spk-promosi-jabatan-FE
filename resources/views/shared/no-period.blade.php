<x-app-layout title="Belum ada periode penilaian">
    <x-panel>
        @if (auth()->user()->hasRole('hrd'))
            <x-empty-state icon="calendar" title="Mulai dengan membuat periode penilaian"
                           body="Periode menentukan rentang waktu, bobot kriteria, dan batas pengisian nilai oleh atasan.">
                <x-btn :href="route('hrd.periods')" icon="plus">Buat periode</x-btn>
            </x-empty-state>
        @else
            <x-empty-state icon="calendar" title="Belum ada periode penilaian"
                           body="Halaman ini terisi setelah HRD membuat dan memulai periode penilaian." />
        @endif
    </x-panel>
</x-app-layout>
