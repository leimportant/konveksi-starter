import axios from 'axios';
import { defineStore } from 'pinia';

interface GoodReceiveItem {
    id?: number; // Make optional since new items won't have an ID
    good_receive_id?: number; // Make optional for new items
    model_material_id: number;
    model_material_item: string;
    qty: number;
    qty_convert: number;
    uom_base: string;
    uom_convert: string;
}
interface GoodReceive {
    id: number;
    date: string;
    model_id: number;
    recipent: string;
    model?: {
        id: number;
        description: string;
    };
    good_receive_items?: GoodReceiveItem[];
}

interface State {
    items: GoodReceive[];
    loading: boolean;
    loaded: boolean;
}

export const useGoodReceiveStore = defineStore('goodReceive', {
    state: (): State => ({
        items: [],
        loading: false,
        loaded: false,
    }),

    actions: {
        async fetchGoodReceives() {
            if (this.loaded) return;

            this.loading = true;
            try {
                const response = await axios.get('/api/good-receive');
                this.items = response.data.data;
                this.loaded = true;
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
                    throw new Error('Data tidak ditemukan');
                }
                return response.data;
            } catch (error) {
                console.error('Error fetching good receive by id:', error);
                throw error;
            }
        },

        async createGoodReceive(data: Omit<GoodReceive, 'id'>) {
            try {
                const response = await axios.post('/api/good-receive', data);
                this.items.unshift(response.data);
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
                }
            } catch (error) {
                console.error('Error deleting good receive:', error);
                throw error;
            }
        },
    },
});
