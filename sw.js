/**
 * WallacePOS Service Worker
 * Replaces deprecated applicationCache with modern Service Worker + Cache API
 */

const CACHE_NAME = 'wpos-cache-v1';

// Core assets that should always be cached
const CORE_ASSETS = [
    '/',
    '/index.html',
    '/kitchen/',
    '/kitchen/index.html'
];

// Assets that should be excluded from caching (dynamic/API endpoints)
const EXCLUDE_PATTERNS = [
    /\/api\//,
    /\/wpos\.appcache/,
    /\.(php|asp|aspx|jsp)(\?.*)?$/
];

// File types that should be cached
const CACHEABLE_EXTENSIONS = [
    '.js', '.css', '.html', '.htm',
    '.woff', '.woff2', '.ttf', '.eot', '.svg',
    '.png', '.jpg', '.jpeg', '.gif', '.ico',
    '.json', '.xml'
];

// Check if a URL should be cached
function shouldCache(url) {
    const urlObj = new URL(url);
    
    // Skip excluded patterns
    if (EXCLUDE_PATTERNS.some(pattern => pattern.test(urlObj.pathname))) {
        return false;
    }
    
    // Cache if it's a core asset
    if (CORE_ASSETS.includes(urlObj.pathname)) {
        return true;
    }
    
    // Cache if it's in the assets directory
    if (urlObj.pathname.startsWith('/assets/')) {
        return true;
    }
    
    // Cache if it has a cacheable extension
    const hasExtension = CACHEABLE_EXTENSIONS.some(ext => 
        urlObj.pathname.toLowerCase().endsWith(ext)
    );
    
    return hasExtension;
}

// Install event - cache core assets
self.addEventListener('install', event => {
    console.log('Service Worker installing...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Caching core assets...');
                return cache.addAll(CORE_ASSETS);
            })
            .then(() => {
                console.log('Service Worker installed');
                return self.skipWaiting();
            })
            .catch(error => {
                console.error('Service Worker installation failed:', error);
            })
    );
});

// Activate event - cleanup old caches
self.addEventListener('activate', event => {
    console.log('Service Worker activating...');
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== CACHE_NAME) {
                            console.log('Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('Service Worker activated');
                return self.clients.claim();
            })
    );
});

// Fetch event - serve from cache with network fallback
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);
    
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }
    
    // Skip if shouldn't be cached
    if (!shouldCache(event.request.url)) {
        return;
    }
    
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Return cached version if available
                if (response) {
                    // For assets, also try to update cache in background
                    if (url.pathname.startsWith('/assets/')) {
                        fetch(event.request)
                            .then(networkResponse => {
                                if (networkResponse && networkResponse.status === 200) {
                                    caches.open(CACHE_NAME)
                                        .then(cache => cache.put(event.request, networkResponse.clone()));
                                }
                            })
                            .catch(() => {}); // Ignore network errors
                    }
                    return response;
                }
                
                // Otherwise fetch from network
                return fetch(event.request)
                    .then(response => {
                        // Don't cache non-successful responses
                        if (!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }
                        
                        // Clone the response for caching
                        const responseToCache = response.clone();
                        
                        caches.open(CACHE_NAME)
                            .then(cache => {
                                cache.put(event.request, responseToCache);
                            });
                        
                        return response;
                    });
            })
            .catch(() => {
                // If both cache and network fail, return offline page for HTML requests
                if (event.request.headers.get('accept') && 
                    event.request.headers.get('accept').includes('text/html')) {
                    return caches.match('/index.html');
                }
            })
    );
});

// Message event - handle commands from main thread
self.addEventListener('message', event => {
    switch (event.data.action) {
        case 'skipWaiting':
            self.skipWaiting();
            break;
        case 'clearCache':
            caches.delete(CACHE_NAME)
                .then(() => {
                    console.log('Cache cleared');
                    event.ports[0].postMessage({ success: true });
                })
                .catch(error => {
                    console.error('Cache clear failed:', error);
                    event.ports[0].postMessage({ success: false, error });
                });
            break;
        default:
            console.log('Unknown message:', event.data);
    }
});