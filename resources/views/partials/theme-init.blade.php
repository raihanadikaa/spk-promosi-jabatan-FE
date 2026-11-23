{{-- Terapkan tema & ukuran teks sebelum halaman tergambar (tanpa kedip).
     Urutan: pilihan yang tersimpan di akun → pilihan di perangkat ini (tamu) → bawaan (ikuti sistem, sedang). --}}
@php $pref = auth()->user(); @endphp
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    (function () {
        var server = @json(['theme' => $pref?->theme, 'text' => $pref?->text_size]);
        var local = {};
        try { local = { theme: localStorage.getItem('tr-theme'), text: localStorage.getItem('tr-text') }; } catch (e) {}
        var theme = server.theme || local.theme || 'system';
        var dark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        var root = document.documentElement;
        root.setAttribute('data-theme', dark ? 'dark' : 'light');
        root.setAttribute('data-text', server.text || local.text || 'md');
        root.setAttribute('data-theme-choice', theme);

        // Transisi pindah halaman bawaan browser bisa dibatalkan browser (mis. ukuran layar berubah di HP);
        // janji (promise)-nya ditangani supaya tidak tercatat sebagai error.
        var quiet = function (e) {
            var vt = e.viewTransition;
            if (!vt) return;
            [vt.ready, vt.finished, vt.updateCallbackDone].forEach(function (p) { p && p.catch(function () {}); });
        };
        window.addEventListener('pagereveal', quiet);
        window.addEventListener('pageswap', quiet);
    })();
</script>
