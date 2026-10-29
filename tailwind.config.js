import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** Token warna & tipografi — lihat DESIGN.md */
/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Support/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans Variable"', '"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
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
            colors: {
                brand: { DEFAULT: '#7A1F2B', hover: '#66192A', soft: '#F6ECEE', ring: '#B8616C' },
                ink: { DEFAULT: '#1A1D24', muted: '#5A6170', faint: '#8A909C' },
                line: { DEFAULT: '#E2E5EA', strong: '#CDD2D9' },
                canvas: '#F3F4F6',
                surface: '#FFFFFF',
                c1: '#3F6E9A',
                c2: '#21877A',
                c3: '#C08A1E',
                c4: '#86549B',
                c5: '#5F8F4E',
                ok: { DEFAULT: '#1D6B45', bg: '#E6F3EC' },
                warn: { DEFAULT: '#8A5A00', bg: '#FBF1DC' },
                bad: { DEFAULT: '#A42A22', bg: '#FBE9E7' },
                info: { DEFAULT: '#3B4250', bg: '#EEF0F3' },
            },
            borderRadius: {
                ctl: '6px',
                panel: '10px',
            },
            letterSpacing: {
                title: '-0.02em',
            },
        },
    },

    plugins: [forms],
};
