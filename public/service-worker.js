const CACHE_NAME = 'aninkafashion-cache-v2';
const urlsToCache = [
    '/',
    '/index.php',
    '/images/icons/icon-192x192-2.svg',
    '/images/icons/icon-512x512-2.svg',
    '/manifest.json'
];

// ✅ Install - Cache files
self.addEventListener('install', event => {
    console.log('[SW] Installing...');
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            console.log('[SW] Caching assets');
            return cache.addAll(urlsToCache);
        })
    );
    self.skipWaiting(); // force activate immediately
});

// ✅ Fetch - Serve from cache, fallback to network
// self.addEventListener('fetch', event => {
//     event.respondWith(
//         caches.match(event.request).then(response => {
//             return response || fetch(event.request);
//         }).catch(err => {
//             console.error('[SW] Fetch failed:', err);
//         })
//     );
// });

// ✅ Activate - Clean old cache
self.addEventListener('activate', event => {
    console.log('[SW] Activating...');
    const cacheWhitelist = [CACHE_NAME];
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (!cacheWhitelist.includes(cacheName)) {
                        console.log('[SW] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// ✅ Push Notification - Safe & compatible for all platforms
self.addEventListener('push', event => {
    console.log('[SW] Push event received');
    
    if (!event.data) {
        console.error('[SW] Push event has no data');
        return;
    }

    let data = {};
    try {
        data = event.data.json();
        console.log('[SW] Push data received:', data);
    } catch (e) {
        // Try text format as fallback
        try {
            const textData = event.data.text();
            console.log('[SW] Push text data:', textData);
            try {
                data = JSON.parse(textData);
            } catch (jsonError) {
                data = { title: 'Notification', body: textData };
            }
        } catch (textError) {
            console.error('[SW] Push data parse error:', e, textError);
            return;
        }
    }

    // Extract notification data with fallbacks
    const title = data.title || 'New Notification';
    const body = data.body || 'You have a new message';
    const icon = data.icon || '/images/icons/icon-192x192-2.svg';
    const badge = data.badge || '/images/icons/icon-192x192-2.svg';
    const url = (data.data && data.data.url) || data.url || '/';
    const timestamp = data.timestamp || Date.now();
    const tag = data.tag || 'vue-starter-notification-' + timestamp;
    
    // Log the notification details
    console.log('[SW] Preparing notification:', {
        title, body, url, tag, timestamp
    });
    
    const options = {
        body: body,
        icon: icon,
        badge: badge,
        // Vibration pattern [vibrate, pause, vibrate, ...]
        vibrate: [100, 50, 100, 50, 100],
        // Store data for when user clicks notification
        data: {
            url: url,
            timestamp: timestamp,
            id: data.id || `notification-${Date.now()}`
        },
        // Required for Android
        actions: [
            {
                action: 'open',
                title: 'View'
            },
            {
                action: 'dismiss',
                title: 'Dismiss'
            }
        ],
        // For iOS support
        renotify: true,
        tag: tag,
        // Require interaction prevents auto-dismissal on some platforms
        requireInteraction: true,
        // Show timestamp
        timestamp: timestamp
    };

    console.log('[SW] Showing notification with options:', options);
    
    event.waitUntil(
        self.registration.showNotification(title, options)
            .then(() => console.log('[SW] Notification shown successfully'))
            .catch(err => console.error('[SW] Error showing notification:', err))
    );
});

// ✅ Click on notification - Open/focus tab - Enhanced for all platforms
self.addEventListener('notificationclick', event => {
    console.log('[SW] Notification clicked', event);
    
    // Close the notification
    event.notification.close();
    
    // Get the action (if any) and notification data
    const action = event.action;
    const notificationData = event.notification.data || {};
    const targetUrl = notificationData.url || '/';
    const notificationId = notificationData.id || 'unknown';
    
    console.log('[SW] Notification click - Action:', action, 'URL:', targetUrl, 'ID:', notificationId);
    
    // If the action is dismiss, just close the notification without opening a window
    if (action === 'dismiss') {
        console.log('[SW] User dismissed notification:', notificationId);
        return;
    }
    
    // For 'open' action or default click, open/focus the URL
    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then(clientList => {
            // Try to find an exact URL match first
            let exactMatch = null;
            let anyClient = null;
            
            for (let client of clientList) {
                // Save reference to any client for fallback
                if (!anyClient && 'focus' in client) {
                    anyClient = client;
                }
                
                // Check for exact URL match
                if (client.url === targetUrl && 'focus' in client) {
                    exactMatch = client;
                    break;
                }
            }
            
            // If we found an exact match, focus it
            if (exactMatch) {
                console.log('[SW] Focusing existing client with exact URL match');
                return exactMatch.focus();
            }
            
            // If we have any client, focus it and navigate
            if (anyClient) {
                console.log('[SW] Focusing existing client and navigating');
                return anyClient.focus().then(() => {
                    return anyClient.navigate(targetUrl);
                });
            }
            
            // If no existing tab found, open a new one
            if (clients.openWindow) {
                console.log('[SW] Opening new window to:', targetUrl);
                return clients.openWindow(targetUrl).catch(err => {
                    console.error('[SW] Error opening window:', err);
                });
            }
        }).catch(err => {
            console.error('[SW] Error handling notification click:', err);
        })
    );
});

// ✅ Handle notification close event
self.addEventListener('notificationclose', event => {
    console.log('[SW] Notification closed', event);
});
