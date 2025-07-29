import axios from 'axios';

// Configure axios with performance optimizations
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.timeout = 10000; // 10 second timeout
window.axios.defaults.headers.common['Accept'] = 'application/json';

// Add request interceptor for performance monitoring
axios.interceptors.request.use(
    config => {
        config.metadata = { startTime: new Date() };
        return config;
    },
    error => Promise.reject(error)
);

// Add response interceptor for performance monitoring
axios.interceptors.response.use(
    response => {
        if (response.config.metadata) {
            response.config.metadata.endTime = new Date();
            response.duration = response.config.metadata.endTime - response.config.metadata.startTime;
            
            // Log slow requests in development
            if (process.env.NODE_ENV === 'development' && response.duration > 2000) {
                console.warn(`Slow API request: ${response.config.url} took ${response.duration}ms`);
            }
        }
        return response;
    },
    error => {
        if (error.response && error.response.status === 429) {
            console.warn('Rate limit exceeded. Please try again later.');
        }
        return Promise.reject(error);
    }
);

// CSRF token setup for Laravel
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found');
}
