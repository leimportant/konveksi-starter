import '../css/app.css';
import 'vue-toastification/dist/index.css';

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



import PrimeVue from 'primevue/config';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue')
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // ✅ Pinia harus digunakan setelah createApp
        const pinia = createPinia();
       // dalam createApp
      app.component('QuillEditor', QuillEditor)

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
