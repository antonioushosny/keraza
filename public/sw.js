const CACHE_NAME = 'keraza-store-v3';
const ASSETS_TO_CACHE = [
    '/',
    '/icon-192.png',
    '/icon-512.png',
    '/apple-touch-icon.png',
];

// Paths the SW should NEVER intercept
const BYPASS_PATTERNS = [
    '/storage/',
    '/admin',
    '/livewire',
    '/filament',
    '__clockwork',
];

function shouldBypass(url) {
    const path = new URL(url).pathname;
    return BYPASS_PATTERNS.some((p) => path.startsWith(p));
}

self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS_TO_CACHE))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then((names) =>
            Promise.all(
                names.filter((n) => n !== CACHE_NAME).map((n) => caches.delete(n))
            )
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (e) => {
    const { request } = e;

    // Only handle plain http/https GET requests
    if (!request.url.startsWith('http') || request.method !== 'GET') return;

    // Let admin, storage, livewire requests go directly to network
    if (shouldBypass(request.url)) return;

    e.respondWith(
        fetch(request)
            .then((response) => {
                // Only cache successful, basic (same-origin) responses
                if (response.ok && response.type === 'basic') {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                }
                return response;
            })
            .catch(() =>
                caches.match(request).then(
                    (cached) => cached || new Response('Offline', { status: 503, statusText: 'Service Unavailable' })
                )
            )
    );
});
