import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** Token warna & tipografi TalentRank */
const v = (name) => `rgb(var(--c-${name}) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
export default {
    // Mode gelap dipilih lewat atribut data-theme pada <html> (tombol di sidebar; bawaan mengikuti sistem).
    darkMode: ['selector', '[data-theme="dark"]'],

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Support/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Manrope', '"Plus Jakarta Sans Variable"', '"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                xs: ['12px', '16px'],
                sm: ['13px', '18px'],
                base: ['14px', '21px'],
                md: ['16px', '24px'],
                lg: ['20px', '28px'],
                xl: ['26px', '32px'],
                '2xl': ['34px', '40px'],
            },
            // Warna dibaca dari variabel CSS (resources/css/app.css) supaya mode gelap cukup mengganti variabelnya.
            // Nilai mode terang sama persis dengan warna sebelumnya. Modifier opasitas (bg-c1/70 dst.) tetap jalan.
            colors: {
                brand: { DEFAULT: v('brand'), hover: v('brand-hover'), soft: v('brand-soft'), ring: v('brand-ring') },
                ink: { DEFAULT: v('ink'), muted: v('ink-muted'), faint: v('ink-faint') },
                line: { DEFAULT: v('line'), strong: v('line-strong') },
                canvas: v('canvas'),
                surface: v('surface'),
                c1: v('c1'),
                c2: v('c2'),
                c3: v('c3'),
                c4: v('c4'),
                c5: v('c5'),
                ok: { DEFAULT: v('ok'), bg: v('ok-bg') },
                warn: { DEFAULT: v('warn'), bg: v('warn-bg') },
                bad: { DEFAULT: v('bad'), bg: v('bad-bg') },
                info: { DEFAULT: v('info'), bg: v('info-bg') },
            },
            borderRadius: {
                ctl: '0.5rem',
                panel: '0.75rem',
            },
            letterSpacing: {
                title: '-0.02em',
            },
        },
    },

    plugins: [forms],
};
