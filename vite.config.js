import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { nativephpMobile, nativephpHotFile } from './vendor/nativephp/mobile/resources/js/vite-plugin.js';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            hotFile: nativephpHotFile(),
        }),
        tailwindcss(),
        nativephpMobile(),
    ],
    build: {
        chunkSizeWarningLimit: 1800,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules/leaflet')) {
                        return 'leaflet';
                    }
                    if (id.includes('node_modules/laravel-echo') || id.includes('node_modules/pusher-js')) {
                        return 'echo';
                    }
                },
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
