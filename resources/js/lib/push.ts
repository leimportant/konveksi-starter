import axios from 'axios';

export async function subscribeToPush(vapidPublicKey: string): Promise<void> {
    if (!('serviceWorker' in navigator)) {
        throw new Error('Service workers are not supported.');
    }

    const registration = await navigator.serviceWorker.ready;

    // Cek existing subscription
    const existingSubscription = await registration.pushManager.getSubscription();

    // Jika sudah ada dan key berbeda, unsubscribe dulu
    if (existingSubscription) {
        const oldKey = existingSubscription.options.applicationServerKey;
        const newKey = urlBase64ToUint8Array(vapidPublicKey);

        // Bandingkan key, jika beda, unsubscribe
        if (!keysAreEqual(oldKey, newKey)) {
            console.log('Unsubscribing existing push subscription...');
            await existingSubscription.unsubscribe();
        } else {
            console.log('Already subscribed with same key.');
            return; // Sudah terdaftar dengan key yang sama, tidak perlu subscribe lagi.
        }
    }

    const newSubscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
    });

    const payload = {
        endpoint: newSubscription.endpoint,
        keys: {
            p256dh: arrayBufferToBase64(newSubscription.getKey('p256dh')),
            auth: arrayBufferToBase64(newSubscription.getKey('auth')),
        },
    };

    const response = await axios.post('/api/push/subscribe', payload);
    console.log('Subscription berhasil dikirim:', response.data);
}

// Helpers
function urlBase64ToUint8Array(base64String: string): Uint8Array {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

function arrayBufferToBase64(buffer: ArrayBuffer | null): string {
    if (!buffer) return '';
    const bytes = new Uint8Array(buffer);
    let binary = '';
    bytes.forEach(b => binary += String.fromCharCode(b));
    return window.btoa(binary);
}

function keysAreEqual(key1: ArrayBuffer | null, key2: Uint8Array): boolean {
    if (!key1 || key1.byteLength !== key2.byteLength) return false;
    const key1Array = new Uint8Array(key1);
    return key1Array.every((value, index) => value === key2[index]);
}

// function getCsrfToken(): string {
//   const tokenMeta = document.querySelector('meta[name="csrf-token"]');
//   return tokenMeta ? tokenMeta.getAttribute('content') || '' : '';
// }
