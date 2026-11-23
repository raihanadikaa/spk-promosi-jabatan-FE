{{-- Ganti bahasa cepat: ID | EN dengan pil yang meluncur ke pilihan baru, lalu halaman dimuat ulang dalam bahasa itu.
     Tamu → disimpan di sesi; pengguna login → disimpan juga di akun. Intro logo login tidak diputar ulang untuk ini. --}}
@php $current = app()->getLocale(); @endphp
<form method="POST" action="{{ route('locale.update') }}"
      x-data="{ active: @js($current), pick(code) { if (code === this.active) return; this.active = code; this.$refs.value.value = code;
                try { sessionStorage.setItem('tr-skip-intro', '1') } catch (e) {}
                setTimeout(() => this.$root.submit(), window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 260) } }"
      class="tr-lang relative inline-grid grid-cols-2 items-center rounded-full border border-line bg-surface p-0.5 text-xs font-bold {{ $class ?? '' }}"
      aria-label="{{ __('Bahasa') }}">
    @csrf
    <input type="hidden" name="locale" x-ref="value" value="{{ $current }}">
    {{-- Pil penanda pilihan: meluncur (transform) ke tombol yang dipilih --}}
    <span class="tr-lang-pill pointer-events-none absolute inset-y-0.5 left-0.5 w-[calc(50%-2px)] rounded-full bg-brand shadow-sm"
          :style="active === 'en' ? 'transform: translateX(100%)' : ''"
          @if ($current === 'en') style="transform: translateX(100%)" @endif aria-hidden="true"></span>
    @foreach (['id' => 'ID', 'en' => 'EN'] as $code => $short)
        <button type="button" @click="pick('{{ $code }}')" title="{{ \App\Http\Middleware\SetLocale::SUPPORTED[$code] }}"
                @class(['relative z-10 inline-flex items-center justify-center gap-1.5 rounded-full px-2.5 py-1 transition-colors duration-200',
                        'text-white' => $current === $code, 'text-ink-muted hover:text-ink' => $current !== $code])
                :class="active === '{{ $code }}' ? '!text-white' : '!text-ink-muted hover:!text-ink'"
                :aria-pressed="(active === '{{ $code }}').toString()">
            @if ($flags ?? false)
                {{-- Bendera SVG (emoji bendera tidak tampil di Windows) --}}
                <span class="inline-block size-3.5 shrink-0 overflow-hidden rounded-full ring-1 ring-black/25" aria-hidden="true">
                    @if ($code === 'id')
                        <svg viewBox="0 0 20 20" class="size-full"><rect width="20" height="10" fill="#E70011"/><rect y="10" width="20" height="10" fill="#fff"/></svg>
                    @else
                        <svg viewBox="0 0 60 60" class="size-full"><rect width="60" height="60" fill="#012169"/><path d="M0 0 60 60M60 0 0 60" stroke="#fff" stroke-width="12"/><path d="M0 0 60 60M60 0 0 60" stroke="#C8102E" stroke-width="5"/><path d="M30 0v60M0 30h60" stroke="#fff" stroke-width="18"/><path d="M30 0v60M0 30h60" stroke="#C8102E" stroke-width="10"/></svg>
                    @endif
                </span>
            @endif
            {{ $short }}</button>
    @endforeach
</form>
