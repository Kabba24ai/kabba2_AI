import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import 'dotenv/config'; // Loads .env automatically

export default defineConfig(() => {
    const hmrHost = process.env.FRONT_DOMAIN  || 'kabba.local';
    const originProtocol = process.env.VITE_ORIGIN_PROTOCOL || 'https';

    return {
        server: {
            https: process.env.VITE_SSL_KEY && process.env.VITE_SSL_CERT
            ? {
                key: process.env.VITE_SSL_KEY,
                cert: process.env.VITE_SSL_CERT,
            }
            : false,
            host: '0.0.0.0',
            port: 8000,
            strictPort: true,
            origin: `${originProtocol}://${hmrHost}:8000`,
            cors: true,
            hmr: {
                host: hmrHost,
            },
            // fs: {
            //     strict: false,
            //     allow: [".."],
            // },
            watch: {
                ignored: [
                    '**/app/Http/**',
                    '**/storage/**',
                    '**/vendor/**',
                ],
            },
        },
        build: {
            rollupOptions: {
                external: ['tinymce']
            },
            sourcemap: false, // no source maps in production
            minify: "terser",
            terserOptions: {
                compress: {
                    drop_console: false, // change to true to remove console.logs
                    drop_debugger: true,
                },
                format: {
                    comments: false,
                },
            },
        },
        css: {
            devSourcemap: false, // no CSS maps in dev
        },
        optimizeDeps: {
            exclude: [
                'tinymce'
            ],

        },
        //logLevel: "info",
        plugins: [
            tailwindcss(),
            laravel({
                input: [
                    // Default
                    'resources/css/app.css',
                    'resources/js/app.js',

                    // If you have global/shared assets, list here
                    // 'resources/shared/css/shared.css',
                    // 'resources/shared/js/shared.js',

                    // Admin entry points
                    'resources/admin/css/app.css',
                    'resources/admin/js/app.js',
                    'resources/admin/js/tinymce.js',

                    // Front entry points
                    "resources/front/assets/css/app.css",
                    "resources/front/assets/js/app.js",
                    "resources/front/assets/js/products/product-details-page.js",

                    //public
                    "public/tinymce/skins/content/default/content.css"


                ],
                refresh: ['resources/views/**/*.php'],
            }),
        ],
    };
});
