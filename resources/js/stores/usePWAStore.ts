import { defineStore } from 'pinia';

export const usePWAStore = defineStore('pwa', {
  state: () => ({
    deferredPrompt: null as Event | null,
    showInstallPrompt: false,
  }),
  actions: {
    setDeferredPrompt(event: Event) {
      this.deferredPrompt = event;
      this.showInstallPrompt = true;
    },
    async installPWA() {
      if (this.deferredPrompt) {
        (this.deferredPrompt as any).prompt();
        const { outcome } = await (this.deferredPrompt as any).userChoice;
        console.log(`User response to the install prompt: ${outcome}`);
        this.deferredPrompt = null;
        this.showInstallPrompt = false;
      }
    },
    hideInstallPrompt() {
      this.showInstallPrompt = false;
    },
  },
});