/**
 * Umpan balik saat menunggu server:
 *  1. Tombol kirim form (POST) menampilkan putaran "memproses" dan form tidak bisa terkirim dua kali.
 *     Tombol TIDAK di-disable: tombol ber-name/value (mis. Setujui/Tolak) tetap ikut terkirim.
 *     Form yang dibatalkan (confirm → Batal, validasi browser) tidak terpengaruh.
 *  2. Garis progres tipis di atas layar saat pindah halaman.
 * Form yang sudah punya penanganan sendiri bisa dikecualikan dengan atribut data-no-busy.
 */
function reset() {
    document.documentElement.classList.remove('tr-navigating');
    document.querySelectorAll('form[data-submitting]').forEach((form) => {
        delete form.dataset.submitting;
        form.removeAttribute('aria-busy');
    });
    document.querySelectorAll('.tr-busy').forEach((btn) => {
        btn.classList.remove('tr-busy');
        btn.querySelector('.tr-spinner')?.remove();
        if (btn.dataset.originalLabel) {
            btn.querySelector('.tr-label').textContent = btn.dataset.originalLabel;
        }
    });
}

function onSubmit(event) {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() !== 'post' || form.target === '_blank') return;

    if (form.dataset.submitting) {
        event.preventDefault(); // klik kedua saat masih memproses
        return;
    }
    if (event.defaultPrevented || form.hasAttribute('data-no-busy')) return;

    form.dataset.submitting = '1';
    form.setAttribute('aria-busy', 'true');
    document.documentElement.classList.add('tr-navigating');

    const btn = event.submitter ?? form.querySelector('button[type=submit], button:not([type])');
    if (!btn) return;
    btn.classList.add('tr-busy');
    const spinner = document.createElement('span');
    spinner.className = 'tr-spinner';
    spinner.setAttribute('aria-hidden', 'true');
    btn.prepend(spinner);

    // Teks khusus saat menunggu, mis. data-busy-label="Menghitung…"
    if (btn.dataset.busyLabel) {
        const label = btn.querySelector('.tr-label');
        if (label) {
            btn.dataset.originalLabel = label.textContent;
            label.textContent = btn.dataset.busyLabel;
        }
    }
}

function onClick(event) {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    const link = event.target.closest('a[href]');
    if (!link || link.target || link.hasAttribute('download')) return;
    const url = new URL(link.href, location.href);
    if (url.origin !== location.origin || url.hash && url.pathname === location.pathname && url.search === location.search) return;
    if (/\/(unduh|matriks|templat)(\/|$)/.test(url.pathname)) return; // unduhan file tidak berpindah halaman
    document.documentElement.classList.add('tr-navigating');
}

export function initInteractions() {
    // Didaftarkan di document (fase bubble) supaya berjalan SETELAH handler @submit milik form (confirm).
    document.addEventListener('submit', onSubmit);
    document.addEventListener('click', onClick);
    // Kembali lewat tombol Back (bfcache): kembalikan tombol & progres ke keadaan normal.
    window.addEventListener('pageshow', (e) => e.persisted && reset());
}
