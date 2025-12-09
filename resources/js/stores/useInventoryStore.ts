import axios from 'axios';
import { defineStore } from 'pinia';

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
    size_id: string;
    qty: number;
}

interface InventoryReport {
    id: number;
    product_id: string;
    product_name: string;
    location_id: string;
    location_name: string;
    sloc_id: string;
    sloc_name: string;
    uom_id: string;
    size_id: string;
    variant: string;
    qty: number;
    qty_in: number;
    qty_out: number;
    qty_available: number;
    qty_available_rpt: number;
}
interface StockMonitoringReport {
  product_id: number;
  product_name: string;
  uom_id: string;
  sloc_id: string;

  // Dynamic locations as key-value map
  [location_key: string]: any | {
    qty: number;
  };
}

interface State {
    loading: boolean;
    stockMonitoringReport: StockMonitoringReport[];
    inventory: InventoryItem[];
    inventoryRpt: InventoryReport[]; // ✅ Declare this properly
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
        stockMonitoringReport: [],
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
            } else {
                this.filters[filter] = value === '' ? null : String(value);
            }
        },

        async fetchInventory(page = 1, perPage = 50) {
            this.loading = true;
            try {
                const response = await axios.get(`/api/inventories?page=${page}&perPage=${perPage}`, {
                    params: { ...this.filters },
                });
                this.inventoryRpt = response.data.data;
                this.total = response.data.total ?? 0;
                this.currentPage = response.data.current_page ?? 1;
                this.lastPage = response.data.last_page ?? 1;
            } catch (error: any) {
                this.error = error.response?.data?.message || 'Failed to fetch inventory';
                console.error('Fetch inventory error:', this.error);
            } finally {
                this.loading = false;
            }
        },

        async updateInventory(product_id: number, location_id: number, sloc_id: string, size_id: string, qty: number) {
            try {
                await axios.put(`/api/inventories/${product_id}/${location_id}/${sloc_id}/${size_id}`, { qty });
            } catch (error: any) {
                this.error = error.response?.data?.message || 'Failed to update inventory';
                console.error('Update inventory error:', this.error);
            }
        },

        async deleteInventory(id: number) {
            try {
                await axios.delete(`/api/inventories/${id}`);
            } catch (error: any) {
                this.error = error.response?.data?.message || 'Failed to delete inventory';
                console.error('Delete inventory error:', this.error);
            }
        },

     async fetchStockMonitoringReport(page = 1, perPage = 50) {
  this.loading = true;
  this.error = null;

  try {
    const response = await axios.get('/api/inventory/stock-monitoring', {
      params: {
        page,
        perPage,
        ...this.filters,
      },
    });

    // Assign data and pagination
    this.stockMonitoringReport = response.data.data;
    this.currentPage = response.data.current_page ?? 1;
    this.lastPage = response.data.last_page ?? 1;
    this.total = response.data.total ?? 0;
  } catch (error: any) {
    this.error = error.response?.data?.message || 'Failed to fetch stock monitoring report';
    console.error('Fetch stock monitoring error:', this.error);
  } finally {
    this.loading = false;
  }
}

    },
});
