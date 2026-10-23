import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    base: '/build/',
    plugins: [
        laravel({
            input: [
                'resources/css/app.scss',
                'resources/css/guest.scss',
                'resources/js/app.js',
                'resources/js/editor.js',
            ],
            refresh: true,
        }),
    ],
});
