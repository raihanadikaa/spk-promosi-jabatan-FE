@php
    // Error validasi/aturan bisnis selalu ditampilkan di sini juga, supaya tidak pernah ada aksi yang "diam-diam gagal".
    $toast = match (true) {
        (bool) session('success') => ['success', session('success')],
        (bool) session('error') => ['error', session('error')],
        $errors->any() => ['error', $errors->first()],
        default => null,
    };
@endphp
@if ($toast)
    <div x-data="{ open: true }" x-init="setTimeout(() => open = false, {{ $toast[0] === 'error' ? 9000 : 5000 }})" x-show="open"
         x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="opacity-0 translate-y-1"
         class="fixed bottom-4 right-4 z-[60] flex max-w-sm items-start gap-3 rounded-panel border bg-surface px-4 py-3 shadow-[0_8px_24px_rgba(26,29,36,0.12)] {{ $toast[0] === 'error' ? 'border-bad/40' : 'border-line' }}"
         role="{{ $toast[0] === 'error' ? 'alert' : 'status' }}" aria-live="polite">
        <x-icon :name="$toast[0] === 'success' ? 'check-circle' : 'alert'" @class(['mt-0.5', 'text-ok' => $toast[0] === 'success', 'text-bad' => $toast[0] === 'error']) />
        <p class="text-base text-ink">{{ $toast[1] }}</p>
        <button type="button" class="-mr-1 ml-auto grid size-7 place-items-center rounded-ctl text-ink-muted hover:bg-canvas" @click="open = false" aria-label="Tutup notifikasi">
            <x-icon name="x" class="size-4" />
        </button>
    </div>
@endif
