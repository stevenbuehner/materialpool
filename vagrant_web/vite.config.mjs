import {fileURLToPath, URL} from 'node:url';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import {defineConfig} from 'vite';
import svgVuePlugin from './scripts/vite-svg-vue-plugin.mjs';

export default defineConfig({
    plugins: [
        svgVuePlugin(),
        laravel({
            input: ['resources/js/apps/main/index.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: false,
            },
        }),
    ],
    resolve: {
        extensions: ['.mjs', '.js', '.mts', '.ts', '.jsx', '.tsx', '.json', '.vue'],
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
            '@icons': fileURLToPath(new URL('./resources/icons', import.meta.url)),
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                loadPaths: [fileURLToPath(new URL('.', import.meta.url))],
            },
        },
    },
    build: {
        sourcemap: true,
    },
});
