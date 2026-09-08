import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    build: {
        // Performance optimizations
        minify: 'esbuild', // Fast minification
        target: 'es2015', // Better browser compatibility
        rollupOptions: {
            output: {
                // Code splitting for better loading performance
                manualChunks: {
                    vendor: ['axios'],
                    utils: ['bootstrap']
                },
                // Optimize asset names
                assetFileNames: (assetInfo) => {
                    const info = assetInfo.name.split('.');
                    const extType = info[info.length - 1];
                    if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(extType)) {
                        return `assets/images/[name]-[hash][extname]`;
                    }
                    if (/css/i.test(extType)) {
                        return `assets/css/[name]-[hash][extname]`;
                    }
                    return `assets/[name]-[hash][extname]`;
                },
                chunkFileNames: 'assets/js/[name]-[hash].js',
                entryFileNames: 'assets/js/[name]-[hash].js',
            },
        },
        // Optimize chunk size
        chunkSizeWarningLimit: 1000,
        // Enable source maps for debugging (disable in production)
        sourcemap: process.env.NODE_ENV !== 'production',
        // Asset inlining threshold
        assetsInlineLimit: 4096,
    },
    // CSS optimization
    css: {
        devSourcemap: true,
        preprocessorOptions: {
            scss: {
                additionalData: `@import "resources/scss/variables.scss";`,
            },
        },
    },
    // Development server optimization
    server: {
        hmr: {
            overlay: false
        },
        host: true,
    },
    // Resolve configuration
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
            '~': resolve(__dirname, 'resources'),
        },
    },
    // Optimize dependencies
    optimizeDeps: {
        include: ['axios', 'bootstrap'],
        exclude: ['@vite/client', '@vite/env'],
    },
});
