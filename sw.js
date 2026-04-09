/**
 * BuildFlow ERP - Service Worker
 * 
 * Handles caching, offline functionality, and background sync.
 * Version-controlled for easy updates.
 */

const CACHE_VERSION = 'v1.1.4';
const CACHE_NAME = 'buildflow-cache-v1.1.4';
const VERSION = '1.1.4';
const STATIC_CACHE = `buildflow-static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `buildflow-dynamic-${CACHE_VERSION}`;
const API_CACHE = `buildflow-api-${CACHE_VERSION}`;

// Assets to pre-cache (App Shell)
const PRECACHE_ASSETS = [
    '/',
    '/offline.html',
    '/assets/css/main.css',
    '/assets/js/app.js',
    '/assets/js/notifications.js',
    '/assets/js/update-service.js'
];

// API endpoints to cache
const CACHEABLE_API_PATTERNS = [
    /\/api\/projects\?/,
    /\/api\/clients\?/,
    /\/api\/dashboard/,
    /\/api\/employees/
];

// =====================================================
// INSTALL EVENT
// =====================================================

self.addEventListener('install', (event) => {
    console.log('[ServiceWorker] Installing version:', CACHE_VERSION);

    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => {
                console.log('[ServiceWorker] Pre-caching app shell');
                return cache.addAll(PRECACHE_ASSETS);
            })
            .then(() => {
                // Skip waiting to activate immediately
                return self.skipWaiting();
            })
            .catch((error) => {
                console.error('[ServiceWorker] Pre-cache failed:', error);
            })
    );
});

// =====================================================
// ACTIVATE EVENT
// =====================================================

self.addEventListener('activate', (event) => {
    console.log('[ServiceWorker] Activating version:', CACHE_VERSION);

    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => {
                            // Delete old cache versions
                            return name.startsWith('buildflow-') &&
                                !name.includes(CACHE_VERSION);
                        })
                        .map((name) => {
                            console.log('[ServiceWorker] Deleting old cache:', name);
                            return caches.delete(name);
                        })
                );
            })
            .then(() => {
                // Take control of all clients immediately
                return self.clients.claim();
            })
    );
});

// =====================================================
// FETCH EVENT
// =====================================================

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Skip cross-origin requests
    if (url.origin !== location.origin) {
        return;
    }

    // Skip version API - always fetch fresh to check for updates
    if (url.pathname === '/api/version') {
        return;
    }

    // API requests: Network-first with cache fallback
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(networkFirstStrategy(request, API_CACHE));
        return;
    }

    // Static assets: Cache-first (CSS, JS, images)
    if (isStaticAsset(url.pathname)) {
        event.respondWith(cacheFirstStrategy(request, STATIC_CACHE));
        return;
    }

    // HTML pages: CACHE-FIRST for version isolation
    // Users stay on cached version until they accept update
    if (request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(cacheFirstWithFallback(request, DYNAMIC_CACHE));
        return;
    }

    // Default: Cache-first
    event.respondWith(cacheFirstStrategy(request, DYNAMIC_CACHE));
});

// =====================================================
// CACHING STRATEGIES
// =====================================================

/**
 * Cache-first strategy - Used for static assets
 */
async function cacheFirstStrategy(request, cacheName) {
    const cached = await caches.match(request);
    if (cached) {
        return cached;
    }

    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        console.error('[ServiceWorker] Cache-first fetch failed:', error);
        throw error;
    }
}

/**
 * Network-first strategy - Used for API data
 */
async function networkFirstStrategy(request, cacheName) {
    try {
        const response = await fetch(request);
        if (response.ok && isCacheableApi(request.url)) {
            const cache = await caches.open(cacheName);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        console.log('[ServiceWorker] Network failed, trying cache');
        const cached = await caches.match(request);
        if (cached) {
            return cached;
        }
        throw error;
    }
}

/**
 * Network-first with offline page fallback - Used for HTML
 */
async function networkFirstWithOffline(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        console.log('[ServiceWorker] Serving offline page');
        const cached = await caches.match(request);
        if (cached) {
            return cached;
        }
        return caches.match('/offline.html');
    }
}

/**
 * Cache-first with network fallback - Used for version isolation
 * Users stay on cached version until they explicitly update
 */
async function cacheFirstWithFallback(request, cacheName) {
    const cached = await caches.match(request);

    if (cached) {
        console.log('[ServiceWorker] Serving cached page (version isolated):', request.url);
        return cached;
    }

    // First visit - fetch from network and cache
    try {
        console.log('[ServiceWorker] First visit, caching page:', request.url);
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        console.log('[ServiceWorker] Offline, showing offline page');
        return caches.match('/offline.html');
    }
}

/**
 * Stale-while-revalidate strategy
 */
async function staleWhileRevalidate(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);

    const fetchPromise = fetch(request)
        .then((response) => {
            if (response.ok) {
                cache.put(request, response.clone());
            }
            return response;
        })
        .catch(() => cached);

    return cached || fetchPromise;
}

// =====================================================
// HELPER FUNCTIONS
// =====================================================

function isStaticAsset(pathname) {
    const staticExtensions = ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.woff', '.woff2', '.ttf'];
    return staticExtensions.some(ext => pathname.endsWith(ext));
}

function isCacheableApi(url) {
    return CACHEABLE_API_PATTERNS.some(pattern => pattern.test(url));
}

// =====================================================
// BACKGROUND SYNC
// =====================================================

self.addEventListener('sync', (event) => {
    console.log('[ServiceWorker] Background sync:', event.tag);

    if (event.tag === 'sync-pending-actions') {
        event.waitUntil(syncPendingActions());
    }
});

async function syncPendingActions() {
    // Will be implemented with IndexedDB queue
    console.log('[ServiceWorker] Syncing pending actions...');
}

// =====================================================
// PUSH NOTIFICATIONS
// =====================================================

self.addEventListener('push', (event) => {
    console.log('[ServiceWorker] Push received');

    let data = {
        title: 'Cash Flow',
        body: 'You have a new notification'
    };

    if (event.data) {
        try {
            data = { ...data, ...event.data.json() };
        } catch (e) {
            data.body = event.data.text();
        }
    }

    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: data.icon,
            badge: data.badge,
            tag: data.tag || 'default',
            data: data.data || {},
            actions: data.actions || [],
            requireInteraction: data.requireInteraction || false
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    console.log('[ServiceWorker] Notification clicked');

    event.notification.close();

    const urlToOpen = event.notification.data?.url || '/dashboard';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Check if app is already open
                for (const client of clientList) {
                    if (client.url.includes(self.location.origin) && 'focus' in client) {
                        client.navigate(urlToOpen);
                        return client.focus();
                    }
                }
                // Open new window
                return clients.openWindow(urlToOpen);
            })
    );
});

// =====================================================
// MESSAGE HANDLING
// =====================================================

self.addEventListener('message', (event) => {
    console.log('[ServiceWorker] Message received:', event.data);

    if (event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }

    if (event.data.type === 'CACHE_URLS') {
        event.waitUntil(
            caches.open(DYNAMIC_CACHE)
                .then((cache) => cache.addAll(event.data.urls))
        );
    }

    if (event.data.type === 'CLEAR_CACHE') {
        event.waitUntil(
            caches.keys().then((names) =>
                Promise.all(names.map((name) => caches.delete(name)))
            )
        );
    }
});

console.log('[ServiceWorker] Script loaded, version:', CACHE_VERSION);
