import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    // IPv4 eksplisit: alamat IPv6 [::1] tidak valid sebagai sumber di Content-Security-Policy.
    server: {
        host: '127.0.0.1',
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
