import { defineStore } from 'pinia';
import axios from 'axios';

const VAPID_PUBLIC_KEY = import.meta.env.VITE_VAPID_PUBLIC_KEY;

function urlBase64ToUint8Array(base64String: string) {
  if (!base64String) throw new Error('VAPID public key is missing!');
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
  const rawData = window.atob(base64);
  return Uint8Array.from([...rawData].map(char => char.charCodeAt(0)));
}

export const usePushNotificationStore = defineStore('pushNotification', {
  state: () => ({
    isSubscribed: false,
    error: '' as string | null,
  }),
  actions: {
    async subscribe() {
      this.error = null;
      if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        this.error = 'Push notifications are not supported in your browser.';
        return;
      }
      try {
        const registration = await navigator.serviceWorker.register('/service-worker.js');

        const subscription = await registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
        });

        await axios.post('/api/push/subscribe', subscription, {
          headers: { 'Content-Type': 'application/json' },
        });

        this.isSubscribed = true;
      } catch (err) {
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
        await axios.post('/api/push/send', {}, {
          headers: { 'Content-Type': 'application/json' },
        });
      } catch (err) {
        this.error = err instanceof Error ? err.message : String(err);
      }
    }
  }
});
