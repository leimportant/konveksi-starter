// stores/useAuthStore.ts
import { defineStore } from 'pinia';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export const useAuthStore = defineStore('auth', () => {
  const page = usePage();

  const user = computed(() => page.props.auth?.user ?? null);
  const isLoggedIn = computed(() => !!user.value);

  return {
    user,
    isLoggedIn,
  };
});
