var CACHE_VERSION = 'vc-pwa-v2';
var PRECACHE = [
    './',
    './index.php',
    './offline.html',
    './css/style.css',
    './js/main.js',
    './manifest.webmanifest',
    './icons/icon-192.svg'
];

self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_VERSION).then(function(cache) {
            return cache.addAll(PRECACHE);
        }).then(function() {
            return self.skipWaiting();
        })
    );
});

self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys().then(function(keys) {
            return Promise.all(keys.map(function(key) {
                if (key !== CACHE_VERSION) {
                    return caches.delete(key);
                }
                return null;
            }));
        }).then(function() {
            return self.clients.claim();
        })
    );
});

self.addEventListener('fetch', function(event) {
    if (event.request.method !== 'GET') {
        return;
    }

    var url = new URL(event.request.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    if (url.pathname.indexOf('/api/') !== -1 || url.pathname.indexOf('/admin/') !== -1) {
        return;
    }

    event.respondWith(
        fetch(event.request).then(function(response) {
            if (response && response.status === 200 && response.type === 'basic') {
                var copy = response.clone();
                caches.open(CACHE_VERSION).then(function(cache) {
                    cache.put(event.request, copy);
                });
            }
            return response;
        }).catch(function() {
            return caches.match(event.request).then(function(cached) {
                if (cached) {
                    return cached;
                }
                if (event.request.mode === 'navigate') {
                    return caches.match('./offline.html');
                }
                return new Response('Offline', { status: 503, statusText: 'Offline' });
            });
        })
    );
});

self.addEventListener('push', function(event) {
    var data = { title: 'Village Connect', body: '', url: './', icon: './icons/icon-192.svg' };
    if (event.data) {
        try {
            var parsed = event.data.json();
            if (parsed.title) data.title = parsed.title;
            if (parsed.body) data.body = parsed.body;
            if (parsed.url) data.url = parsed.url;
            if (parsed.icon) data.icon = parsed.icon;
        } catch (e) {
            data.body = event.data.text();
        }
    }

    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: data.icon,
            badge: data.icon,
            data: { url: data.url }
        })
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    var target = './';
    if (event.notification.data && event.notification.data.url) {
        target = event.notification.data.url;
    }
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(list) {
            for (var i = 0; i < list.length; i++) {
                if (list[i].url.indexOf(target) !== -1 && 'focus' in list[i]) {
                    return list[i].focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(target);
            }
            return null;
        })
    );
});
