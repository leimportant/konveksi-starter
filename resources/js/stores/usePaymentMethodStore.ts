import { defineStore } from 'pinia';
import axios from 'axios';

interface PaymentMethod {
  id: string;
  name: string;
}

interface State {
  items: PaymentMethod[];
  total: number;
  loaded: boolean;
  loading: boolean;
  currentPage: number;
  lastPage: number;
  filterName: string;
  error: string | null;
}

export const usePaymentMethodStore = defineStore('paymentMethod', {
  state: (): State => ({
    items: [],
    total: 0,
    loaded: false,
    loading: false,
    currentPage: 1,
    lastPage: 1,
    filterName: '',
    error: null,
  }),

  actions: {
    async fetchPaymentMethods(page = 1, perPage = 50) {
      if (this.loaded) return;

      this.loading = true;
      try {
        const response = await axios.get('/api/payment-methods', {
          params: {
            page,
            perPage,
            name: this.filterName,
          },
        });

        this.items = response.data.data;
        this.total = response.data.total;
        this.currentPage = response.data.current_page;
        this.lastPage = response.data.last_page;
        this.loaded = true;
        this.error = null;
      } catch (error: any) {
        console.error('Failed to fetch payment methods', error);
        this.error = error.message || 'Unknown error';
      } finally {
        this.loading = false;
      }
    },
     setFilter(field: string, value: string) {
        if (field === 'name') {
            this.filterName = value;
            this.currentPage = 1;
            this.loaded = false; // <--- Tambahkan ini
            this.fetchPaymentMethods(1);
        }
    },
    async createPaymentMethod(name: string) {
      try {
        await axios.post('/api/payment-methods', { name });
        this.loaded = false;
        await this.fetchPaymentMethods();
      } catch (error) {
        throw error;
      }
    },

    async updatePaymentMethod(id: string, name: string) {
      try {
        await axios.put(`/api/payment-methods/${id}`, { name });
        this.loaded = false;
        await this.fetchPaymentMethods();
      } catch (error) {
        throw error;
      }
    },

    async deletePaymentMethod(id: string) {
      try {
        await axios.delete(`/api/payment-methods/${id}`);
        this.loaded = false;
        await this.fetchPaymentMethods();
      } catch (error) {
        throw error;
      }
    },
  },
});
