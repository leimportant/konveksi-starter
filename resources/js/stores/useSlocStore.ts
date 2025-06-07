import { defineStore } from 'pinia';
import axios from 'axios';

interface Sloc {
    id: string;
    name: string;
}

interface State {
  items: Sloc[];
  total: number;
  loaded: boolean;
  loading: boolean; // optional, if you want to track loading state
  currentPage: number;
  lastPage: number;
  filterName: string;
}

export const useSlocStore = defineStore('sloc', {
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
        async fetchSlocs(page = 1, perPage = 10) {
           this.loading = true;
            try {
                 const response = await axios.get('/api/slocs', {
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
                console.error('Failed to fetch Slocs', error);
            }
        },
         setFilter(field: string, value: string) {
            if (field === 'name') {
            this.filterName = value;
            this.currentPage = 1;
            this.fetchSlocs(1);
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