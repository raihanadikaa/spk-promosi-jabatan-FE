@props(['compact' => false])
{{-- Ganti cepat terang/gelap. Tersimpan di akun (bila login) & perangkat ini; lihat resources/js/preferences.js. --}}
<button type="button"
        x-data="themeToggle"
        x-on:click="toggle()"
        x-bind:aria-pressed="dark.toString()"
        x-bind:title="dark ? @js(__('Ganti ke mode terang')) : @js(__('Ganti ke mode gelap'))"
        {{ $attributes->merge(['class' => $compact
            ? 'grid size-10 place-items-center rounded-ctl text-ink-muted transition-colors hover:bg-canvas hover:text-ink'
            : 'flex w-full items-center gap-2 rounded-ctl px-2 py-1.5 text-sm text-ink-muted transition-colors hover:bg-canvas hover:text-ink']) }}>
    <span class="relative grid size-4 place-items-center" aria-hidden="true">
        <x-icon name="moon" class="tr-theme-icon size-4" x-show="! dark" />
        <x-icon name="sun" class="tr-theme-icon size-4" x-show="dark" x-cloak />
    </span>
    @if ($compact)
        <span class="sr-only">{{ __('Mode gelap') }}</span>
    @else
        <span x-text="dark ? @js(__('Mode terang')) : @js(__('Mode gelap'))">{{ __('Mode gelap') }}</span>
    @endif
</button>
