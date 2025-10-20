import { defineStore } from 'pinia';
import axios from 'axios';

interface Uom {
    id: string;
    name: string;
}

interface State {
  items: Uom[];
  total: number;
  loaded: boolean;
  loading: boolean; // optional, if you want to track loading state
  currentPage: number;
  lastPage: number;
  filterName: string;
}

export const useUomStore = defineStore('uom', {
    state: (): State => ({
        items: [],
        total: 0,
        loaded: false,
        loading: false, // optional, if you want to track loading state
        currentPage: 1,
        lastPage: 1,
        filterName: '',
    }),

    actions: {
       async fetchUoms(page = 1, perPage = 50) {
            this.loading = true;
            try {

                const response = await axios.get('/api/uoms', {
                params: {
                    page,
                    perPage,
                    name: this.filterName,  // pakai filterName kalau ada
                }
                });

                this.items = response.data.data;
                this.total = response.data.total;
                this.currentPage = page;
                this.lastPage = response.data.last_page || 1;
                this.loaded = true;
            } catch (error) {
             console.error(error);
            } finally {
                this.loading = false;
            }
        },
        setFilter(field: string, value: string) {
            if (field === 'name') {
            this.filterName = value;
            this.currentPage = 1;
            this.fetchUoms(1);
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

        async deleteUom(id: string) {
            try {
                await axios.delete(`/api/uoms/${id}`);
                await this.fetchUoms();
            } catch (error) {
                throw error;
            }
        }
    },
});