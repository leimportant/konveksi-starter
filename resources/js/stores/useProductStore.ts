import { defineStore } from 'pinia'
import axios from 'axios'

export const useProductStore = defineStore('product', {
  state: () => ({
    items: [] as any[],
    total: 0,               // total number of products
    loaded: false,
    loading: false, // for loading state
    currentPage: 1,         // for pagination
    filterName: '',         // for search
  }),

  actions: {
    async fetchProducts(page = 1, perPage = 10) {

      const params = {
          page,
          perPage,
          search: this.filterName, // gunakan filterName sebagai search query
        };
      this.loaded = false; // reset loaded state before fetching
      this.currentPage = page; // set current page for pagination
      try {

        const response = await axios.get('/api/products', { params });
        this.items = response.data.data;
        this.total = response.data.total;
        this.currentPage = page;
        this.loaded = true;
      } catch (error) {
        console.error('Failed to fetch products:', error);
        throw error;
      }
    },

    setFilter(field: string, value: string) {
      if (field === 'search' || field === 'name') {
        this.filterName = value;
        this.currentPage = 1;
        this.loaded = false;
        this.fetchProducts(1); // apply new filter
      }
    },

    async fetchProductById(id: number) {
      try {
        const response = await axios.get(`/api/products/${id}`);
        return response.data;
      } catch (error) {
        console.error('Failed to fetch product by ID:', error);
        throw error;
      }
    },

    async createProduct(data: { name: string; category_id: number; uom_id: string }) {
      try {
        await axios.post('/api/products', {
          name: data.name,
          category_id: data.category_id,
          uom_id: data.uom_id,
        });
        this.loaded = false;
        await this.fetchProducts();
      } catch (error) {
        console.error('Failed to create product:', error);
        throw error;
      }
    },

    async updateProduct(id: number, data: { name: string; category_id: number; uom_id: string }) {
      try {
        await axios.put(`/api/products/${id}`, data);
        this.loaded = false;
        await this.fetchProducts(this.currentPage);
      } catch (error) {
        console.error('Failed to update product:', error);
        throw error;
      }
    },

    async deleteProduct(id: number) {
      try {
        await axios.delete(`/api/products/${id}`);
        this.loaded = false;
        await this.fetchProducts(this.currentPage);
      } catch (error) {
        console.error('Failed to delete product:', error);
        throw error;
      }
    }
  }
});
