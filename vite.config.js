import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import inertia from '@inertiajs/vite';
import ui from '@nuxt/ui/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/inertia.js',
            ],
            refresh: true,
            hotFile: "public/hot"
        }),
        inertia({
            ssr: {
                entry: "resources/js/ssr.js",
                host: "127.0.0.1"
            },
        }),
        vue(),
        tailwindcss(),
        ui({
            router: 'inertia'
        })
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
