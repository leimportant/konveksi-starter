// public/service-worker.js

// Skip waiting to immediately activate the service worker
self.addEventListener('install', (event) => {
  console.log('[Service Worker] Installed');
  self.skipWaiting();
});

// Claim control so the service worker takes control immediately
self.addEventListener('activate', (event) => {
  console.log('[Service Worker] Activated');
  event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
    console.log('[Service Worker] Push Received');

    let notificationData = {
        title: 'Default Title',
        body: 'Default message',
        icon: '/icon.png',
        badge: '/badge.png',
        data: {}
    };

    try {
        if (event.data) {
            notificationData = event.data.json();
        }
    } catch (e) {
        console.warn('[Service Worker] Push event data error:', e);
        // Try parsing as text if JSON fails
        try {
            const text = event.data.text();
            notificationData.body = text;
        } catch (textError) {
            console.error('[Service Worker] Could not parse push data:', textError);
        }
    }

    const options = {
        body: notificationData.body,
        icon: notificationData.icon,
        badge: notificationData.badge,
        data: notificationData.data,
        vibrate: [100, 50, 100],
        actions: [
            {
                action: 'open',
                title: 'Open'
            }
        ]
    };

    console.log('[Service Worker] Notification options:', options);

    event.waitUntil(
        self.registration.showNotification(notificationData.title, options)
    );
});

self.addEventListener('notificationclick', (event) => {
    console.log('[Service Worker] Notification click received');

    event.notification.close();

    if (event.action === 'open' && event.notification.data.url) {
        clients.openWindow(event.notification.data.url);
    }
});