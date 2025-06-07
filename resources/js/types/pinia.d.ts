// src/types/pinia.d.ts
import 'pinia'
import type { PersistedStateOptions } from 'pinia-plugin-persistedstate'

declare module 'pinia' {
  export interface DefineStoreOptionsBase {
    persist?: boolean | PersistedStateOptions
  }
}
