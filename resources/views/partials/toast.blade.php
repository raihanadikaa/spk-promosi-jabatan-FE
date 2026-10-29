@php
    $toast = session('success') ? ['success', session('success')] : (session('error') ? ['error', session('error')] : null);
@endphp
@if ($toast)
    <div x-data="{ open: true }" x-init="setTimeout(() => open = false, 5000)" x-show="open"
         x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="opacity-0 translate-y-1"
         class="fixed bottom-4 right-4 z-50 flex max-w-sm items-start gap-3 rounded-panel border border-line bg-surface px-4 py-3 shadow-[0_8px_24px_rgba(26,29,36,0.12)]"
         role="status" aria-live="polite">
        <x-icon :name="$toast[0] === 'success' ? 'check-circle' : 'alert'" @class(['mt-0.5', 'text-ok' => $toast[0] === 'success', 'text-bad' => $toast[0] === 'error']) />
        <p class="text-base text-ink">{{ $toast[1] }}</p>
        <button type="button" class="-mr-1 ml-auto grid size-7 place-items-center rounded-ctl text-ink-muted hover:bg-canvas" @click="open = false" aria-label="Tutup notifikasi">
            <x-icon name="x" class="size-4" />
        </button>
    </div>
@endif
