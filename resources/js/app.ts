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
        app.use(plugin)
            .use(ZiggyVue)
            .use(Toast)
            .use(pinia) // ✅ Tambahkan di sini
            .use(PrimeVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

initializeTheme();
