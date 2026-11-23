{{-- Terapkan tema sebelum halaman tergambar (tanpa kedip putih). Pilihan disimpan per perangkat; bawaan mengikuti sistem. --}}
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    (function () {
        var saved = null;
        try { saved = localStorage.getItem('tr-theme'); } catch (e) {}
        var dark = saved ? saved === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
    })();
</script>
