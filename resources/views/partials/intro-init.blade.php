{{-- Intro logo diputar setiap kali halaman login dibuka/dimuat ulang (termasuk sesudah keluar).
     Tidak diputar saat halaman kembali karena login gagal (ada pesan error), supaya tidak menunggu tiap salah ketik,
     dan tidak diputar bila pengguna meminta gerak dikurangi. --}}
@unless ($errors->any())
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    (function () {
        // Baru saja ganti bahasa di halaman login → jangan putar ulang intro (cukup animasi tombol bahasa).
        var skip = false;
        try { skip = sessionStorage.getItem('tr-skip-intro') === '1'; sessionStorage.removeItem('tr-skip-intro'); } catch (e) {}
        if (!skip && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.documentElement.classList.add('tr-intro-on');
            // Intro menggantikan transisi pindah halaman: lewati crossfade bawaan agar layar keluar (gelap)
            // langsung bersambung dengan layar intro (gelap), tanpa tampilan lama yang menyela.
            window.addEventListener('pagereveal', function (e) {
                var vt = e.viewTransition;
                if (!vt) return;
                // Janji (promise) transisi ditolak saat dilewati; ditangani supaya tidak tercatat sebagai error.
                vt.ready.catch(function () {});
                vt.finished.catch(function () {});
                vt.skipTransition();
            }, { once: true });
        }
    })();
</script>
@endunless
