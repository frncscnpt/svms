/**
 * SVMS Service Worker
 * Cache-first for static assets, network-first for API/pages
 */

const CACHE_NAME = 'svms-v1.0.2';

// Install - skip waiting immediately (no pre-caching)
self.addEventListener('install', event => {
    event.waitUntil(self.skipWaiting());
});

// Activate - clean old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch strategy
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);
    
    // Skip non-GET requests
    if (event.request.method !== 'GET') return;
    
    // Skip chrome-extension and other non-http(s) requests
    if (!url.protocol.startsWith('http')) return;
    
    // API calls - network first
    if (url.pathname.includes('/api/')) {
        event.respondWith(
            fetch(event.request)
                .catch(() => {
                    return new Response(JSON.stringify({ error: 'Offline' }), {
                        headers: { 'Content-Type': 'application/json' }
                    });
                })
        );
        return;
    }
    
    // Static assets - cache first, fallback to network
    if (url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|woff2?|ttf|eot|ico)$/)) {
        event.respondWith(
            caches.match(event.request)
                .then(cached => {
                    if (cached) return cached;
                    
                    return fetch(event.request)
                        .then(response => {
                            // Only cache successful responses
                            if (response && response.status === 200) {
                                const clone = response.clone();
                                caches.open(CACHE_NAME)
                                    .then(cache => cache.put(event.request, clone))
                                    .catch(() => {}); // Silently fail cache writes
                            }
                            return response;
                        })
                        .catch(() => {
                            // Return a fallback or just fail silently
                            return new Response('', { status: 404 });
                        });
                })
        );
        return;
    }
    
    // Pages - network first, fallback to cache
    event.respondWith(
        fetch(event.request)
            .then(response => {
                // Only cache successful responses
                if (response && response.status === 200) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME)
                        .then(cache => cache.put(event.request, clone))
                        .catch(() => {}); // Silently fail cache writes
                }
                return response;
            })
            .catch(() => {
                return caches.match(event.request)
                    .then(cached => cached || new Response('Offline', { status: 503 }));
            })
    );
});

// Push - show native notification
self.addEventListener('push', event => {
    // If we have data, use it. If not (standalone VAPID), fetch latest from API
    let promise;
    if (event.data && event.data.text()) {
        try {
            const data = event.data.json();
            promise = Promise.resolve(data);
        } catch (e) {
            promise = Promise.resolve({ title: 'SVMS', message: event.data.text() });
        }
    } else {
        // Fetch from API (uses browser cookies automatically) - use relative path
        const apiPath = self.registration.scope + 'api/get_latest_notification.php';
        promise = fetch(apiPath)
            .then(res => res.json())
            .catch(() => ({ title: 'SVMS', message: 'You have a new alert.' }));
    }

    event.waitUntil(
        promise.then(data => {
            const options = {
                body: data.message,
                icon: self.registration.scope + 'assets/img/logo.png',
                badge: self.registration.scope + 'assets/img/logo.png',
                vibrate: [100, 50, 100],
                data: {
                    url: data.link || (self.registration.scope + 'notifications.php')
                }
            };
            return self.registration.showNotification(data.title, options);
        })
    );
});

// Notification Click - open the app
self.addEventListener('notificationclick', event => {
    event.notification.close();
    const urlToOpen = event.notification.data.url;

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(windowClients => {
            // If a window is already open, focus it
            for (let i = 0; i < windowClients.length; i++) {
                const client = windowClients[i];
                if (client.url.includes(urlToOpen) && 'focus' in client) {
                    return client.focus();
                }
            }
            // Otherwise, open a new window
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});
