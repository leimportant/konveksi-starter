// stores/useMenuStore.ts
import { defineStore } from 'pinia';
import axios from 'axios';
import type { NavItem } from '@/types';

export const useMenuStore = defineStore('menu', {
  state: () => ({
    items: [] as NavItem[],
    loaded: false,
    isCollapsed: false, // <-- tambahkan ini
  }),

  actions: {
    async fetchMenus() {
      if (this.loaded) return;

      try {
        const response = await axios.get('/user-menus');
        this.items = response.data;
        this.loaded = true;
      } catch (error) {
        console.error('Failed to fetch menus', error);
      }
    },

    async fetchAllMenus(roleId: number | null = null) {
      try {
        const response = await axios.get('/all-menus', { params: { role_id: roleId } });
        return response.data;
      } catch (error) {
        console.error('Failed to fetch all menus', error);
        return [];
      }
    },

    toggleCollapse() {
      this.isCollapsed = !this.isCollapsed;
    },
  },
});

