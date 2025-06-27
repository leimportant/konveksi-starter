import { defineStore } from 'pinia';
import axios from 'axios';

export interface SizeItem {
    size_id: string;
    qty: number;
  }

  export interface ActivityItem {
    activity_role_id: number;
    price: number;
  }
  
  export interface DocumentData {
    id: string;
    url: string;
    filename: string;
  }

  export interface ModelMaterialData {
    product_id: number;
    qty: number;
    uom_id: number;
    remark: string;
  }
interface ModelData {
    description: string;
    remark: string | null;
    uniqId: string | null;
    estimation_price_pcs: number;
    estimation_qty: number;
    start_date: string | null;
    // ← NEW:
    sizes: SizeItem[];
    activity: ActivityItem[];
    documents: DocumentData[];
    modelMaterials: ModelMaterialData[];
}

interface Model extends ModelData {
    id: number;
    created_at: string;
    updated_at: string;
    created_by: number;
    updated_by: number | null;
}

interface FetchParams {
    search?: string;
    start_date?: string | null;
    end_date?: string | null;
    sort_field?: string;
    sort_order?: 'asc' | 'desc';
    per_page?: number;
}

export const useModelStore = defineStore('model', {
    state: () => ({
        loading: false,
        error: null as string | null,
        models: [] as Model[],
        total: 0,
        currentPage: 1,
        lastPage: 1,
    }),

    actions: {
        async createModel(data: ModelData) {
            try {
                this.loading = true;
                if (!data.start_date) {
                    throw new Error('Tanggal mulai harus diisi');
                }
                const response = await axios.post('/api/models', {
                    ...data,
                    start_date: data.start_date
                });
                return response.data;
            } catch (error: any) {
                this.error = error.response?.data?.message || 'Terjadi kesalahan saat membuat model';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchModels(params: FetchParams = {}) {
            try {
                this.loading = true;
                const response = await axios.get('/api/models/list', { params });
                this.models = response.data.data.data;
                this.total = response.data.data.total;
                this.currentPage = response.data.data.current_page;
                this.lastPage = response.data.data.last_page;
                return response.data;
            } catch (error: any) {
                this.error = error.response?.data?.message || 'Terjadi kesalahan saat mengambil data model';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchModelById(id: number) {
            try {
                this.loading = true;
                const response = await axios.get(`/api/models/${id}`);
                if (!response.data || !response.data.data) {
                    throw new Error('Data model tidak ditemukan');
                }
                return response.data;
            } catch (error: any) {
                this.error = error.response?.data?.message || error.message || 'Gagal mengambil data model';
                throw error;
            } finally {
                this.loading = false;
            }
        },
        
        async updateModel(id: number, data: Partial<ModelData>) {
            try {
                this.loading = true;
                const response = await axios.put(`/api/models/${id}`, data);
                const index = this.models.findIndex(model => model.id === id);
                if (index !== -1) {
                    this.models[index] = response.data.data;
                }
                return response.data;
            } catch (error: any) {
                this.error = error.response?.data?.message || 'Terjadi kesalahan saat memperbarui model';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async deleteModel(id: number) {
            try {
                this.loading = true;
                await axios.delete(`/api/models/${id}`);
                this.models = this.models.filter(model => model.id !== id);
            } catch (error: any) {
                this.error = error.response?.data?.message || 'Terjadi kesalahan saat menghapus model';
                throw error;
            } finally {
                this.loading = false;
            }
        }
    }
});