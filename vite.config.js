import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/sass/auth.scss',
                'resources/js/app.js',
                'resources/js/auth.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) return;
                    if (id.includes('fullcalendar')) return 'calendar';
                    if (id.includes('chart.js')) return 'charts';
                    if (id.includes('datatables') || id.includes('jquery')) return 'tables';
                    if (id.includes('sweetalert2')) return 'alerts';
                    if (id.includes('bootstrap') || id.includes('@popperjs')) return 'bootstrap';
                    if (id.includes('axios')) return 'http';

                    return 'vendor';
                },
            },
        },
    },
});
