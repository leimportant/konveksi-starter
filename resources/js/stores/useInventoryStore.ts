import { defineStore } from 'pinia';
import axios from 'axios';

interface InventoryItem {
  id: number;
  product: {
    id: number;
    name: string;
  };
  location: {
    id: number;
    name: string;
  };
  sloc: {
    id: number;
    name: string;
  };
  uom: {
    id: number;
    name: string;
  };
  qty: number;
}

interface State {
  loading: boolean;
  inventory: InventoryItem[];
  error: string | null;
  total: number;
  currentPage: number;
  lastPage: number;
  filters: {
    location: number | null;
    sloc: number | null;
    product: number | null;
  };
}

export const useInventoryStore = defineStore('inventory', {
  state: (): State => ({
    loading: false,
    inventory: [],
    error: null,
    total: 0,
    currentPage: 1,
    lastPage: 1,
    filters: {
      location: null,
      sloc: null,
      product: null,
    },
  }),

  actions: {
    setFilter(filter: keyof State['filters'], value: string | null) {
      this.filters[filter] = value === '' ? null : Number(value);
    },

    async fetchInventory(page: number = 1) {
      this.loading = true;
      try {
        const response = await axios.get('/api/inventories', {
          params: {
            page: page,
            ...this.filters,
          },
        });
        this.inventory = response.data.data; // Assuming the API response structure is similar to production
        this.total = response.data.total;
        this.currentPage = response.data.current_page;
        this.lastPage = response.data.last_page;
        return response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to fetch inventory';
        throw error;
      } finally {
        this.loading = false;
      }
    },
  },
});