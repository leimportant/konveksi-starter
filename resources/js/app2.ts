import '@vueup/vue-quill/dist/vue-quill.snow.css';
import 'vue-toastification/dist/index.css';
import '../css/app.css';

import './bootstrap';
import i18n from './i18n';

import { createInertiaApp } from '@inertiajs/vue3';
import { QuillEditor } from '@vueup/vue-quill';
import axios from 'axios';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import PrimeVue from 'primevue/config';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import Toast from 'vue-toastification';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';

import Order from './components/Order/Order.vue';
import OrderItem from './components/Order/OrderItem.vue';
import { PWAInstallPrompt } from './components/ui/pwa-install-prompt';
import { usePWAStore } from './stores/usePWAStore';
// Import Push Notification Manager
// import pushNotificationManager from './push-notifications';

const appName = import.meta.env.VITE_APP_NAME || 'Aninkafashion';

// 🔒 Axios: redirect ke halaman login jika 401
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            window.location.href = '/welcome';
        }
        return Promise.reject(error);
    },
);

axios.defaults.withCredentials = true;

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const pages = import.meta.glob<DefineComponent>('./pages/**/*.vue');
        let path = `./pages/${name}.vue`;

        if (name.startsWith('Home/')) {
            path = `./pages/home/${name.substring(5)}.vue`;
        } else if (name.startsWith('Setting/')) {
            path = `./pages/setting/${name.substring(8)}.vue`;
        }

        return resolvePageComponent(path, pages);
    },
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) });

        // Register global components
        vueApp.component('QuillEditor', QuillEditor);
        vueApp.component('Order', Order);
        vueApp.component('OrderItem', OrderItem);
        vueApp.component('PWAInstallPrompt', PWAInstallPrompt);

        // Install plugins (Pastikan ini di atas mount)
        vueApp.use(plugin).use(ZiggyVue).use(Toast).use(createPinia()).use(PrimeVue).use(i18n).mount(el);

        // 📱 PWA: Handle install prompt (Bisa tetap di sini)
        const pwaStore = usePWAStore();

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            pwaStore.setDeferredPrompt(e);
            console.log('beforeinstallprompt event fired and stored in Pinia');
        });

        // =========================================================================
        // ✅ AKTIVASI DAN INISIALISASI PUSH NOTIFICATION
        // =========================================================================

        // Cek support Service Worker dan Public VAPID Key
        // if ('serviceWorker' in navigator && import.meta.env.VITE_VAPID_PUBLIC_KEY) {
        //     // 1. Registrasi Service Worker
        //     navigator.serviceWorker
        //         .register('/service-worker.js', { scope: '/' })
        //         .then((registration) => {
        //             console.log('SW registered successfully with scope:', registration.scope);

        //             // 2. Setelah SW siap, inisialisasi Push Manager
        //             navigator.serviceWorker.ready.then(async () => {
        //                 // Tambahkan Public Key ke window (sebagai fallback untuk manager)
        //                 (window as any).vapidPublicKey = import.meta.env.VITE_VAPID_PUBLIC_KEY;

        //                 // Inisialisasi Push Notification Manager
        //                 const initialized = await pushNotificationManager.initialize();

        //                 if (initialized) {
        //                     console.log('Push notification manager initialized successfully.');
        //                 }
        //             });
        //         })
        //         .catch((error) => {
        //             console.error('ServiceWorker registration failed:', error);
        //         });
        // } else {
        //     console.warn('Push notifications not supported or VAPID key missing.');
        // }
    },
    progress: {
        color: '#4B5563',
    },
});

// 🌓 Init theme
initializeTheme();
