import { defineStore } from 'pinia';
import axios from 'axios';

interface Category {
    id: number;
    name: string;
}

export const useCategoryStore = defineStore('category', {
    state: () => ({
        items: [] as Category[],
        loaded: false,
    }),

    actions: {
        async fetchCategories() {
            if (this.loaded) return;

            try {
                const response = await axios.get('/api/categories');
                this.items = response.data.data;
                this.loaded = true;
            } catch (error) {
                console.error('Failed to fetch Category', error);
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