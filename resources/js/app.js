import './bootstrap';

// Performance optimization: Lazy load non-critical modules
const loadNonCritical = () => {
    // Add any non-critical imports here
    // Example: import('./components/NonCriticalComponent');
};

// Performance optimization: Load non-critical modules after page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadNonCritical);
} else {
    loadNonCritical();
}

// Performance optimization: Service Worker registration for caching
if ('serviceWorker' in navigator && process.env.NODE_ENV === 'production') {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then((registration) => {
                console.log('SW registered: ', registration);
            })
            .catch((registrationError) => {
                console.log('SW registration failed: ', registrationError);
            });
    });
}

// Performance optimization: Preload critical resources
const preloadCriticalResources = () => {
    const criticalResources = [
        // Add paths to critical CSS/JS files
        // Example: '/assets/css/critical.css'
    ];

    criticalResources.forEach(resource => {
        const link = document.createElement('link');
        link.rel = 'preload';
        link.href = resource;
        link.as = resource.endsWith('.css') ? 'style' : 'script';
        document.head.appendChild(link);
    });
};

// Initialize performance optimizations
preloadCriticalResources();
