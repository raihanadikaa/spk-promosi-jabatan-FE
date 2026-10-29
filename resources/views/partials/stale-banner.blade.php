{{-- Peringatan: ranking belum mencerminkan data terbaru. HRD mendapat tombol hitung ulang. --}}
@if (! empty($stale))
    <div class="mb-6 flex flex-wrap items-start gap-3 rounded-panel border border-warn/30 bg-warn-bg px-4 py-3 text-warn" role="status">
        <x-icon name="alert" class="mt-0.5" />
        <div class="min-w-0 flex-1">
            <p class="font-semibold">Ranking perlu dihitung ulang</p>
            <ul class="mt-0.5 text-sm">
                @foreach ($stale as $reason)
                    <li>{{ $reason }}</li>
                @endforeach
            </ul>
        </div>
        @if (auth()->user()->hasRole('hrd'))
            <form method="POST" action="{{ route('hrd.saw.run') }}">
                @csrf
                <x-btn type="submit" size="sm" icon="refresh">Hitung ulang sekarang</x-btn>
            </form>
        @else
            <p class="text-sm">HRD akan menghitung ulang sebelum keputusan final.</p>
        @endif
    </div>
@endif
