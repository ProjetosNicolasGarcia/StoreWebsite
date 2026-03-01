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
        // 0.0.0.0 força o Vite a expor o servidor na rede, contornando loops locais bloqueados
        host: '0.0.0.0', 
        port: 5173,
        hmr: {
            // Diz para o navegador que o WebSocket está exatamente onde o site está rodando
            host: 'localhost',
        },
        watch: {
            ignored: ['**/storage/framework/views/**', '**/vendor/**'],
        },
    },
});