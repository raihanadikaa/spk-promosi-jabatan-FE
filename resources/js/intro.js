/**
 * Animasi logo TalentRank: bulatan "naik tangga".
 *  - Masuk  : diputar di halaman login (sekali per sesi browser, dan setiap kali sesudah keluar).
 *  - Keluar : bulatan turun tangga sebelum diarahkan ke halaman login.
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

const calm = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export function logoMarkup() {
    return `
        <div class="tr-intro-stage">
            <svg class="tr-intro-logo" viewBox="0 0 32 32" aria-hidden="true">
                <rect class="tr-i-box" width="32" height="32" rx="7" fill="#2563EB"/>
                <path class="tr-i-stairs" d="M8 23h5v-5h5v-5h6" pathLength="1" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <g class="tr-i-hop"><circle class="tr-i-glow" cx="${HOME.x}" cy="${HOME.y}" r="${R}" fill="#BFDBFE"/><circle class="tr-i-dot" cx="${HOME.x}" cy="${HOME.y}" r="${R}" fill="#BFDBFE"/></g>
            </svg>
            <p class="tr-i-word">TalentRank</p>
            <p class="tr-i-tag">Sistem pendukung keputusan promosi jabatan</p>
        </div>`;
}

const t = (p) => `translate(${p.x}px, ${p.y}px)`;

/**
 * Susun lompatan dari titik ke titik: lengkung (naik melambat, turun cepat) + regang saat lepas & gepeng saat mendarat.
 * @returns {{ move: Keyframe[], squash: Keyframe[], duration: number }}
 */
function hops(path, { hop = 260, rest = 90, height = 4.2, start = 0 } = {}) {
    const total = start + (path.length - 1) * (hop + rest) + rest;
    const at = (ms) => ms / total;
    const move = [{ offset: 0, transform: t(path[0]), easing: 'linear' }];
    const squash = [{ offset: 0, transform: 'scale(1, 1)' }];
    let clock = start;
    if (start) {
        move.push({ offset: at(start), transform: t(path[0]) });
        squash.push({ offset: at(start), transform: 'scale(1, 1)' });
    }
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
    const box = el(overlay, '.tr-i-box');
    const stairs = el(overlay, '.tr-i-stairs');
    const hop = el(overlay, '.tr-i-hop');
    const dot = el(overlay, '.tr-i-dot');
    const glow = el(overlay, '.tr-i-glow');
    const word = el(overlay, '.tr-i-word');
    const tag = el(overlay, '.tr-i-tag');
    const stage = el(overlay, '.tr-intro-stage');

    const opts = (delay, duration, easing = SMOOTH) => ({ delay, duration, easing, fill: 'both' });
    const anims = [];

    anims.push(box.animate([{ transform: 'scale(0.4) rotate(-12deg)', opacity: 0 }, { transform: 'scale(1) rotate(0)', opacity: 1 }], opts(80, 650, SPRING)));
    anims.push(stairs.animate([{ strokeDashoffset: 1 }, { strokeDashoffset: 0 }], opts(380, 560)));
    stairs.style.strokeDasharray = '1';

    // Bulatan jatuh ke anak tangga pertama, lalu tiga lompatan naik sampai puncak.
    const drop = { x: STEPS[0].x, y: STEPS[0].y - 12 };
    const path = [drop, STEPS[0], STEPS[1], STEPS[2], TOP];
    const h = hops(path, { hop: 300, rest: 110, height: 4.5 });
    const dotDelay = 820;
    anims.push(dot.animate([{ opacity: 0 }, { opacity: 1 }], opts(dotDelay, 140)));
    anims.push(hop.animate(h.move, { delay: dotDelay, duration: h.duration, fill: 'both' }));
    anims.push(dot.animate(h.squash, { delay: dotDelay, duration: h.duration, fill: 'both' }));
    const arrive = dotDelay + h.duration;

    // Sampai puncak: kilau
    anims.push(glow.animate([{ transform: 'scale(1)', opacity: 0.0 }, { transform: 'scale(1)', opacity: 0.7, offset: 0.1 }, { transform: 'scale(3.6)', opacity: 0 }], opts(arrive - 60, 700, 'ease-out')));
    anims.push(word.animate([{ transform: 'translateY(14px)', opacity: 0, filter: 'blur(4px)' }, { transform: 'none', opacity: 1, filter: 'blur(0)' }], opts(arrive - 120, 560)));
    anims.push(tag.animate([{ transform: 'translateY(10px)', opacity: 0 }, { transform: 'none', opacity: 1 }], opts(arrive + 20, 520)));

    // Keluar: panggung sedikit membesar & memudar, layar menghilang
    const exitAt = arrive + 620;
    anims.push(stage.animate([{ transform: 'none', opacity: 1 }, { transform: 'scale(1.06)', opacity: 0 }], opts(exitAt, 420, 'cubic-bezier(0.4, 0, 1, 1)')));
    const fade = overlay.animate([{ opacity: 1 }, { opacity: 0 }], opts(exitAt + 140, 420, 'ease-in'));
    anims.push(fade);

    let finished = false;
    const finish = () => {
        if (finished) return;
        finished = true;
        overlay.remove();
        document.documentElement.classList.remove('tr-intro-on');
        document.removeEventListener('keydown', skip, true);
        onDone?.();
    };
    const skip = () => {
        // Lewati: langsung ke akhir dengan memudar cepat.
        anims.forEach((a) => a.pause());
        overlay.animate([{ opacity: getComputedStyle(overlay).opacity }, { opacity: 0 }], { duration: 220, fill: 'both' }).onfinish = finish;
    };
    overlay.addEventListener('pointerdown', skip, { once: true });
    document.addEventListener('keydown', skip, { capture: true, once: true });
    fade.onfinish = finish;
}

/** Animasi keluar: dipasang pada form logout [data-logout]. */
function playLogout(form) {
    const overlay = document.createElement('div');
    overlay.className = 'tr-intro tr-outro';
    overlay.setAttribute('aria-hidden', 'true');
    overlay.innerHTML = logoMarkup();
    document.body.appendChild(overlay);

    const box = el(overlay, '.tr-i-box');
    const stairs = el(overlay, '.tr-i-stairs');
    const hop = el(overlay, '.tr-i-hop');
    const dot = el(overlay, '.tr-i-dot');
    const word = el(overlay, '.tr-i-word');
    el(overlay, '.tr-i-tag').textContent = 'Sampai jumpa';
    stairs.style.strokeDasharray = '1';

    const o = (delay, duration, easing = SMOOTH) => ({ delay, duration, easing, fill: 'both' });
    overlay.animate([{ opacity: 0 }, { opacity: 1 }], o(0, 260, 'ease-out'));
    box.animate([{ transform: 'scale(0.85)', opacity: 0 }, { transform: 'scale(1)', opacity: 1 }], o(60, 380, SPRING));
    word.animate([{ opacity: 0, transform: 'translateY(8px)' }, { opacity: 1, transform: 'none' }], o(120, 360));
    el(overlay, '.tr-i-tag').animate([{ opacity: 0 }, { opacity: 1 }], o(220, 360));

    // Turun tangga: puncak → anak tangga 3 → 2 → 1, lalu tangga terhapus & bulatan mengecil.
    const h = hops([TOP, STEPS[2], STEPS[1], STEPS[0]], { hop: 220, rest: 70, height: 3 });
    hop.animate(h.move, { delay: 320, duration: h.duration, fill: 'both' });
    dot.animate(h.squash, { delay: 320, duration: h.duration, fill: 'both' });
    const end = 320 + h.duration;
    stairs.animate([{ strokeDashoffset: 0 }, { strokeDashoffset: -1 }], o(end - 120, 380, 'ease-in'));
    dot.animate([{ opacity: 1 }, { opacity: 0 }], o(end, 220, 'ease-in'));

    try { sessionStorage.setItem('tr-intro', 'logout'); } catch (e) { /* tetap keluar */ }
    setTimeout(() => form.submit(), end + 180);
}

export function initIntro() {
    const overlay = document.getElementById('tr-intro');
    if (overlay && document.documentElement.classList.contains('tr-intro-on')) {
        try { sessionStorage.setItem('tr-intro', 'seen'); } catch (e) { /* abaikan */ }
        if (calm()) {
            overlay.remove();
            document.documentElement.classList.remove('tr-intro-on');
        } else {
            playIntro(overlay);
        }
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
