/**
 * Animasi logo TalentRank: bulatan "naik tangga".
 *  - Masuk  : diputar setiap kali halaman login dibuka/dimuat ulang (kecuali sesudah login gagal).
 *             Di akhir, logo besar terbang & mengecil menjadi logo di halaman login, nama meluncur ke sebelahnya.
 *  - Keluar : logo kecil di aplikasi membesar & terbang ke tengah, bulatan turun tangga, lalu ke halaman login.
 * Web Animations API (transform/opacity saja → mulus & ringan). Bisa dilewati dengan klik/tombol apa pun.
 * Tidak diputar bila pengguna meminta gerak dikurangi. Bila JavaScript gagal, CSS menyembunyikan layar intro
 * sendiri setelah beberapa detik (lihat .tr-intro di app.css), jadi halaman login tidak pernah tertutup.
 */

// Geometri logo (viewBox 32×32): tangga M8 23h5v-5h5v-5h6, bulatan r=1.9 di (24, 9.5).
const R = 1.9;
const HOME = { x: 24, y: 9.5 };
// Titik pijak bulatan di tiap anak tangga, relatif terhadap posisi akhir.
const STEPS = [
    { x: 10.5 - HOME.x, y: 23 - R - HOME.y },
    { x: 15.5 - HOME.x, y: 18 - R - HOME.y },
    { x: 21 - HOME.x, y: 13 - R - HOME.y },
];
const TOP = { x: 0, y: 0 };

const SPRING = 'cubic-bezier(0.34, 1.56, 0.64, 1)';
const SMOOTH = 'cubic-bezier(0.22, 1, 0.36, 1)';
const UP = 'cubic-bezier(0.33, 0, 0.67, 0)'; // naik melambat ke puncak lengkung
const DOWN = 'cubic-bezier(0.33, 1, 0.68, 1)';
// Gerak "terbang" logo antar posisi: pelan di awal & akhir, cepat di tengah.
// Logo terbang: pindah & mengecil memakai SATU kurva yang sama → lintasan lurus, logo tepat menimpa tujuannya.
// Awal lembut, pendaratan panjang & pelan (tanpa rem mendadak).
const TRAVEL = 'cubic-bezier(0.5, 0, 0.15, 1)';
const SHRINK = TRAVEL;
const GROW = TRAVEL;

const calm = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const EN = () => (document.documentElement.lang || '').startsWith('en');

export function logoMarkup() {
    return `
        <div class="tr-intro-bg"></div>
        <div class="tr-intro-stage">
            <div class="tr-intro-mark"><div class="tr-mark-inner">
            <span class="tr-mark-shadow"></span>
            <svg class="tr-intro-logo" viewBox="0 0 32 32" aria-hidden="true">
                <rect class="tr-i-box" width="32" height="32" rx="7" fill="#2563EB"/>
                <path class="tr-i-stairs" d="M8 23h5v-5h5v-5h6" pathLength="1" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <g class="tr-i-hop"><circle class="tr-i-glow" cx="${HOME.x}" cy="${HOME.y}" r="${R}" fill="#BFDBFE"/><circle class="tr-i-dot" cx="${HOME.x}" cy="${HOME.y}" r="${R}" fill="#BFDBFE"/></g>
            </svg>
            </div></div>
            <p class="tr-i-word">TalentRank</p>
            <p class="tr-i-tag">${EN() ? 'Promotion decision support system' : 'Sistem pendukung keputusan promosi jabatan'}</p>
        </div>`;
}

const t = (p) => `translate(${p.x}px, ${p.y}px)`;

/** Elemen pertama yang cocok dan benar-benar terlihat di layar (mis. logo sidebar di desktop, logo bar atas di HP). */
function visible(selector) {
    return [...document.querySelectorAll(selector)].find((n) => {
        const r = n.getBoundingClientRect();
        return r.width > 0 && r.height > 0 && r.bottom > 0 && r.right > 0 && r.top < innerHeight && r.left < innerWidth;
    }) || null;
}

const fontSize = (n) => parseFloat(getComputedStyle(n).fontSize) || 16;

/**
 * Transform yang memindahkan kotak `from` tepat ke kotak `to` (transform-origin kiri-atas):
 * rata kiri, rata tengah vertikal, skala = lebar tujuan / lebar asal (atau `scale` bila diberikan).
 */
/** Sama dengan delta(), tetapi pindah & skala dipisah untuk dua lapisan GPU: pembungkus bergeser, isi mengecil. */
function split(from, to) {
    const fr = from.getBoundingClientRect();
    const tr = to.getBoundingClientRect();
    const s = tr.width / fr.width;
    const dx = tr.left - fr.left;
    const dy = tr.top + tr.height / 2 - (fr.top + (fr.height * s) / 2);
    return { move: `translate(${dx}px, ${dy}px)`, scale: `scale(${s})` };
}

function delta(from, to, scale) {
    const fr = from.getBoundingClientRect();
    const tr = to.getBoundingClientRect();
    const s = scale ?? tr.width / fr.width;
    const dx = tr.left - fr.left;
    const dy = tr.top + tr.height / 2 - (fr.top + (fr.height * s) / 2);
    return `translate(${dx}px, ${dy}px) scale(${s})`;
}

/**
 * Susun lompatan dari titik ke titik: lengkung (naik melambat, turun cepat) + regang saat lepas & gepeng saat mendarat.
 * @returns {{ move: Keyframe[], squash: Keyframe[], duration: number }}
 */
function hops(path, { hop = 260, rest = 90, height = 4.2 } = {}) {
    const total = (path.length - 1) * (hop + rest) + rest;
    const at = (ms) => ms / total;
    const move = [{ offset: 0, transform: t(path[0]), easing: 'linear' }];
    const squash = [{ offset: 0, transform: 'scale(1, 1)' }];
    let clock = 0;
    for (let i = 1; i < path.length; i++) {
        const a = path[i - 1];
        const b = path[i];
        const apex = { x: a.x + (b.x - a.x) * 0.55, y: Math.min(a.y, b.y) - height };
        // lepas landas: regang ke atas
        squash.push({ offset: at(clock), transform: 'scale(1.15, 0.8)' });
        squash.push({ offset: at(clock + hop * 0.18), transform: 'scale(0.86, 1.18)' });
        squash.push({ offset: at(clock + hop * 0.5), transform: 'scale(1, 1)' });
        // lengkung
        move[move.length - 1].easing = UP;
        move.push({ offset: at(clock + hop * 0.5), transform: t(apex), easing: DOWN });
        move.push({ offset: at(clock + hop), transform: t(b), easing: 'linear' });
        // mendarat: gepeng lalu memantul kembali
        squash.push({ offset: at(clock + hop * 0.9), transform: 'scale(0.9, 1.12)' });
        squash.push({ offset: at(clock + hop + 40), transform: 'scale(1.3, 0.72)' });
        squash.push({ offset: at(clock + hop + rest), transform: 'scale(1, 1)' });
        clock += hop + rest;
        move.push({ offset: at(clock), transform: t(b), easing: 'linear' });
    }
    move[move.length - 1].offset = 1;
    squash.push({ offset: 1, transform: 'scale(1, 1)' });
    // offset harus naik; buang duplikat yang sama persis
    const clean = (frames) => frames.filter((f, i) => i === 0 || f.offset > frames[i - 1].offset || i === frames.length - 1);
    return { move: clean(move), squash: clean(squash), duration: total };
}

function el(root, sel) { return root.querySelector(sel); }

/** Intro di halaman login. */
export function playIntro(overlay, { onDone } = {}) {
    const bg = el(overlay, '.tr-intro-bg');
    const stage = el(overlay, '.tr-intro-stage');
    // Yang terbang: pembungkus logo (transform saja). Bayangan = lapisan terpisah yang hanya memudar.
    // Tidak ada filter yang dianimasikan → seluruh gerak diproses GPU, tidak tersendat.
    const logo = el(overlay, '.tr-intro-mark');
    const inner = el(overlay, '.tr-mark-inner');
    const shadow = el(overlay, '.tr-mark-shadow');
    const box = el(overlay, '.tr-i-box');
    const stairs = el(overlay, '.tr-i-stairs');
    const hop = el(overlay, '.tr-i-hop');
    const dot = el(overlay, '.tr-i-dot');
    const glow = el(overlay, '.tr-i-glow');
    const word = el(overlay, '.tr-i-word');
    const tag = el(overlay, '.tr-i-tag');

    const opts = (delay, duration, easing = SMOOTH) => ({ delay, duration, easing, fill: 'both' });
    const anims = [];

    anims.push(box.animate([{ transform: 'scale(0.4) rotate(-12deg)', opacity: 0 }, { transform: 'scale(1) rotate(0)', opacity: 1 }], opts(80, 650, SPRING)));
    anims.push(stairs.animate([{ strokeDashoffset: 1 }, { strokeDashoffset: 0 }], opts(380, 560)));
    stairs.style.strokeDasharray = '1';

    // Bulatan jatuh ke anak tangga pertama, lalu tiga lompatan naik sampai puncak.
    const drop = { x: STEPS[0].x, y: STEPS[0].y - 12 };
    const h = hops([drop, STEPS[0], STEPS[1], STEPS[2], TOP], { hop: 300, rest: 110, height: 4.5 });
    const dotDelay = 820;
    anims.push(dot.animate([{ opacity: 0 }, { opacity: 1 }], opts(dotDelay, 140)));
    anims.push(hop.animate(h.move, { delay: dotDelay, duration: h.duration, fill: 'both' }));
    anims.push(dot.animate(h.squash, { delay: dotDelay, duration: h.duration, fill: 'both' }));
    const arrive = dotDelay + h.duration;

    // Sampai puncak: kilau, nama & keterangan muncul.
    anims.push(glow.animate([{ transform: 'scale(1)', opacity: 0 }, { transform: 'scale(1)', opacity: 0.7, offset: 0.1 }, { transform: 'scale(3.6)', opacity: 0 }], opts(arrive - 60, 700, 'ease-out')));
    anims.push(word.animate([{ transform: 'translateY(14px)', opacity: 0 }, { transform: 'none', opacity: 1 }], opts(arrive - 120, 560)));
    anims.push(tag.animate([{ transform: 'translateY(10px)', opacity: 0 }, { transform: 'none', opacity: 1 }], opts(arrive + 20, 520)));

    // Jeda singkat di puncak lalu langsung terbang: gerak terasa mengalir, tanpa diam yang terlalu lama.
    const exitAt = arrive + 480;
    const landing = []; // animasi pada elemen halaman login (dibatalkan bila intro dilewati)
    let timer = null;
    let finished = false;

    const finish = () => {
        if (finished) return;
        finished = true;
        clearTimeout(timer);
        landing.forEach((a) => a.cancel());
        overlay.remove();
        document.documentElement.classList.remove('tr-intro-on');
        document.removeEventListener('keydown', skip, true);
        onDone?.();
    };
    function skip() {
        // Lewati: langsung ke akhir dengan memudar cepat.
        clearTimeout(timer);
        anims.forEach((a) => a.pause());
        overlay.animate([{ opacity: getComputedStyle(overlay).opacity }, { opacity: 0 }], { duration: 220, fill: 'both' }).onfinish = finish;
    }
    overlay.addEventListener('pointerdown', skip, { once: true });
    document.addEventListener('keydown', skip, { capture: true, once: true });

    // Sesaat sebelum terbang (saat bulatan bersinar diam di puncak), halaman login "dibangunkan": hitung ulang gaya
    // & animasi panelnya dimulai di sini. Satu frame berat di momen diam tidak terlihat, sehingga frame-frame
    // perjalanan logo dan pendaratannya bersih.
    const wake = setTimeout(() => {
        overlay.classList.add('tr-outro');
        document.documentElement.classList.remove('tr-intro-on');
    }, exitAt - 300);
    overlay.addEventListener('pointerdown', () => clearTimeout(wake), { once: true });

    // Keluar: logo terbang & mengecil menjadi logo di halaman login, nama meluncur ke sebelahnya, latar memudar.
    // Posisi tujuan diukur tepat saat keluar (font halaman login sudah dimuat).
    timer = setTimeout(() => {
        const toLogo = visible('[data-intro-logo]');
        const toWord = visible('[data-intro-word]');
        if (!toLogo) {
            anims.push(stage.animate([{ transform: 'none', opacity: 1 }, { transform: 'scale(1.06)', opacity: 0 }], opts(0, 420, 'cubic-bezier(0.4, 0, 1, 1)')));
            const fade = overlay.animate([{ opacity: 1 }, { opacity: 0 }], opts(140, 420, 'ease-in'));
            anims.push(fade);
            fade.onfinish = finish;
            return;
        }

        const FLY_MS = 1050;
        const flight = { duration: FLY_MS, easing: TRAVEL, fill: 'both' };
        word.style.transformOrigin = '0 0';
        const path = split(logo, toLogo);

        const plane = logo.animate([{ transform: 'none' }, { transform: path.move }], flight);
        anims.push(inner.animate([{ transform: 'none' }, { transform: path.scale }], { duration: FLY_MS, easing: SHRINK, fill: 'both' }));
        anims.push(shadow.animate([{ opacity: 1 }, { opacity: 0 }], opts(0, FLY_MS * 0.6, 'ease-out')));
        anims.push(plane);
        anims.push(toWord
            ? word.animate([{ transform: 'none' }, { transform: delta(word, toWord, fontSize(toWord) / fontSize(word)) }], flight)
            : word.animate([{ opacity: 1 }, { opacity: 0 }], opts(0, 300, 'ease-in')));
        anims.push(tag.animate([{ opacity: 1, transform: 'none' }, { opacity: 0, transform: 'translateY(8px)' }], opts(0, 280, 'ease-in')));
        anims.push(bg.animate([{ opacity: 1 }, { opacity: 0 }], opts(FLY_MS * 0.15, FLY_MS * 0.85, 'cubic-bezier(0.45, 0, 0.55, 1)')));

        // Serah terima tanpa celah: yang asli muncul dulu DI BAWAH logo terbang (tidak terlihat karena tertutup),
        // baru setelah mendarat tepat di pikselnya, logo terbang memudar → tidak ada redup, kedip, atau "pop".
        landing.push(toLogo.animate([{ opacity: 0 }, { opacity: 1 }], { delay: FLY_MS - 260, duration: 260, fill: 'backwards' }));
        if (toWord) {
            landing.push(toWord.animate([{ opacity: 0 }, { opacity: 1 }], { delay: FLY_MS * 0.6, duration: FLY_MS * 0.3, fill: 'backwards' }));
            anims.push(word.animate([{ opacity: 1 }, { opacity: 0 }], opts(FLY_MS * 0.75, FLY_MS * 0.25, 'ease-in')));
        }
        plane.onfinish = () => {
            const handoff = logo.animate([{ opacity: 1 }, { opacity: 0 }], { duration: 180, easing: 'ease-out', fill: 'forwards' });
            handoff.onfinish = () => { landing.length = 0; finish(); };
        };
    }, exitAt);
}

/**
 * Animasi keluar: logo kecil di aplikasi (sidebar / bar atas) membesar & terbang ke tengah,
 * bulatan turun tangga, lalu diarahkan ke halaman login (yang memutar intro naik tangga lagi).
 */
function playLogout(form) {
    const overlay = document.createElement('div');
    overlay.className = 'tr-intro tr-outro';
    overlay.setAttribute('aria-hidden', 'true');
    overlay.innerHTML = logoMarkup();
    document.body.appendChild(overlay);

    const bg = el(overlay, '.tr-intro-bg');
    const logo = el(overlay, '.tr-intro-mark');
    const inner = el(overlay, '.tr-mark-inner');
    const shadow = el(overlay, '.tr-mark-shadow');
    const stairs = el(overlay, '.tr-i-stairs');
    const hop = el(overlay, '.tr-i-hop');
    const dot = el(overlay, '.tr-i-dot');
    const word = el(overlay, '.tr-i-word');
    const tag = el(overlay, '.tr-i-tag');
    tag.textContent = EN() ? 'See you soon' : 'Sampai jumpa';
    stairs.style.strokeDasharray = '1';

    const o = (delay, duration, easing = SMOOTH) => ({ delay, duration, easing, fill: 'both' });
    const fromLogo = visible('[data-logo-anchor]');
    const fromWord = visible('[data-word-anchor]');
    let descend = 320;

    bg.animate([{ opacity: 0 }, { opacity: 1 }], o(0, fromLogo ? 600 : 260, 'cubic-bezier(0.45, 0, 0.55, 1)'));
    if (fromLogo) {
        const FLY_MS = 900;
        const flight = { duration: FLY_MS, easing: TRAVEL, fill: 'both' };
        word.style.transformOrigin = '0 0';
        const path = split(logo, fromLogo);
        logo.animate([{ transform: path.move }, { transform: 'none' }], flight);
        inner.animate([{ transform: path.scale }, { transform: 'none' }], { duration: FLY_MS, easing: GROW, fill: 'both' });
        shadow.animate([{ opacity: 0 }, { opacity: 1 }], o(FLY_MS * 0.4, FLY_MS * 0.6, 'ease-in'));
        fromLogo.animate([{ opacity: 1 }, { opacity: 0 }], { duration: 1, fill: 'forwards' }); // yang asli "diangkat"
        if (fromWord) {
            word.animate([{ transform: delta(word, fromWord, fontSize(fromWord) / fontSize(word)) }, { transform: 'none' }], flight);
            fromWord.animate([{ opacity: 1 }, { opacity: 0 }], { duration: 1, fill: 'forwards' });
        } else {
            word.animate([{ opacity: 0, transform: 'translateY(8px)' }, { opacity: 1, transform: 'none' }], o(FLY_MS * 0.5, 400));
        }
        tag.animate([{ opacity: 0, transform: 'translateY(6px)' }, { opacity: 1, transform: 'none' }], o(FLY_MS - 150, 360));
        descend = FLY_MS + 120;
    } else {
        el(overlay, '.tr-i-box').animate([{ transform: 'scale(0.85)', opacity: 0 }, { transform: 'scale(1)', opacity: 1 }], o(60, 380, SPRING));
        word.animate([{ opacity: 0, transform: 'translateY(8px)' }, { opacity: 1, transform: 'none' }], o(120, 360));
        tag.animate([{ opacity: 0 }, { opacity: 1 }], o(220, 360));
    }

    // Turun tangga: puncak → anak tangga 3 → 2 → 1, lalu tangga terhapus & bulatan menghilang.
    const h = hops([TOP, STEPS[2], STEPS[1], STEPS[0]], { hop: 220, rest: 70, height: 3 });
    hop.animate(h.move, { delay: descend, duration: h.duration, fill: 'both' });
    dot.animate(h.squash, { delay: descend, duration: h.duration, fill: 'both' });
    const end = descend + h.duration;
    stairs.animate([{ strokeDashoffset: 0 }, { strokeDashoffset: -1 }], o(end - 120, 380, 'ease-in'));
    dot.animate([{ opacity: 1 }, { opacity: 0 }], o(end, 220, 'ease-in'));
    // Logo & tulisan memudar ke layar biru kosong — layar yang sama dengan awal intro di halaman login.
    const out = o(end + 120, 320, 'ease-in');
    logo.animate([{ opacity: 1, transform: 'none' }, { opacity: 0, transform: 'scale(0.92)' }], out);
    word.animate([{ opacity: 1 }, { opacity: 0 }], out);
    tag.animate([{ opacity: 1 }, { opacity: 0 }], out);

    // Sidebar punya lapisan View Transition sendiri; tanpa ini lapisan itu sempat tergambar DI ATAS layar keluar
    // saat pindah halaman (tampilan lama terlihat sekejap).
    document.querySelectorAll('#sidebar').forEach((n) => { n.style.viewTransitionName = 'none'; });

    setTimeout(() => form.submit(), end + 460);
}

export function initIntro() {
    const overlay = document.getElementById('tr-intro');
    if (overlay && document.documentElement.classList.contains('tr-intro-on') && !calm()) {
        playIntro(overlay);
    } else if (overlay) {
        // Intro tidak diputar (gerak dikurangi, sesudah login gagal, atau ganti bahasa): buang dari halaman.
        overlay.remove();
        document.documentElement.classList.remove('tr-intro-on');
    }

    // Logout dengan animasi (didaftarkan sebelum handler "memproses" form lain).
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-logout') || e.defaultPrevented || calm()) return;
        if (form.dataset.leaving) { e.preventDefault(); return; }
        if (!('animate' in Element.prototype)) return;
        e.preventDefault();
        form.dataset.leaving = '1';
        playLogout(form);
    });
}
