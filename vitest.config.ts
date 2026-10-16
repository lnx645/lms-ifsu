import { fileURLToPath, URL } from 'node:url';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import { defineConfig } from 'vite';

export default defineConfig({
    resolve: {
        conditions: ['browser'],
        alias: {
            '@': fileURLToPath(
                new URL('./resources/js', import.meta.url),
            ),
            $lib: fileURLToPath(
                new URL('./resources/js/lib', import.meta.url),
            ),
        },
    },
    plugins: [svelte()],
    test: {
        environment: 'jsdom',
        globals: true,
        setupFiles: ['./tests/frontend/setup.ts'],
        include: ['tests/frontend/**/*.test.ts'],
    },
});
