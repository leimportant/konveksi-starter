import axios from 'axios';
import { defineStore } from 'pinia';

export interface StockOpnameItem {
  size_id: string;
  variant: string;
  qty: number;
  qty_system: number;
  qty_physical: number;
  difference: number;
  note: string;
}

export interface StockOpname {
  id: string;
  location_id: number;
  location?: {
    id: number;
    name: string;
  }
  product_id: number;
  product?: {
    id: number;
    name: string;
  }
  sloc_id: string;
  uom_id: string;
  remark: string;
  stock_opname_items: StockOpnameItem[];
  created_by: number;
  updated_by: number;
  created_at: string;
  updated_at: string;
}

interface State {
  items: StockOpname[];
  loading: boolean;
  loaded: boolean;
}


export const useStockOpnameStore = defineStore('stockOpname', {
  state: (): State => ({
    items: [],
    loading: false,
    loaded: false,
  }),

  actions: {
    async fetchOpnames() {
      if (this.loaded) return;
      this.loading = true;
      try {
        const res = await axios.get('/api/stock-opnames');
        this.items = res.data.data;
        this.loaded = true;
      } catch (e) {
        console.error("Error fetching stock opnames", e);
      } finally {
        this.loading = false;
      }
    },

    async fetchOpnameById(id: string) {
      if (this.items.find(opname => opname.id === id)) return;
      this.loading = true;
      try {
        const res = await axios.get(`/api/stock-opnames/${id}`);
        this.items.push(res.data.data);
      } catch (e) {
        console.error("Error fetching stock opname", e);
      } finally {
        this.loading = false;
      }
    },

    async createOpname(opname: Omit<StockOpname, 'id' | 'created_at' | 'updated_at'>) {
      await axios.post('/api/stock-opnames', opname);
    },

    async updateOpname(id: string, opname: Partial<StockOpname>) {
      await axios.put(`/api/stock-opnames/${id}`, opname);
    },

    async deleteOpname(id: string) {
      await axios.delete(`/api/stock-opnames/${id}`);
    },

    reset() {
      this.items = [];
      this.loading = false;
      this.loaded = false;
    }
  }
});
