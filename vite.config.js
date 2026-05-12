import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    // ─── Docker HMR Support ───────────────────────────────
    // Agar Vite bisa diakses dari browser host saat berjalan di Docker
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'localhost',
            port: 5173,
        },
        watch: {
            // Polling diperlukan di Docker/WSL agar file changes terdeteksi
            usePolling: true,
            interval: 500,
        },
    },
    build: {
        rollupOptions: {
            output: {
                // Nama file tanpa hash acak agar tidak diblokir Brave Shields
                entryFileNames: 'assets/[name].js',
                chunkFileNames: 'assets/[name].js',
                assetFileNames: 'assets/[name].[ext]',
            },
        },
    },
});
