const CACHE_NAME = 'rentsmart-cache-v5';
const BASE = (self.registration && self.registration.scope ? self.registration.scope : '/').replace(/\/$/, '');
const ASSETS = [
  BASE + '/',
  BASE + '/index.php',
  BASE + '/public/assets/css/style.css',
  BASE + '/public/assets/js/app.js',
  BASE + '/manifest.php'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(async (cache) => {
        for (const url of ASSETS) {
          try {
            const u = new URL(url, self.location.origin);
            // Do not cache any non-http(s) schemes or extension resources
            if ((u.protocol === 'http:' || u.protocol === 'https:') && !u.protocol.startsWith('chrome-extension')) {
              await cache.add(u.toString());
            }
          } catch (e) {
            // ignore invalid or unsupported schemes
          }
        }
      })
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.map((key) => key !== CACHE_NAME && caches.delete(key)))).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;

  let url;
  try {
    url = new URL(request.url);
  } catch (e) {
    return;
  }

  if (url.protocol !== 'http:' && url.protocol !== 'https:') return;
  if (url.origin !== self.location.origin) return;

  const isDocumentRequest = request.mode === 'navigate' || request.destination === 'document';
  if (isDocumentRequest) {
    event.respondWith(
      fetch(new Request(request, { cache: 'no-store' }))
        .then((networkResponse) => {
          if (networkResponse && networkResponse.ok) {
            const copy = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(request, copy)).catch(() => {});
          }
          return networkResponse;
        })
        .catch(() => caches.match(request).then((cached) => cached || caches.match(BASE + '/')))
    );
    return;
  }

  const isStaticAsset = ['style', 'script', 'image', 'font', 'manifest'].includes(request.destination);
  if (!isStaticAsset) {
    return;
  }

  event.respondWith(
    caches.match(request).then((cached) => {
      const fetchPromise = fetch(request)
        .then((networkResponse) => {
          if (networkResponse && networkResponse.ok) {
            const copy = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(request, copy)).catch(() => {});
          }
          return networkResponse;
        })
        .catch(() => cached);
      return cached || fetchPromise;
    })
  );
});

self.addEventListener('push', function(event) {
  try {
    const data = event && event.data ? event.data.json() : {};
    const title = (data && data.title) ? String(data.title) : 'RentSmart';
    const body = (data && data.body) ? String(data.body) : '';
    const link = (data && data.link) ? String(data.link) : '';
    const options = {
      body: body,
      icon: BASE + '/icon.php?size=192',
      badge: BASE + '/icon.php?size=192',
      data: { link: link }
    };
    event.waitUntil(self.registration.showNotification(title, options));
  } catch (e) {
  }
});

self.addEventListener('notificationclick', function(event) {
  try {
    event.notification.close();
    const link = event.notification && event.notification.data ? event.notification.data.link : '';
    if (!link) return;
    const url = new URL(link, self.location.origin).toString();
    event.waitUntil(
      self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(clientList) {
        for (const client of clientList) {
          try {
            if (client.url === url && 'focus' in client) {
              return client.focus();
            }
          } catch (e) {}
        }
        if (self.clients.openWindow) {
          return self.clients.openWindow(url);
        }
      })
    );
  } catch (e) {
  }
});
