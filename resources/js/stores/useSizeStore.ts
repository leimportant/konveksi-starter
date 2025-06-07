import { defineStore } from 'pinia';
import axios from 'axios';

interface Size {
    id: number;
    name: string;
}


interface State {
  sizes: Size[];
  total: number;
  loaded: boolean;
  isLoading: false,
  loading: boolean; // optional, if you want to track loading state
  currentPage: number;
  lastPage: number;
  filterName: string;
  error: ''
}

export const useSizeStore = defineStore('size', {
   state: (): State => ({
        sizes: [],
        total: 0,
        loaded: false,
        loading: false, // optional, if you want to track loading state
        isLoading: false,
        currentPage: 1,
        lastPage: 1,
        filterName: '',
        error: ''
    }),

    actions: {
        async fetchSizes(page = 1, perPage = 10) {
            if (this.loaded) return;

            try {
                const response = await axios.get('/api/sizes', {
                params: {
                    page,
                    perPage,
                    name: this.filterName,  // pakai filterName kalau ada
                }
                });

                this.sizes = response.data.data;
                this.total = response.data.total;
                this.currentPage = page;
                this.lastPage = response.data.last_page || 1;
                this.loaded = true;

            } catch (error) {
                console.error('Failed to fetch Sizes', error);
            }
        },
        setFilter(field: string, value: string) {
            if (field === 'name') {
                this.filterName = value;
                this.currentPage = 1;
                this.loaded = false; // <--- Tambahkan ini
                this.fetchSizes(1);
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