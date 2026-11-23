@props(['compact' => false])
{{-- Ganti mode terang/gelap. Pilihan disimpan di perangkat ini (localStorage); bawaan mengikuti setelan sistem. --}}
<button type="button"
        x-data="{ dark: document.documentElement.dataset.theme === 'dark' }"
        x-on:click="
            const apply = () => {
                dark = !dark;
                document.documentElement.dataset.theme = dark ? 'dark' : 'light';
                try { localStorage.setItem('tr-theme', dark ? 'dark' : 'light') } catch (e) {}
            };
            const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            document.startViewTransition && ! calm ? document.startViewTransition(apply) : apply();
        "
        x-bind:aria-pressed="dark.toString()"
        x-bind:title="dark ? 'Ganti ke mode terang' : 'Ganti ke mode gelap'"
        {{ $attributes->merge(['class' => $compact
            ? 'grid size-10 place-items-center rounded-ctl text-ink-muted transition-colors hover:bg-canvas hover:text-ink'
            : 'flex w-full items-center gap-2 rounded-ctl px-2 py-1.5 text-sm text-ink-muted transition-colors hover:bg-canvas hover:text-ink']) }}>
    <span class="relative grid size-4 place-items-center" aria-hidden="true">
        <x-icon name="moon" class="tr-theme-icon size-4" x-show="! dark" />
        <x-icon name="sun" class="tr-theme-icon size-4" x-show="dark" x-cloak />
    </span>
    @if ($compact)
        <span class="sr-only">Mode gelap</span>
    @else
        <span x-text="dark ? 'Mode terang' : 'Mode gelap'">Mode gelap</span>
    @endif
</button>
