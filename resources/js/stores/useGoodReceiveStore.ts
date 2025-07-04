import axios from 'axios';
import { defineStore } from 'pinia';

interface GoodReceiveItem {
    id?: number;
    good_receive_id?: number;
    model_material_id: number;
    model_material_desc: string;
    model_material_item: number;
    qty: number;
    qty_convert: number;
    uom_base: string;
    uom_convert: string;
}
interface GoodReceive {
    id: number;
    date: string;
    model_id: number;
    description: string;
    recipent: string;
    created_at: string;
    updated_at: string;
    model?: {
        id: number;
        description: string;
    };
    items?: GoodReceiveItem[];
}

interface State {
    items: GoodReceive[];
    total: number;
    loading: boolean;
    loaded: boolean;
    currentPage: number;
    filterName: string;
}

export const useGoodReceiveStore = defineStore('goodReceive', {
    state: (): State => ({
        items: [],
        total: 0,
        loading: false,
        loaded: false,
        currentPage: 1,
        filterName: '',
    }),

    actions: {
        async fetchGoodReceives(page = 1, perPage = 10) {
            this.loading = true;
            try {
                const response = await axios.get('/api/good-receive', {
                params: {
                    page,
                    perPage,
                    name: this.filterName,  // pakai filterName kalau ada
                }
                });

                this.items = response.data.data;
                this.total = response.data.total;
                this.loaded = true; // optional: might not be needed anymore
            } catch (error) {
                console.error('Error fetching good receives:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchGoodReceivesById(id: number) {
            try {
                const response = await axios.get(`/api/good-receive/${id}`);
                if (!response.data || !response.data.data) {
                    throw new Error('Data not found');
                }
                return response.data;
            } catch (error) {
                console.error('Error fetching good receive by id:', error);
                throw error;
            }
        },
        setFilter(field: string, value: string) {
            if (field === 'name') {
                this.filterName = value;
                this.currentPage = 1;
                this.fetchGoodReceives(1);
                }
        },
        async createGoodReceive(data: Omit<GoodReceive, "id" | "created_at" | "updated_at">) {
            try {
                const response = await axios.post('/api/good-receive', data);
                this.items.unshift(response.data);
                this.total++;  // increase total count
                return response.data;
            } catch (error) {
                console.error('Error creating good receive:', error);
                throw error;
            }
        },

        async updateGoodReceive(id: number, data: Partial<GoodReceive>) {
            try {
                const response = await axios.put(`/api/good-receive/${id}`, data);
                const index = this.items.findIndex((item) => item.id === id);
                if (index !== -1) {
                    this.items[index] = response.data;
                }
                return response.data;
            } catch (error) {
                console.error('Error updating good receive:', error);
                throw error;
            }
        },

        async deleteGoodReceive(id: number) {
            try {
                await axios.delete(`/api/good-receive/${id}`);
                const index = this.items.findIndex((item) => item.id === id);
                if (index !== -1) {
                    this.items.splice(index, 1);
                    this.total--; // decrease total count
                }
            } catch (error) {
                console.error('Error deleting good receive:', error);
                throw error;
            }
        },
    },
});
