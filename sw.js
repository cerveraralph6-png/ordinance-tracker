const CACHE_NAME = 'bch-offline-v1';
const OFFLINE_URL = 'offline.html';

// Install: Cache the offline page
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll([
                OFFLINE_URL,
                'Imag3s/baguio-logo-gov.png' // Cache logo too
            ]);
        })
    );
});

// Fetch: If network fails, show offline page
self.addEventListener('fetch', (event) => {
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(() => {
                return caches.match(OFFLINE_URL);
            })
        );
    }
});