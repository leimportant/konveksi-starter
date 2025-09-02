import { defineStore } from 'pinia';
import axios from 'axios';

const VAPID_PUBLIC_KEY = import.meta.env.VITE_VAPID_PUBLIC_KEY || import.meta.env.VAPID_PUBLIC_KEY;

// Convert base64 string to Uint8Array for applicationServerKey
function urlBase64ToUint8Array(base64String: string) {
  if (!base64String) throw new Error('VAPID public key is missing!');
  console.log('Using VAPID key:', base64String.substring(0, 10) + '...');
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
  const rawData = window.atob(base64);
  return Uint8Array.from([...rawData].map(char => char.charCodeAt(0)));
}

export const usePushNotificationStore = defineStore('pushNotification', {
  state: () => ({
    isSubscribed: false,
    error: '' as string | null,
    subscription: null as PushSubscription | null,
    swRegistration: null as ServiceWorkerRegistration | null,
  }),
  actions: {
    async checkSubscription() {
      try {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
          this.error = 'Push notifications are not supported in your browser.';
          return false;
        }

        // Get service worker registration
        const registration = await navigator.serviceWorker.getRegistration('/service-worker.js');
        if (!registration) {
          console.log('No service worker registration found');
          return false;
        }

        this.swRegistration = registration;
        
        // Check if already subscribed
        const subscription = await registration.pushManager.getSubscription();
        if (subscription) {
          console.log('Existing push subscription found');
          this.subscription = subscription;
          this.isSubscribed = true;
          return true;
        }
        
        return false;
      } catch (err) {
        console.error('Error checking subscription:', err);
        this.error = err instanceof Error ? err.message : String(err);
        return false;
      }
    },

    async subscribe() {
      this.error = null;
      
      if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        this.error = 'Push notifications are not supported in your browser.';
        return;
      }
      
      try {
        // First check if we're already subscribed
        const isAlreadySubscribed = await this.checkSubscription();
        if (isAlreadySubscribed) {
          console.log('Already subscribed to push notifications');
          return;
        }

        // Register service worker if not already registered
        if (!this.swRegistration) {
          console.log('Registering service worker...');
          this.swRegistration = await navigator.serviceWorker.register('/service-worker.js', {
            scope: '/'
          });
          
          // Wait for the service worker to be ready
          if (this.swRegistration.installing) {
            console.log('Service worker installing...');
            const serviceWorker = this.swRegistration.installing || this.swRegistration.waiting;
            
            await new Promise<void>((resolve) => {
              if (!serviceWorker) {
                resolve();
                return;
              }
              
              serviceWorker.addEventListener('statechange', (e: Event) => {
                const target = e.target as ServiceWorker;
                console.log('Service worker state changed:', target.state);
                if (target.state === 'activated') {
                  console.log('Service worker activated');
                  resolve();
                }
              });
            });
          }
        }

        // Request permission
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
          this.error = 'Notification permission denied';
          return;
        }

        console.log('Subscribing to push notifications...');
        // Subscribe to push
        this.subscription = await this.swRegistration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
        });

        console.log('Push subscription:', this.subscription);

        // Send subscription to server
        await axios.post('/api/push/subscribe', this.subscription, {
          headers: { 'Content-Type': 'application/json' },
        });

        this.isSubscribed = true;
        console.log('Successfully subscribed to push notifications');
      } catch (err) {
        console.error('Subscription error:', err);
        this.error = err instanceof Error ? err.message : String(err);
      }
    },
    async send() {
      this.error = null;
      if (!this.isSubscribed) {
        this.error = 'You must subscribe to push notifications first.';
        return;
      }
      try {
        const response = await axios.post('/api/push/send', {}, {
          headers: { 'Content-Type': 'application/json' },
        });
        console.log('Push notification sent:', response.data);
        return response.data;
      } catch (err) {
        console.error('Error sending push notification:', err);
        this.error = err instanceof Error ? err.message : String(err);
      }
    },
    
    async unsubscribe() {
      this.error = null;
      try {
        // Check if we have a subscription
        if (!this.subscription && this.swRegistration) {
          this.subscription = await this.swRegistration.pushManager.getSubscription();
        }
        
        if (this.subscription) {
          // Unsubscribe from push manager
          await this.subscription.unsubscribe();
          
          // Notify server
          await axios.post('/api/push/unsubscribe', this.subscription, {
            headers: { 'Content-Type': 'application/json' },
          });
          
          this.subscription = null;
          this.isSubscribed = false;
          console.log('Successfully unsubscribed from push notifications');
        }
      } catch (err) {
        console.error('Error unsubscribing:', err);
        this.error = err instanceof Error ? err.message : String(err);
      }
    }
  }
});
