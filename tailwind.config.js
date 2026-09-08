/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.js",
        "./resources/js/**/*.jsx",
        "./resources/js/**/*.ts",
        "./resources/js/**/*.tsx",
        "./resources/js/**/*.vue",
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', 'sans-serif'],
            },
            // Performance optimization: Reduce unused animations
            animation: {
                'spin-fast': 'spin 0.5s linear infinite',
                'pulse-fast': 'pulse 1s cubic-bezier(0.4, 0, 0.6, 1) infinite',
            },
            // Optimize transitions
            transitionDuration: {
                'fast': '150ms',
                'normal': '250ms',
                'slow': '350ms',
            },
            // Hardware acceleration utilities
            backdropBlur: {
                xs: '2px',
            },
        },
    },
    plugins: [
        // Add performance-focused plugins
        require('@tailwindcss/forms')({
            strategy: 'class', // Use class strategy for better performance
        }),
    ],
    // Performance optimizations
    experimental: {
        optimizeUniversalDefaults: true,
    },
    // Reduce CSS bundle size
    corePlugins: {
        // Disable unused plugins for better performance
        container: false,
        space: false,
        divideWidth: false,
        divideColor: false,
        divideStyle: false,
        divideOpacity: false,
    },
    // JIT mode configuration for faster builds
    mode: 'jit',
    // Purge unused styles aggressively
    purge: {
        enabled: process.env.NODE_ENV === 'production',
        content: [
            "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
            "./storage/framework/views/*.php",
            "./resources/views/**/*.blade.php",
            "./resources/js/**/*.js",
            "./resources/js/**/*.jsx",
            "./resources/js/**/*.ts",
            "./resources/js/**/*.tsx",
            "./resources/js/**/*.vue",
        ],
        options: {
            safelist: [
                // Keep essential classes that might be added dynamically
                'active',
                'disabled',
                'hidden',
                'show',
                'fade',
                'collapse',
                'collapsing',
            ],
        },
    },
};
