import {fileURLToPath, URL} from 'node:url';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import {defineConfig} from 'vite';
import svgVuePlugin from './scripts/vite-svg-vue-plugin.mjs';

export default defineConfig({
    plugins: [
        svgVuePlugin(),
        laravel({
            input: [
                'resources/js/apps/main/index.js',
                'resources/sass/main.scss',
            ],
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
                // Bootstrap 5.3 still uses APIs deprecated by Dart Sass. Keep
                // dependency warnings quiet while application Sass remains
                // protected by the Sass architecture tests.
                quietDeps: true,
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/**', '**/public/build/**'],
        },
    },
    build: {
        // Source maps must not be published with production assets. If private
        // error monitoring is added later, upload maps there during deployment.
        sourcemap: false,
        // The only deliberately large chunk is the lazy-loaded Video.js
        // player. Project-specific raw and gzip budgets are enforced after
        // every production build by check-frontend-bundle.mjs.
        chunkSizeWarningLimit: 650,
        rolldownOptions: {
            checks: {
                // This host-dependent timing heuristic is noisy in CI. Bundle
                // size and correctness are enforced by deterministic checks.
                pluginTimings: false,
            },
        },
    },
});
