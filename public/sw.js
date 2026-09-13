/* Nexicon ERP - Service Worker */
const VERSION = 'v1.0.0';
const PRECACHE = ['/manifest.json'];

const STATIC_CACHE = `nexicon-static-${VERSION}`;
const PAGE_CACHE = `nexicon-pages-${VERSION}`;

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then((cache) => cache.addAll(PRECACHE))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) =>
        Promise.all(
          keys
            .filter((key) => key.startsWith('nexicon-') && key !== STATIC_CACHE && key !== PAGE_CACHE)
            .map((key) => caches.delete(key))
        )
      )
      .then(() => self.clients.claim())
  );
});

// Aset statis & media → cache-first (offline-friendly)
const JS_CSS = /\.(?:js|css|woff2?|ttf|otf)$/i;
const MEDIA = /\.(?:png|jpe?g|gif|webp|svg|ico)$/i;
// Endpoint yang tidak boleh di-re-target hasil (Livewire), halaman portal & PDF verifikasi
const PAGE_ROUTES = /\/portal|customer-portal|invoice\/verify|delivery-order\/track/;
// Gateway mutlak (auth/CSRF/API) → jangan pernah cache
const GATEWAY = /\/login$|\/logout$|\/livewire|\/csrf|sanctum|\/attendance|\/api\/|accessible_picture|cash-register/;

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);
  if (url.origin !== self.location.origin) return; // cross-origin: biarkan browser

  // GET saja; method lain (POST, HEAD livewire) → network
  if (event.request.method !== 'GET') return;

  const path = url.pathname;

  // Manifest selalu fresh
  if (path === '/manifest.json') {
    event.respondWith(fetch(event.request));
    return;
  }

  if (GATEWAY.test(path)) return; // tidak sentuh sama sekali

  if (JS_CSS.test(path) || MEDIA.test(path)) {
    // Statis: cache-first
    event.respondWith(
      caches.match(event.request).then((cached) => {
        const network = fetch(event.request)
          .then((response) => {
            if (response && response.ok) {
              const copy = response.clone();
              caches.open(STATIC_CACHE).then((cache) => cache.put(event.request, copy));
            }
            return response;
          })
          .catch(() => cached);
        return cached || network;
      })
    );
    return;
  }

  // Navigasi (halaman) & route portal → NetworkFirst, streaming
  if (event.request.mode === 'navigate' || PAGE_ROUTES.test(path)) {
    event.respondWith(
      fetch(event.request)
        .then((response) => {
          if (response && response.ok) {
            const copy = response.clone();
            caches.open(PAGE_CACHE).then((cache) => cache.put(event.request, copy));
          }
          return response;
        })
        .catch(() => caches.match(event.request).then((cached) => cached || caches.match('/')))
    );
    return;
  }

  // Sisa GET Navigasi lain → network-only
  event.respondWith(fetch(event.request));
});

// Kirim pesan ke halaman: kapan APK/instal tersedia
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') self.skipWaiting();
});