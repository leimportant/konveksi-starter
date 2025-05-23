import { defineStore } from 'pinia';
import axios from 'axios';

interface Sloc {
    id: string;
    name: string;
}

export const useSlocStore = defineStore('sloc', {
    state: () => ({
        items: [] as Sloc[],
        loaded: false,
    }),

    actions: {
        async fetchSlocs() {
            if (this.loaded) return;

            try {
                const response = await axios.get('/api/slocs');
                this.items = response.data.data;
                this.loaded = true;
            } catch (error) {
                console.error('Failed to fetch Slocs', error);
            }
        },

        async createSloc(id: string, name: string) {
            try {
                await axios.post('/api/slocs', { id, name });
                await this.fetchSlocs();
            } catch (error) {
                throw error;
            }
        },

        async updateSloc(id: string, name: string) {
            try {
                await axios.put(`/api/slocs/${id}`, { name });
                await this.fetchSlocs();
            } catch (error) {
                throw error;
            }
        },

        async deleteSloc(id: string) {
            try {
                await axios.delete(`/api/slocs/${id}`);
                await this.fetchSlocs();
            } catch (error) {
                throw error;
            }
        }
    },
});