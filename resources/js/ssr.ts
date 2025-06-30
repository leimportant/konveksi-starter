import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { PageProps } from '@inertiajs/vue3';
import { renderToString } from '@vue/server-renderer';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

import { createSSRApp, h, DefineComponent } from 'vue';
import { route as ziggyRoute } from 'ziggy-js';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

interface ZiggyConfig {
    url: string;
    port: number | null;
    defaults: Record<string, any>;
    routes: Record<string, any>;
    location: URL;
}

createServer((page: PageProps & { props: { ziggy: ZiggyConfig } }) =>
    createInertiaApp({
        page,
        render: renderToString,
        title: (title) => `${title} - ${appName}`,
        resolve: async (name) => {
            const component = (await resolvePageComponent(`./pages/${name}.vue`, import.meta.glob('./pages/**/*.vue'))) as DefineComponent; 
            return component.default || component;
        },
        setup({ App, props, plugin }) {
            const app = createSSRApp({ render: () => h(App, props) });

            // Configure Ziggy for SSR...
            const ziggyConfig = {
                ...page.props.ziggy,
                location: new URL(page.props.ziggy.location),
            };

            // Create route function...
            const route: typeof ziggyRoute = ((name: string, params?: any, absolute?: boolean) => ziggyRoute(name, params, absolute, ziggyConfig)) as typeof ziggyRoute;

            // Make route function available globally...
            app.config.globalProperties.route = route;

            // Make route function available globally for SSR...
            if (typeof window === 'undefined') {
                globalThis.route = route;
            }

            app.use(plugin);

            return app;
        },
    }),
);
