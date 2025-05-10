import { defineStore } from 'pinia'
import axios from 'axios'

export const useProductStore = defineStore('product', {
  state: () => ({
    items: [] as any[],
    loaded: false
  }),
  actions: {
    async fetchProducts() {
      const response = await axios.get('/api/products')
      this.items = response.data.data
      this.loaded = true
    },
    async fetchProductById(id: number) {
      const response = await axios.get(`/api/products/${id}`)
      return response.data
    },
    async createProduct(data: { name: string; category_id: number; uom_id: string }) {
      try {
        await axios.post('/api/products', {
          name: data.name,
          category_id: data.category_id,
          uom_id: data.uom_id
        });
        this.loaded = false;
        await this.fetchProducts();
      } catch (error) {
        throw error;
      }
    },
    async updateProduct(id: number, data: { name: string; category_id: number; uom_id: string }) {
      try {
        await axios.put(`/api/products/${id}`, data);
        this.loaded = false;
        await this.fetchProducts();
      } catch (error) {
        throw error;
      }
    },
    async deleteProduct(id: number) {
      try {
        await axios.delete(`/api/products/${id}`);
        this.loaded = false;
        await this.fetchProducts();
      } catch (error) {
        throw error;
      }
    }
  }
})