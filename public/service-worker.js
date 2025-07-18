const CACHE_NAME = 'aninkafashion-cache-v2';

const urlsToCache = [
    '/',
    '/index.php',


    '/images/icons/icon-192x192-2.svg',
    '/images/icons/icon-512x512-2.svg',
    '/manifest.json'
];

self.addEventListener('install', event => {
    console.log('Service Worker: Installing...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Opened cache');
                return cache.addAll(urlsToCache);
            })
    );
});

self.addEventListener('fetch', event => {
    console.log('Service Worker: Fetching', event.request.url);
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                if (response) {
                    return response;
                }
                console.log('Service Worker: Fetching from network', event.request.url);
                return fetch(event.request);
            })
    );
});
self.addEventListener('activate', event => {
  console.log('Service Worker: Activating...');
  const cacheWhitelist = [CACHE_NAME];

  event.waitUntil(
    (async () => {
      const cacheNames = await caches.keys();
      await Promise.all(
        cacheNames.map(cacheName => {
          if (!cacheWhitelist.includes(cacheName)) {
            console.log(`Service Worker: Deleting old cache - ${cacheName}`);
            return caches.delete(cacheName);
          }
        })
      );
      // Ambil kendali atas halaman segera
      await self.clients.claim();
    })()
  );
});
