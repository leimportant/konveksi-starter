import { defineStore } from 'pinia';
import axios from 'axios';

interface Size {
    id: number;
    name: string;
}

export const useSizeStore = defineStore('size', {
    state: () => ({
        items: [] as Size[],
        loaded: false,
    }),

    actions: {
        async fetchSizes() {
            if (this.loaded) return;

            try {
                const response = await axios.get('/api/sizes');
                this.items = response.data.data;
                this.loaded = true;
            } catch (error) {
                console.error('Failed to fetch sizes', error);
            }
        },

        async createSize(name: string) {
            try {
                await axios.post('/api/sizes', { name });
                await this.fetchSizes();
            } catch (error) {
                throw error;
            }
        },

        async updateSize(id: number, name: string) {
            try {
                await axios.put(`/api/sizes/${id}`, { name });
                await this.fetchSizes();
            } catch (error) {
                throw error;
            }
        },

        async deleteSize(id: number) {
            try {
                await axios.delete(`/api/sizes/${id}`);
                await this.fetchSizes();
            } catch (error) {
                throw error;
            }
        }
    },
});