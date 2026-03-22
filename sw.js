/**
 * SVMS Service Worker
 * Cache-first for static assets, network-first for API/pages
 */

const CACHE_NAME = 'svms-v1.0.0';
const STATIC_ASSETS = [
    '/assets/css/style.css',
    '/assets/css/mobile.css',
    '/assets/js/app.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
    'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap'
];

// Install - cache static assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(STATIC_ASSETS);
        }).then(() => self.skipWaiting())
    );
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
    
    // API calls - network first
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(
            fetch(event.request).catch(() => {
                return new Response(JSON.stringify({ error: 'Offline' }), {
                    headers: { 'Content-Type': 'application/json' }
                });
            })
        );
        return;
    }
    
    // Static assets - cache first
    if (url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|woff2?|ttf|eot)$/) || 
        STATIC_ASSETS.includes(url.href)) {
        event.respondWith(
            caches.match(event.request).then(cached => {
                return cached || fetch(event.request).then(response => {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                    return response;
                });
            })
        );
        return;
    }
    
    // Pages - network first, fallback to cache
    event.respondWith(
        fetch(event.request).then(response => {
            const clone = response.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
            return response;
        }).catch(() => {
            return caches.match(event.request).then(cached => {
                return cached || caches.match('/index.php');
            });
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
        // Fetch from API (uses browser cookies automatically)
        promise = fetch('/api/get_latest_notification.php')
            .then(res => res.json())
            .catch(() => ({ title: 'SVMS', message: 'You have a new alert.' }));
    }

    event.waitUntil(
        promise.then(data => {
            const options = {
                body: data.message,
                icon: '/assets/images/logo-icon.png',
                badge: '/assets/images/logo-icon.png',
                vibrate: [100, 50, 100],
                data: {
                    url: data.link || '/notifications.php'
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
