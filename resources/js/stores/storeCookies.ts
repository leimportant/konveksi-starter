import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useStoreCookies = defineStore('storeCookies', () => {
  const isTransferring = ref(false);
  const transferError = ref<string | null>(null);
  const transferSuccess = ref(false);

  function getCookie(name: string) {
    const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    return match ? decodeURIComponent(match[2]) : null;
  }

  async function transferCookies(childUrl: string) {
    isTransferring.value = true;
    transferError.value = null;
    transferSuccess.value = false;

    try {
      // Ambil CSRF cookie dulu
      await fetch('/sanctum/csrf-cookie', { method: 'GET', credentials: 'include' });

      const xsrfToken = getCookie('XSRF-TOKEN');

      const aninkafashionToken = getCookie('aninkafashion-token');
      console.log('aninkafashion_session:', aninkafashionToken);

      console.log('XSRF-TOKEN:', xsrfToken);
      // POST transfer cookie
      const response = await fetch('/api/transfer-cookies', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-XSRF-TOKEN': xsrfToken || ''
        },
        credentials: 'include',
        body: JSON.stringify({ childUrl })
      });

      const data = await response.json();
      if (!response.ok) {
        throw new Error(data.message || 'Transfer failed');
      }

      // pakai redirect dengan token di query string
      transferSuccess.value = true;
      window.location.href = childUrl + '?_token=' + (data._token || '');

    } catch (err: any) {
      transferError.value = err?.message || 'Network or CSRF error';
      console.error('Failed to transfer cookie:', transferError.value);
    } finally {
      isTransferring.value = false;
    }
  }

  return { isTransferring, transferError, transferSuccess, transferCookies };
});
