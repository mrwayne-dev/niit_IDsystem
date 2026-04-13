const CACHE_NAME    = 'niit-id-v1';
const STATIC_ASSETS = [
    '/',
    '/verify',
    '/assets/css/style.css',
    '/assets/css/bootstrap.css',
    '/assets/js/ui.js',
    '/assets/js/verify.js',
    '/assets/img/placeholder.png',
];

// Install: cache static assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(STATIC_ASSETS))
            .then(() => self.skipWaiting())
    );
});

// Activate: clean up old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
            )
        ).then(() => self.clients.claim())
    );
});

// Fetch: network-first for API calls, cache-first for static assets
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Always network-first for API and dynamic PHP pages
    if (url.pathname.includes('/backend/api/') || url.pathname.endsWith('.php')) {
        event.respondWith(
            fetch(event.request).catch(() =>
                new Response(
                    JSON.stringify({ success: false, message: 'You appear to be offline.' }),
                    { headers: { 'Content-Type': 'application/json' } }
                )
            )
        );
        return;
    }

    // Cache-first for static assets
    event.respondWith(
        caches.match(event.request).then(cached => cached || fetch(event.request))
    );
});
