const CACHE_NAME = 'rentsmart-cache-v4';
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
  // Ignore non-http(s) schemes (e.g., chrome-extension)
  try {
    const url = new URL(request.url);
    if (url.protocol !== 'http:' && url.protocol !== 'https:') {
      return; // let the browser handle it
    }
  } catch (e) {
    return;
  }
  event.respondWith(
    caches.match(request).then((cached) => {
      const fetchPromise = fetch(request)
        .then((networkResponse) => {
          // Only cache successful same-origin GET responses
          try {
            const url = new URL(request.url);
            if ((url.protocol === 'http:' || url.protocol === 'https:') && url.origin === self.location.origin && networkResponse && networkResponse.ok) {
              const copy = networkResponse.clone();
              caches.open(CACHE_NAME).then((cache) => cache.put(request, copy)).catch(() => {});
            }
          } catch (e) {}
          return networkResponse;
        })
        .catch(() => cached);
      return cached || fetchPromise;
    })
  );
});


