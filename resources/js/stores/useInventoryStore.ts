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
  uom_id: string;
  size_id: string
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
    sloc: string | null;
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
     setFilter(filter: keyof State['filters'], value: string | number | null) {
      if (filter === 'location' || filter === 'product') {
        this.filters[filter] = value === '' ? null : Number(value);
      } else {
        this.filters[filter] = value === '' ? null : String(value);
      }
    },

    async fetchInventory(page = 1, perPage = 10) {
      this.loading = true;
      console.log('Fetching inventory...');
      try {
        const response = await axios.get(`/api/inventories?page=${page}&perPage=${perPage}`, {
          params: {
            ...this.filters,
          },
        });
        this.inventory = response.data.data;
        // this.total = response.data.total;
        // this.currentPage = response.data.current_page;
        // this.lastPage = response.data.last_page;
        console.log('Data:', response.data.data);
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to fetch inventory';
        console.error('Fetch error:', this.error);
      } finally {
        this.loading = false;
        console.log('Done loading');
      }
    },
  },
});