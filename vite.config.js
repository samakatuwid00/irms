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
                'resources/js/add-print-resource.js',
                'resources/js/add-print-acquisition.js',
                'resources/js/add-nonprint-resource.js',
                'resources/js/add-nonprint-acquisition.js',
                'resources/js/edit-resource-index.js',
                'resources/js/view-print-modal.js',
                'resources/js/view-nonprint-modal.js',
                'resources/js/register.js',
                'resources/js/login.js',
                'resources/js/htmx.js',
                'resources/js/charts/availability.js',
                'resources/js/charts/bosy.js',
                'resources/js/charts/exdef.js',
                'resources/js/charts/heatmap.js',
                'resources/js/charts/lr.js',
                'resources/js/charts/ratio.js',
                'resources/js/charts/visualization.js',
                'resources/js/privacy-modal.js',
                'resources/css/bosy/bosy.css',

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
