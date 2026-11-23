/**
 * Preferensi tampilan: tema & ukuran teks.
 * Diterapkan langsung di halaman, disimpan di perangkat (untuk halaman login/tamu) dan — bila sedang login —
 * di akun lewat PUT /akun/preferensi, supaya sama di semua perangkat & setiap kali login.
 */
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;
const calm = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function remember(key, value) {
    try {
        value === null ? localStorage.removeItem(key) : localStorage.setItem(key, value);
    } catch (e) { /* mode privat: tetap berlaku di halaman ini */ }
}

/** Simpan ke akun (hanya bila ada sesi login & token CSRF di halaman). Mengembalikan true bila tersimpan. */
export async function savePreference(data) {
    const url = document.documentElement.dataset.prefUrl;
    const token = csrf();
    if (!url || !token) return false;
    try {
        const res = await fetch(url, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(data),
            credentials: 'same-origin',
        });
        return res.ok;
    } catch (e) {
        return false;
    }
}

export function applyTheme(choice) {
    const root = document.documentElement;
    const dark = choice === 'dark' || (choice === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    const apply = () => {
        root.setAttribute('data-theme', dark ? 'dark' : 'light');
        root.setAttribute('data-theme-choice', choice);
        window.dispatchEvent(new CustomEvent('tr-theme', { detail: { choice, dark } }));
    };
    if (document.startViewTransition && !calm()) {
        const vt = document.startViewTransition(apply);
        [vt.ready, vt.finished].forEach((p) => p.catch(() => {}));
    } else {
        apply();
    }
    remember('tr-theme', choice === 'system' ? null : choice);
}

export function setTheme(choice) {
    applyTheme(choice);
    return savePreference({ theme: choice });
}

export function setTextSize(size) {
    document.documentElement.setAttribute('data-text', size);
    remember('tr-text', size === 'md' ? null : size);
    return savePreference({ text_size: size });
}

/** Tombol cepat terang/gelap (sidebar, bar atas, menu akun). */
export function themeToggle() {
    return {
        dark: document.documentElement.dataset.theme === 'dark',
        init() {
            window.addEventListener('tr-theme', (e) => { this.dark = e.detail.dark; });
        },
        toggle() { setTheme(this.dark ? 'light' : 'dark'); },
    };
}

/** Pilihan tema di halaman Tampilan: terang, gelap, atau ikuti sistem. */
export function themePicker() {
    return {
        choice: document.documentElement.dataset.themeChoice || 'system',
        saved: false,
        async set(value) {
            this.choice = value;
            this.saved = await setTheme(value);
            setTimeout(() => { this.saved = false; }, 1800);
        },
    };
}

/** Pilihan ukuran teks: kecil, sedang, besar. */
export function textPicker() {
    return {
        size: document.documentElement.dataset.text || 'md',
        saved: false,
        async set(value) {
            this.size = value;
            this.saved = await setTextSize(value);
            setTimeout(() => { this.saved = false; }, 1800);
        },
    };
}
