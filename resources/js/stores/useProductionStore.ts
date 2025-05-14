import { defineStore } from 'pinia';
import axios from 'axios';

interface ProductionItem {
  id?: string;
  production_id?: string;
  size_id: string;
  qty: number;
}

interface Production {
  id: string;
  model_id: number;
  activity_role_id: number;
  remark: string | null;
  created_at: string;
  updated_at: string;
  model?: {
    id: number;
    description: string;
  };
  activity_role?: {
    id: number;
    name: string;
  };
  items?: ProductionItem[];
}

interface State {
  loading: boolean;
  productions: Production[];
  error: string | null;
  total: number;
  currentPage: number;
  lastPage: number;
}

interface FetchParams {
  search?: string;
  sort_field?: string;
  sort_order?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
  date_from?: string;
  date_to?: string;
  activity_role_id?: string | number;
}

export const useProductionStore = defineStore('production', {
  state: (): State => ({
    loading: false,
    productions: [],
    error: null,
    total: 0,
    currentPage: 1,
    lastPage: 1,
  }),

  actions: {
    async fetchProductions(params: FetchParams) {
      this.loading = true;
      try {
        const response = await axios.get('/api/productions', { params });
        this.productions = response.data.data.data; // Access nested data array
        this.total = response.data.data.total;
        this.currentPage = response.data.data.current_page;
        this.lastPage = response.data.data.last_page;
        return response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to fetch productions';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchProductionsById(id: string) {
      this.loading = true;
      try {
        const response = await axios.get(`/api/productions/${id}`);
        return response.data.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to fetch productions';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createProduction(productionData: {
      model_id: number;
      activity_role_id: number;
      remark?: string;
      items: ProductionItem[];
    }) {
      this.loading = true;
      try {
        const response = await axios.post('/api/productions', productionData);
        return response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to create production';
        throw error;
      } finally {
        this.loading = false;
      }
    },
    async updateProduction(id: string, data: Partial<Production>) {
      try {
        this.loading = true;
        const response = await axios.put(`/api/productions/${id}`, data);
        const index = this.productions.findIndex(production => production.id === id);
        if (index !== -1) {
          this.productions[index] = response.data;
        }
        return response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to update production';
        throw error;
      } finally {
        this.loading = false;
      }
    },
    async deleteProduction(id: string) {
      this.loading = true;
      try {
        await axios.delete(`/api/productions/${id}`);
        this.productions = this.productions.filter(p => p.id !== id);
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to delete production';
        throw error;
      } finally {
        this.loading = false;
      }
    }
  }
});
