import '../css/app.css';
import 'vue-toastification/dist/index.css';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

import './bootstrap';

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
      .mount(el);

    // 📱 Handle install prompt for PWA
    const pwaStore = usePWAStore();

    window.addEventListener('beforeinstallprompt', e => {
      e.preventDefault();
      pwaStore.setDeferredPrompt(e);
      console.log('beforeinstallprompt event fired and stored in Pinia');
    });

    // ⚙️ Service Worker: hanya jika pakai vite-plugin-pwa
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js')
          .then(registration => {
            console.log('ServiceWorker registered with scope:', registration.scope);
          })
          .catch(error => {
            console.error('ServiceWorker registration failed:', error);
          });
      });
    }
  },
  progress: {
    color: '#4B5563',
  },
});

// 🌓 Init theme
initializeTheme();
