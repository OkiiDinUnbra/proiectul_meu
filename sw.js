const CACHE_NAME = 'braila-pwa-v1';
const urlsToCache = [
    './index.php',
    './evenimente.php',
    './transport.php',
    './ghid.php'
];

// Instalarea Service Worker-ului
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Cache deschis cu succes');
                return cache.addAll(urlsToCache);
            })
    );
});

// Interceptarea cererilor (Strategie: Network First, Fallback to Cache)
self.addEventListener('fetch', event => {
    event.respondWith(
        fetch(event.request).catch(() => {
            return caches.match(event.request);
        })
    );
});

// Curățarea memoriei cache vechi
self.addEventListener('activate', event => {
    const cacheWhitelist = [CACHE_NAME];
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});