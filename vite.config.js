import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite'
import autoprefixer from 'autoprefixer';

export default defineConfig({
    server: {
        host: '0.0.0.0', // Accept all local domains (needed for multiple local domains)
        port: 8000,
        strictPort: true,
        origin: 'http://kabba.local:8000', // Base origin (choose frontend or admin)
        cors: true,
        hmr: {
            host: 'kabba.local', // Can be switched to admin.kabba.local if developing admin
        },
        // Optional if you're using HTTPS
        // https: {
        //     key: fs.readFileSync('/path/to/key.pem'),
        //     cert: fs.readFileSync('/path/to/cert.pem'),
        // },
    },
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                // Default
                'resources/css/app.css',
                'resources/js/app.js',

                // Admin
                'resources/admin/css/app.css',
                'resources/admin/js/app.js',
            ],
            refresh: true,
        }),
    ],
});
