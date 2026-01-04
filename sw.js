// Service Worker for E-Laundry PWA
const CACHE_NAME = 'adin-laundry-v1.0.0';
const urlsToCache = [
    '/',
    '/index.php',
    '/assets/css/style.css',
    '/assets/css/admin.css',
    '/assets/js/script.js',
    '/assets/js/enhancements.js',
    '/assets/images/logo.png',
    '/manifest.json'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                return cache.addAll(urlsToCache);
            })
    );
});

self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Return cached version or fetch from network
                return response || fetch(event.request);
            })
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});