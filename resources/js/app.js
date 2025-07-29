import './bootstrap';
import { initPerformanceOptimizations } from './utils/performance';

// Initialize performance optimizations
const { monitor, cache } = initPerformanceOptimizations();

// Export for global access
window.performanceMonitor = monitor;
window.apiCache = cache;
