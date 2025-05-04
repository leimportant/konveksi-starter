// src/shims-inertia.d.ts or src/types/shims-inertia.d.ts
import { Inertia } from '@inertiajs/inertia';

declare module '@vue/runtime-core' {
  interface ComponentCustomProperties {
    $inertia: Inertia;
  }
}
