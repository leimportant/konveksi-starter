import { defineStore } from 'pinia';
import axios from 'axios';

interface Location {
  id: number;
  name: string;
}

interface Sloc {
  id: number;
  name: string;
}

interface Product {
  id: number;
  name: string;
}

interface Size {
  id: string;
  name: string;
}

interface Uom {
  id: string;
  name: string;
}

interface TransferDetail {
  id?: number;
  product_id: number;
  product_name?: string;
  size_id: string;
  size_name?: string;
  uom_id: string;
  uom_name?: string;
  qty: number;
  product?: Product;
  size?: Size;
  uom?: Uom;
}

interface TransferStock {
  id?: string;
  location_id: number | null;
  location_destination_id: number | null;
  sloc_id: number | null;
  status?: string; // Added status based on View.vue usage
  location?: Location;
  location_destination?: Location;
  sloc?: Sloc;
  transfer_detail: TransferDetail[];
  created_at?: string;
  updated_at?: string;
}

interface State {
  transfers: TransferStock[];
  transfer: TransferStock | null;
  loading: boolean;
  error: string | null;
  products: Product[];
}

export const useTransferStockStore = defineStore('transferStock', {
  state: (): State => ({
    transfers: [],
    transfer: null,
    loading: false,
    error: null,
    products: [],
  }),

  actions: {
    async fetchProducts() {
      this.loading = true;
      try {
        const res = await axios.get('/api/products');
        this.products = res.data.data;
        this.error = null;
      } catch (e: any) {
        this.error = e.response?.data?.message || 'Failed to fetch products';
      } finally {
        this.loading = false;
      }
    },
    async fetchTransfers() {
      this.loading = true;
      try {
        const res = await axios.get('/api/transfer-stock');
        this.transfers = res.data.data;
        this.error = null;
      } catch (e: any) {
        this.error = e.response?.data?.message || 'Failed to fetch transfers';
      } finally {
        this.loading = false;
      }
    },

    async fetchTransferByID(id: string) {
      this.loading = true;
      try {
        const res = await axios.get(`/api/transfer-stock/${id}`);
        this.transfer = res.data;
        this.error = null;
      } catch (e: any) {
        this.error = e.response?.data?.message || 'Failed to fetch transfer';
      } finally {
        this.loading = false;
      }
    },

    async createTransfer(data: TransferStock) {
      this.loading = true;
      try {
        const res = await axios.post('/api/transfer-stock', data);
        this.transfer = res.data.data;
        this.error = null;
        return res.data;
      } catch (e: any) {
        this.error = e.response?.data?.message || 'Failed to create transfer';
        throw e;
      } finally {
        this.loading = false;
      }
    },

    async updateTransfer(id: string, data: TransferStock) {
      this.loading = true;
      try {
        const res = await axios.put(`/api/transfer-stock/${id}`, data);
        this.transfer = res.data;
        this.error = null;
        return res.data;
      } catch (e: any) {
        this.error = e.response?.data?.message || 'Failed to update transfer';
        throw e;
      } finally {
        this.loading = false;
      }
    },

    async deleteTransfer(id: string) {
      this.loading = true;
      try {
        await axios.delete(`/api/transfer-stock/${id}`);
        this.transfer = null;
        this.error = null;
      } catch (e: any) {
        this.error = e.response?.data?.message || 'Failed to delete transfer';
        throw e;
      } finally {
        this.loading = false;
      }
    },
    async acceptTransfer(id: string) {
      if (!id) throw new Error('Transfer ID tidak ditemukan')
      const response = await axios.put(`/api/transfer-stock/${id}/accept`)
      return response
    },
    async rejectTransfer(id: string) {
      if (!id) throw new Error('Transfer ID tidak ditemukan')
      const response = await axios.put(`/api/transfer-stock/${id}/reject`)
      return response
    },

    resetTransfer() {
      this.transfer = null;
      this.error = null;
    }
  },
});
