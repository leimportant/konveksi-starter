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

interface InventoryReport {
  product_id: string;
  product_name: string;
  location_id: string;
  location_name: string;
  sloc_id: string;
  sloc_name: string;
  uom_id: string;
  size_id: string;
  qty_in: number;
  qty_out: number;
  qty_available: number;

}


interface State {
  loading: boolean;
  inventoryRpt: InventoryReport[];
  inventory: InventoryItem[];
  error: string | null;
  total: number;
  currentPage: number;
  lastPage: number;
  filters: {
    location: number | null;
    sloc: string | null;
    product: number | null;
    productName: string | null;
  };
}

export const useInventoryStore = defineStore('inventory', {
  state: (): State => ({
    loading: false,
    inventory: [],
    inventoryRpt: [],
    error: null,
    total: 0,
    currentPage: 1,
    lastPage: 1,
    filters: {
      location: null,
      sloc: null,
      product: null,
      productName: null,
    },
  }),

  actions: {
    setFilter(filter: keyof State['filters'], value: string | number | null) {
      if (filter === 'location' || filter === 'product') {
        this.filters[filter] = value === '' ? null : Number(value);
      } else if (filter === 'productName') {
        this.filters[filter] = value === '' ? null : String(value);
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
            productName: this.filters.productName, // Ensure productName is sent
          },
        });
        this.inventoryRpt = response.data.data;
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
  }
});