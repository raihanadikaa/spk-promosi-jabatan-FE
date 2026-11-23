{{-- Putar intro logo sekali per sesi browser dan setiap kali sesudah keluar (ditandai intro.js). --}}
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    (function () {
        try {
            if (sessionStorage.getItem('tr-intro') !== 'seen' && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                document.documentElement.classList.add('tr-intro-on');
            }
        } catch (e) {}
    })();
</script>
