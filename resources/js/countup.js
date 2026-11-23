/**
 * Angka ringkasan "berhitung naik" sekali saat pertama terlihat di layar.
 *
 * - Hanya peningkatan tampilan: teks asli dari server tetap jadi nilai akhir (dikembalikan persis di akhir),
 *   jadi bila JavaScript mati/gagal, angka tetap benar.
 * - Format Indonesia dipertahankan: titik ribuan, koma desimal, jumlah desimal, dan teks di sekitarnya
 *   ("85%", "4.132 / 5.012", "0,9338").
 * - Dilewati bila pengguna meminta gerak dikurangi, atau saat dicetak.
 * - Lebar dikunci selama animasi supaya tata letak tidak bergeser.
 */
const SELECTOR = '#konten .num.text-xl, #konten .num.text-2xl, #konten .num.text-lg, [data-countup]';
const NUMBER = /\d{1,3}(?:\.\d{3})+(?:,\d+)?|\d+(?:,\d+)?/g;
const DURATION = 900;

const easeOut = (t) => 1 - Math.pow(1 - t, 3);

function parts(text) {
    const tokens = [];
    let last = 0;
    for (const m of text.matchAll(NUMBER)) {
        tokens.push({ text: text.slice(last, m.index) });
        const raw = m[0];
        const decimals = raw.includes(',') ? raw.split(',')[1].length : 0;
        tokens.push({
            value: parseFloat(raw.replace(/\./g, '').replace(',', '.')),
            decimals,
            grouping: raw.includes('.'),
        });
        last = m.index + raw.length;
    }
    tokens.push({ text: text.slice(last) });
    return tokens;
}

function render(tokens, progress) {
    return tokens.map((t) => {
        if (t.text !== undefined) return t.text;
        return new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: t.decimals,
            maximumFractionDigits: t.decimals,
            useGrouping: t.grouping,
        }).format(t.value * progress);
    }).join('');
}

function animate(el) {
    // Hanya elemen berisi teks langsung (tanpa elemen anak), supaya markup di dalamnya tidak rusak.
    if (el.dataset.counted || el.children.length) return;
    el.dataset.counted = '1';

    const original = el.textContent;
    const tokens = parts(original);
    if (!tokens.some((t) => t.value > 0)) return;

    const width = el.getBoundingClientRect().width;
    const display = getComputedStyle(el).display;
    if (display === 'inline') el.style.display = 'inline-block';
    el.style.minWidth = `${width}px`;

    const start = performance.now();
    const step = (now) => {
        const t = Math.min(1, (now - start) / DURATION);
        if (t < 1) {
            el.textContent = render(tokens, easeOut(t));
            requestAnimationFrame(step);
        } else {
            el.textContent = original;
            el.style.minWidth = '';
            if (display === 'inline') el.style.display = '';
        }
    };
    requestAnimationFrame(step);
}

export function initCountUp() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || window.matchMedia('print').matches) return;
    if (!('IntersectionObserver' in window)) return;

    const io = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            if (entry.isIntersecting) {
                io.unobserve(entry.target);
                animate(entry.target);
            }
        }
    }, { threshold: 0.4 });

    document.querySelectorAll(SELECTOR).forEach((el) => io.observe(el));
}
