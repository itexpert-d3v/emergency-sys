const CACHE_VERSION = 'emergency-sys-v1';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const OFFLINE_URL = '/offline.html';

const PRIVATE_PREFIXES = ['/admin', '/track', '/queue', '/dashboard', '/live-map', '/profile', '/api'];

const shouldNeverCacheRequest = (request) => {
  if (request.method !== 'GET') return true;
  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return true;
  return PRIVATE_PREFIXES.some((prefix) => url.pathname === prefix || url.pathname.startsWith(`${prefix}/`));
};

self.addEventListener('install', (event) => {
  event.waitUntil((async () => {
    const cache = await caches.open(STATIC_CACHE);
    await cache.addAll([OFFLINE_URL, '/assets/sou.png', '/assets/icon-192.png', '/assets/icon-512.png', '/assets/apple-touch-icon.png']);
    await self.skipWaiting();
  })());
});

self.addEventListener('message', (event) => {
  if (event.data?.type === 'SKIP_WAITING') self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const names = await caches.keys();
    await Promise.all(names.filter((name) => name.startsWith('emergency-sys-') && name !== STATIC_CACHE).map((name) => caches.delete(name)));
    await self.clients.claim();
  })());
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (shouldNeverCacheRequest(request)) return;

  const url = new URL(request.url);
  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(async () => {
      const cache = await caches.open(STATIC_CACHE);
      return (await cache.match(OFFLINE_URL)) || Response.error();
    }));
    return;
  }

  if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/assets/')) {
    event.respondWith(caches.open(STATIC_CACHE).then(async (cache) => {
      const cached = await cache.match(request);
      const fresh = fetch(request).then(async (response) => {
        if (response.ok) await cache.put(request, response.clone());
        return response;
      }).catch(() => cached);
      return cached || fresh;
    }));
  }
});
