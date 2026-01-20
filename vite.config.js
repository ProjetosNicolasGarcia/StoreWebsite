import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: { 
        // Força o Vite a rodar em 127.0.0.1 para não conflitar com o DNS do Windows
        host: '127.0.0.1',
        hmr: {
            host: 'localhost',
        },
        watch: {
            // Ignora pastas pesadas para poupar CPU do Windows
            ignored: ['**/storage/framework/views/**', '**/vendor/**'],
        },
    },
});