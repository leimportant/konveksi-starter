import { defineStore } from 'pinia';
import axios from 'axios';


interface Product {
  id: number;
  name: string;
}
interface PurchaseOrderItem {
  id?: number;
  purchase_order_id?: string;
  product_id: number | string;
  product: Product;
  qty: number;
  price: number;
  total: number;
  uom_id: string;
}

interface PurchaseOrder {
  id: string;
  purchase_date: string;
  supplier: string;
  nota_number: string;
  status?: string | "Draft";
  items: PurchaseOrderItem[];
}

interface State {
  items: PurchaseOrder[];
  total: number;
  loaded: boolean;
  loading: boolean;
  currentPage: number;
  lastPage: number;
  filterSearch: string;
  error: string | null;

  purchaseOrder: PurchaseOrder | null;
  products: any[]; // Ideally type this if you can
  uoms: any[];
}

type PurchaseOrderItemPayload = Omit<PurchaseOrderItem, 'id' | 'product' | 'purchase_order_id'>;

type PurchaseOrderPayload = {
  purchase_date: string;
  supplier: string;
  nota_number: string;
  notes?: string;
  items: PurchaseOrderItemPayload[];
};


export const usePurchaseOrder = defineStore('purchaseOrder', {
  state: (): State => ({
    items: [],
    total: 0,
    loaded: false,
    loading: false,
    currentPage: 1,
    lastPage: 1,
    filterSearch: '',
    error: null,

    purchaseOrder: null,
    products: [],
    uoms: [],
  }),

  actions: {
    async fetchPurchaseOrders(page = 1, perPage = 10) {
      this.loading = true;
      this.error = null;

      try {
        const response = await axios.get('/api/purchase-order', {
          params: {
            page,
            limit: perPage,
            search: this.filterSearch,
          },
        });

        this.items = response.data.data;
        this.total = response.data.meta?.total ?? 0;
        this.currentPage = response.data.meta?.current_page ?? 1;
        this.lastPage = response.data.meta?.last_page ?? 1;
        this.loaded = true;
      } catch (error: any) {
        console.error('Failed to fetch purchase orders:', error);
        this.error = error?.response?.data?.message || 'Failed to fetch data.';
      } finally {
        this.loading = false;
      }
    },

    async fetchPurchaseOrder(id: string) {
      this.loading = true;
      this.error = null;

      try {
        const response = await axios.get(`/api/purchase-order/${id}`);
        this.purchaseOrder = response.data.data;
        return response.data.data;
      } catch (error: any) {
        console.error(`Failed to fetch purchase order ${id}:`, error);
        this.error = error?.response?.data?.message || 'Failed to fetch data.';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    setFilter(field: string, value: string) {
      if (field === 'search') {
        this.filterSearch = value;
        this.currentPage = 1;
        this.loaded = false;
        this.fetchPurchaseOrders(1);
      }
    },

    async createPurchaseOrder(payload: any) {
      this.loading = true;
      this.error = null;

      try {
        await axios.post('/api/purchase-order', payload);
        this.loaded = false;
        await this.fetchPurchaseOrders();
      } catch (error: any) {
        console.error('Failed to create purchase order:', error);
        this.error = error?.response?.data?.message || 'Failed to create.';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updatePurchaseOrder(id: string, payload: PurchaseOrderPayload) {
      this.loading = true;
      this.error = null;

      try {
        await axios.put(`/api/purchase-order/${id}`, payload);
        this.loaded = false;
        await this.fetchPurchaseOrders();
      } catch (error: any) {
        console.error(`Failed to update purchase order ${id}:`, error);
        this.error = error?.response?.data?.message || 'Failed to update.';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deletePurchaseOrder(id: string) {
      this.loading = true;
      this.error = null;

      try {
        await axios.delete(`/api/purchase-order/${id}`);
        this.loaded = false;
        await this.fetchPurchaseOrders();
      } catch (error: any) {
        console.error(`Failed to delete purchase order ${id}:`, error);
        this.error = error?.response?.data?.message || 'Failed to delete.';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchProducts() {
      try {
        const res = await axios.get('/api/products');
        this.products = res.data.data;
      } catch (error) {
        console.error('Failed to fetch products', error);
      }
    },

    async fetchUoms() {
      try {
        const res = await axios.get('/api/uoms');
        this.uoms = res.data.data;
      } catch (error) {
        console.error('Failed to fetch UOMs', error);
      }
    },
  },
});
