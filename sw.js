const CACHE_NAME = 'rentsmart-cache-v2';
const BASE = (self.registration && self.registration.scope ? self.registration.scope : '/').replace(/\/$/, '');
const ASSETS = [
  BASE + '/',
  BASE + '/public/assets/css/style.css',
  BASE + '/public/assets/js/app.js',
  BASE + '/manifest.php'
];

self.addEventListener('install', (event) => {
  event.waitUntil((async () => {
    try {
      const cache = await caches.open(CACHE_NAME);
      for (const url of ASSETS) {
        try { await cache.add(url); } catch (e) { /* ignore individual failures */ }
      }
    } catch (e) {
      // ignore
    }
    await self.skipWaiting();
  })());
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.map((key) => key !== CACHE_NAME && caches.delete(key)))).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;
  event.respondWith(
    caches.match(request).then((cached) => {
      const fetchPromise = fetch(request)
        .then((networkResponse) => {
          const copy = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
          return networkResponse;
        })
        .catch(() => cached);
      return cached || fetchPromise;
    })
  );
});


