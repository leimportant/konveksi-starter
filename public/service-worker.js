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


self.addEventListener('push', event => {
    try {
        const data = event.data.json();

        // It's a good practice to validate the payload.
        if (!data || !data.title || !data.body || !data.url) {
            console.error('Push message is missing required data.', data);
            return;
        }

        const options = {
            body: data.body,
            icon: '/icon.png', // Make sure this icon exists in your public folder.
            badge: '/badge.png', // A monochrome icon for the status bar (optional).
            data: {
                url: data.url
            }
        };

        event.waitUntil(
            self.registration.showNotification(data.title, options)
        );
    } catch (e) {
        console.error('Error processing push event:', e);
    }
});

self.addEventListener('notificationclick', event => {
    event.notification.close();

    // Use new URL to handle relative URLs from the push payload.
    const urlToOpen = new URL(event.notification.data.url, self.location.origin).href;

    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then(windowClients => {
            // Check if a window with the target URL is already open.
            const matchingClient = windowClients.find(client => client.url === urlToOpen);

            // If so, focus it. Otherwise, open a new window.
            return matchingClient ? matchingClient.focus() : clients.openWindow(urlToOpen);
        })
    );
});

