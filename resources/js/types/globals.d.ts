// global.d.ts or inertia.d.ts
import type { route as routeFn } from 'ziggy-js';
import Echo from 'laravel-echo';

declare global {
  var route: typeof routeFn; // ✅ MUST use `var` for global
  interface Window {
    route: typeof routeFn;
  }
}

declare global {
  interface Window {
    axios: import('axios').AxiosInstance;
    Popper: typeof import('@popperjs/core');
    Echo: Echo;
    Pusher: any;
    _: typeof import('lodash');
  }
}