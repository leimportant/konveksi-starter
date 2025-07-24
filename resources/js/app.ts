import '../css/app.css';
import 'vue-toastification/dist/index.css';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

import './bootstrap';
import i18n from './i18n';

import axios from 'axios';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import Toast from 'vue-toastification';
import { createPinia } from 'pinia';
import { initializeTheme } from './composables/useAppearance';
import { QuillEditor } from '@vueup/vue-quill';
import PrimeVue from 'primevue/config';

import Order from './components/Order/Order.vue';
import OrderItem from './components/Order/OrderItem.vue';
import { PWAInstallPrompt } from './components/ui/pwa-install-prompt';
import { usePWAStore } from './stores/usePWAStore';
import pushNotificationManager from './push-notifications';

const appName = import.meta.env.VITE_APP_NAME || 'Aninkafashion';

// 🔒 Axios: redirect ke halaman login jika 401
axios.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      window.location.href = '/welcome';
    }
    return Promise.reject(error);
  }
);

createInertiaApp({
  title: title => `${title} - ${appName}`,
  resolve: name => {
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

    // Install plugins
    vueApp
      .use(plugin)
      .use(ZiggyVue)
      .use(Toast)
      .use(createPinia())
      .use(PrimeVue)
      .use(i18n)
      .mount(el);

    // 📱 Handle install prompt for PWA
    const pwaStore = usePWAStore();

    window.addEventListener('beforeinstallprompt', e => {
      e.preventDefault();
      pwaStore.setDeferredPrompt(e);
      console.log('beforeinstallprompt event fired and stored in Pinia');
    });

    // Initialize push notification manager

    // Register service worker for PWA and initialize push notifications
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', async function() {
        try {
          // Register the service worker
          const registration = await navigator.serviceWorker.register('/service-worker.js', {
            scope: '/'
          });
          
          console.log('ServiceWorker registration successful with scope: ', registration.scope);
          
          // Make the VAPID public key available globally
          // window.vapidPublicKey = import.meta.env.VITE_VAPID_PUBLIC_KEY;
          (window as any).vapidPublicKey = import.meta.env.VITE_VAPID_PUBLIC_KEY;

          // Initialize push notifications after service worker is ready
          navigator.serviceWorker.ready.then(async () => {
            console.log('Service worker is ready, initializing push notifications');
            
            // Initialize push notification manager
            const initialized = await pushNotificationManager.initialize();
            
            if (initialized) {
              console.log('Push notification manager initialized successfully');
              
              // Check if user is already subscribed
              const isSubscribed = await pushNotificationManager.checkSubscription();
              console.log('User is ' + (isSubscribed ? '' : 'not ') + 'subscribed to push notifications');
              
              // You can auto-subscribe here if needed
              // if (!isSubscribed) {
              //     await pushNotificationManager.subscribeUser();
              // }
            }
          });
        } catch (error) {
          console.error('ServiceWorker registration failed: ', error);
        }
      });
    }

    
  },
  progress: {
    color: '#4B5563',
  },
});

// 🌓 Init theme
initializeTheme();
