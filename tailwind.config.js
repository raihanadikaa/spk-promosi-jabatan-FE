import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** Token warna & tipografi TalentRank */
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
            colors: {
                brand: { DEFAULT: '#2563EB', hover: '#1D4ED8', soft: '#EFF6FF', ring: '#60A5FA' },
                ink: { DEFAULT: '#1E293B', muted: '#64748B', faint: '#94A3B8' },
                line: { DEFAULT: '#E2E8F0', strong: '#CBD5E1' },
                canvas: '#F8FAFC',
                surface: '#FFFFFF',
                c1: '#3F6E9A',
                c2: '#21877A',
                c3: '#C08A1E',
                c4: '#86549B',
                c5: '#5F8F4E',
                ok: { DEFAULT: '#0F766E', bg: '#CCFBF1' },
                warn: { DEFAULT: '#B45309', bg: '#FEF3C7' },
                bad: { DEFAULT: '#B91C1C', bg: '#FEE2E2' },
                info: { DEFAULT: '#475569', bg: '#F1F5F9' },
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
