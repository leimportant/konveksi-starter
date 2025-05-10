import { defineStore } from 'pinia';
import axios from 'axios';

interface Uom {
    id: string;
    name: string;
}

export const useUomStore = defineStore('uom', {
    state: () => ({
        items: [] as Uom[],
        loaded: false,
    }),

    actions: {
        async fetchUoms() {
            if (this.loaded) return;

            try {
                const response = await axios.get('/api/uoms');
                this.items = response.data.data;
                this.loaded = true;
            } catch (error) {
                console.error('Failed to fetch UOMs', error);
            }
        },

        async createUom(name: string) {
            try {
                await axios.post('/api/uoms', { name });
                await this.fetchUoms();
            } catch (error) {
                throw error;
            }
        },

        async updateUom(id: number, name: string) {
            try {
                await axios.put(`/api/uoms/${id}`, { name });
                await this.fetchUoms();
            } catch (error) {
                throw error;
            }
        },

        async deleteUom(id: number) {
            try {
                await axios.delete(`/api/uoms/${id}`);
                await this.fetchUoms();
            } catch (error) {
                throw error;
            }
        }
    },
});