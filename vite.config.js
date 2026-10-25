import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny, local } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // Aset khusus aplikasi internal (dashboard); tidak dimuat oleh halaman publik.
                'resources/css/dashboard.css',
                'resources/js/dashboard.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
                // Font dashboard internal, di-host sendiri (lihat resources/fonts).
                local('Inter', {
                    variants: [
                        { src: 'resources/fonts/inter-latin-400-normal.woff2', weight: 400 },
                        { src: 'resources/fonts/inter-latin-500-normal.woff2', weight: 500 },
                        { src: 'resources/fonts/inter-latin-600-normal.woff2', weight: 600 },
                    ],
                    fallbacks: ['ui-sans-serif', 'system-ui', 'sans-serif'],
                }),
                local('Plus Jakarta Sans', {
                    variants: [
                        { src: 'resources/fonts/plus-jakarta-sans-latin-500-normal.woff2', weight: 500 },
                        { src: 'resources/fonts/plus-jakarta-sans-latin-600-normal.woff2', weight: 600 },
                        { src: 'resources/fonts/plus-jakarta-sans-latin-700-normal.woff2', weight: 700 },
                    ],
                    fallbacks: ['ui-sans-serif', 'system-ui', 'sans-serif'],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
