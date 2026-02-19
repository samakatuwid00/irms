import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/school-profile.js',
                'resources/js/region-profile.js',
                'resources/js/division-profile.js',
                'resources/js/district-profile.js',
                'resources/js/print-resources.js',
                'resources/js/nonprint-resources.js',
                'resources/js/add-resource-index.js',
                'resources/js/add-print-resource.js',
                'resources/js/add-nonprint-resource.js',
                'resources/js/edit-resource-index.js',
                'resources/js/edit-print-resource.js',
                'resources/js/edit-nonprint-resource.js',
                'resources/js/view-print-modal.js',
                'resources/js/view-nonprint-modal.js',
                'resources/js/register.js',
                'resources/js/login.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
