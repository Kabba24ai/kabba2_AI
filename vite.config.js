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
        // Disable strict file system checks
        fs: {
            strict: false,
            allow: [".."],
        },
        // Optional if you're using HTTPS
        // https: {
        //     key: fs.readFileSync('/path/to/key.pem'),
        //     cert: fs.readFileSync('/path/to/cert.pem'),
        // },
    },
    build: {
        sourcemap: false,
        // Disable source map generation completely
        minify: "terser",
        terserOptions: {
            compress: {
                drop_console: false,
                drop_debugger: true,
            },
            format: {
                comments: false, // This removes sourcemap comments
            },
        },
    },
    css: {
        devSourcemap: false,
        // Disable CSS source maps in development too
    },
    // Completely exclude problematic files from dependency optimization
    optimizeDeps: {
        exclude: [
            "resources/front/assets/js/flowbite.min.js",
            "resources/front/assets/js/swiper-bundle.min.js",
            "resources/front/assets/js/signature_pad.umd.min.js",
        ],
        include: [],
    },
    // Set log level to only show warnings and errors
    logLevel: "warn",
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

                // Frontend
                "resources/front/assets/css/all.css",
                "resources/front/assets/css/swiper-bundle.min.css",
                "resources/front/assets/js/tailwind-browser.js",
                "resources/front/assets/css/style.css",

                "resources/front/assets/js/jquery-3.6.0.min.js",
                "resources/front/assets/js/flowbite.min.js",
                "resources/front/assets/js/swiper-bundle.min.js",
                "resources/front/assets/js/signature_pad.umd.min.js",
               
                "resources/front/assets/js/custom.js",
            ],
            refresh: true,
        }),
    ],
});
