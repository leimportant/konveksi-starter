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
// import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';

import Order from './components/Order/Order.vue';
import OrderItem from './components/Order/OrderItem.vue';
import { PWAInstallPrompt } from './components/ui/pwa-install-prompt';
import { usePWAStore } from './stores/usePWAStore';

const appName = import.meta.env.VITE_APP_NAME || 'Aninkafashion';

// 🔒 Axios interceptor
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      window.location.href = '/welcome';
    }
    return Promise.reject(error);
  }
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

    // Global components
    vueApp.component('QuillEditor', QuillEditor);
    vueApp.component('Order', Order);
    vueApp.component('OrderItem', OrderItem);
    vueApp.component('PWAInstallPrompt', PWAInstallPrompt);

    // Core plugins
    vueApp
      .use(plugin)
      .use(Toast)
      // .use(ZiggyVue)
      .use(createPinia())
      .use(PrimeVue)
      .use(i18n)
      .mount(el);

    // 📱 PWA prompt handler
    const pwaStore = usePWAStore();
    window.addEventListener('beforeinstallprompt', (e) => {
      e.preventDefault();
      pwaStore.setDeferredPrompt(e);
      console.log('beforeinstallprompt event captured.');
    });
  },
  progress: {
    color: '#4B5563',
  },
});

// 🌓 Inisialisasi tema
initializeTheme();
