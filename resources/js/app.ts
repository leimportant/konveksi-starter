import '../css/app.css';
import 'vue-toastification/dist/index.css';
import './bootstrap';

import axios from 'axios'
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import Toast from 'vue-toastification';
import { createPinia } from 'pinia';
import { initializeTheme } from './composables/useAppearance';
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'
import Order from './components/Order/Order.vue';
import OrderItem from './components/Order/OrderItem.vue';
import { usePWAStore } from './stores/usePWAStore';
import { PWAInstallPrompt } from './components/ui/pwa-install-prompt';



import PrimeVue from 'primevue/config';

const appName = import.meta.env.VITE_APP_NAME || 'Aninkafashion';

// Axios interceptor
axios.interceptors.response.use(
  response => response,
  error => {
    if (error.response && error.response.status === 401) {
      window.location.href = '/welcome' // Redirect ke landing/welcome page
    }
    return Promise.reject(error)
  }
)


createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const pages = import.meta.glob<DefineComponent>('./pages/**/*.vue');
        let path = `./pages/${name}.vue`;

        // Check if the page is in the 'home' subdirectory
        if (name.startsWith('Home/')) {
            path = `./pages/home/${name.substring(5)}.vue`;
        } else if (name.startsWith('Setting/')) {
            path = `./pages/setting/${name.substring(8)}.vue`;
        }

        return resolvePageComponent(path, pages);
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // ✅ Pinia harus digunakan setelah createApp
        const pinia = createPinia();
       // dalam createApp
      app.component('QuillEditor', QuillEditor)
      app.component('Order', Order);
      app.component('OrderItem', OrderItem);
      app.component('PWAInstallPrompt', PWAInstallPrompt);

        app.use(plugin)
            .use(ZiggyVue)
            .use(Toast)
            .use(pinia) // ✅ Tambahkan di sini
            .use(PrimeVue)
            .mount(el);

        // Register service worker
        // app.ts
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

initializeTheme();

const pwaStore = usePWAStore();

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    pwaStore.setDeferredPrompt(e);
    console.log('beforeinstallprompt event fired and stored in Pinia');
});
