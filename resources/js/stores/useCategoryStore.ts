import { defineStore } from 'pinia';
import axios from 'axios';

interface Category {
    id: number;
    name: string;
}

interface State {
  items: Category[];
  total: number;
  loaded: boolean;
  loading: boolean; // optional, if you want to track loading state
  currentPage: number;
  lastPage: number;
  filterName: string;
}

export const useCategoryStore = defineStore('category', {
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
       async fetchCategories(page = 1, perPage = 10) {
            this.loaded = false;   // reset loading
            this.currentPage = page;

            try {
                const response = await axios.get('/api/categories', {
                params: {
                    page,
                    perPage,
                    name: this.filterName,  // pakai filterName kalau ada
                }
                });

                this.items = response.data.data;
                this.total = response.data.total;
                this.lastPage = response.data.last_page;  // pastikan backend kirim last_page
                this.loaded = true;
            } catch (error) {
                console.error('Failed to fetch Category', error);
                this.loaded = true;
            }
        },
        setFilter(field: string, value: string) {
            if (field === 'name') {
                this.filterName = value;
                this.currentPage = 1;
                this.fetchCategories(1);
                }
        },
        async createCategory(name: string) {
            try {
                await axios.post('/api/categories', { name });
                await this.fetchCategories();
            } catch (error) {
                throw error;
            }
        },

        async updateCategory(id: number, name: string) {
            try {
                await axios.put(`/api/categories/${id}`, { name });
                await this.fetchCategories();
            } catch (error) {
                throw error;
            }
        },

        async deleteCategory(id: number) {
            try {
                await axios.delete(`/api/categories/${id}`);
                await this.fetchCategories();
            } catch (error) {
                throw error;
            }
        }
    },
});