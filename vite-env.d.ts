/// <reference types="vite/client" />

import Echo from 'laravel-echo';

declare global {
  interface Window {
    Echo: Echo<any>;
    Pusher: any;
    vapidPublicKey: string;
  }
}

declare module 'vite/client' {
    interface ImportMetaEnv {
      readonly VITE_APP_NAME: string;
      [key: string]: string | boolean | undefined;
    }
  
    interface ImportMeta {
      readonly env: ImportMetaEnv;
      readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
  }
  