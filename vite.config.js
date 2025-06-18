import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import autoprefixer from 'autoprefixer';

export default defineConfig(() => {
    const hmrHost = process.env.VITE_HMR_HOST || 'kabba.local';

    return {
        server: {
            host: '0.0.0.0',
            port: 8000,
            strictPort: true,
            origin: `http://${hmrHost}:8000`,
            cors: true,
            hmr: {
                host: hmrHost,
            },
            // fs: {
            //     strict: false,
            //     allow: [".."],
            // },
        },
        build: {
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
                "resources/front/assets/js/flowbite.min.js",
                "resources/front/assets/js/swiper-bundle.min.js",
                "resources/front/assets/js/signature_pad.umd.min.js",
            ],
            include: [],
        },
        //logLevel: "info",
        plugins: [
            tailwindcss(),
            laravel({
                input: [
                    'resources/css/app.css',
                    'resources/js/app.js',
                    'resources/admin/css/app.css',
                    'resources/admin/js/app.js',
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
    };
});
