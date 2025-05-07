// global.d.ts or inertia.d.ts
import type { route as routeFn } from 'ziggy-js';

declare global {
  var route: typeof routeFn; // ✅ MUST use `var` for global
  interface Window {
    route: typeof routeFn;
  }
}
