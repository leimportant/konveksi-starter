import { defineStore } from 'pinia';
import axios from 'axios';

interface Production {
    id: string;
    model_id: number;
    activity_role_id: number;
    qty: number;
    remark?: string;
    created_at: string;
    updated_at: string;
    model?: {
        id: number;
        description: string;
    };
    activityRole?: {
        id: number;
        name: string;
    };
}

interface FetchParams {
    search?: string;
    sort_field?: string;
    sort_order?: 'asc' | 'desc';
    per_page?: number;
    page?: number;
}

export const useProductionStore = defineStore('production', {
    state: () => ({
        items: [] as Production[],
        loading: false,
        error: null as string | null,
        total: 0,
        currentPage: 1,
        lastPage: 1,
    }),

    actions: {
        async fetchProductions(params: FetchParams = {}) {
            try {
                this.loading = true;
                const response = await axios.get('/api/productions', { params });
                this.items = response.data.data;
                this.total = response.data.total;
                this.currentPage = response.data.current_page;
                this.lastPage = response.data.last_page;
                return response.data;
            } catch (error: any) {
                this.error = error.response?.data?.message || 'Failed to fetch productions';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async createProduction(data: Omit<Production, 'id' | 'created_at' | 'updated_at'>) {
            try {
                this.loading = true;
                const response = await axios.post('/api/productions', data);
                await this.fetchProductions({ page: this.currentPage });
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
                const index = this.items.findIndex(item => item.id === id);
                if (index !== -1) {
                    this.items[index] = response.data.data;
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
            try {
                this.loading = true;
                await axios.delete(`/api/productions/${id}`);
                this.items = this.items.filter(item => item.id !== id);
            } catch (error: any) {
                this.error = error.response?.data?.message || 'Failed to delete production';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async getProduction(id: string) {
            try {
                this.loading = true;
                const response = await axios.get(`/api/productions/${id}`);
                return response.data;
            } catch (error: any) {
                this.error = error.response?.data?.message || 'Failed to fetch production';
                throw error;
            } finally {
                this.loading = false;
            }
        }
    }
});