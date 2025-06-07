import axios from 'axios';
import { defineStore } from 'pinia';

interface Product { id: number; name: string }
interface PriceType { id: number; name: string }
interface UOM { id: number; name: string }
interface Size { id: number; name: string }

// Main ProductPrice type (for existing records)
interface ProductPrice {
  id: number;
  product_id: number;
  product?: Product;
  cost_price: number;
  effective_date: string;
  end_date: string | null;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

// Structure of each price detail line sent when creating/updating
interface PriceTypeDetail {
  price_type_id: number;
  size_id: string;
  price: number;
  discount: number;
  price_sell: number;
}

// Payload shape when creating a new product price with details
interface ProductPriceCreatePayload {
  product_id: number;
  effective_date: string;
  cost_price: number;
  price_types: PriceTypeDetail[];
}

export const useProductPriceStore = defineStore('productPrice', {
  state: () => ({
    productPrices: [] as ProductPrice[],
    total: 0,
    loading: false,
    loaded: false,
    currentPage: 1, // for pagination
    filterName: '', // for search/filtering by product name
    products: [] as Product[],
    priceTypes: [] as PriceType[],
    uoms: [] as UOM[],
    sizes: [] as Size[],
  }),

  actions: {
    async fetchProductPrice(page = 1, perPage = 10) {
      this.loading = true;
      try {
        const response = await axios.get('/api/product-prices', {
          params: {
            page,
            perPage,
            search: this.filterName, // Gunakan filter di query
          }
        });
        this.productPrices = response.data.data;
        this.total = response.data.total;
      } catch (error) {
        console.error('Failed to fetch product prices:', error);
        throw error;
      } finally {
        this.loading = false;
      }
    },
    setFilter(field: string, value: string) {
        if (field === 'name') {
          this.filterName = value;
          console.log('Filter set:', this.filterName);
          this.fetchProductPrice(1);  // fetch page 1 with filter
        }
      },
    async fetchProductPriceById(id: number) {
      try {     
        const response = await axios.get(`/api/product-prices/${id}`);
        return response.data;
      } catch (error) {
        console.error('Failed to fetch product price by ID:', error);   
        throw error;
      }
    },

    async createProductPrice(data: ProductPriceCreatePayload) {
      try {
        const response = await axios.post('/api/product-prices', data);
        this.productPrices.unshift(response.data);
        this.total++;
        return response.data;
      } catch (error) {
        console.error('Failed to create product price:', error);
        throw error;
      }
    },

   async updateProductPrice(id: number, data: ProductPriceCreatePayload) {
      try {
        const response = await axios.put(`/api/product-prices/${id}`, data);
        const index = this.productPrices.findIndex((p) => p.id === id);
        if (index !== -1) {
          this.productPrices[index] = response.data;
        }
        return response.data;
      } catch (error) {
        console.error('Failed to update product price:', error);
        throw error;
      }
    },

    async deleteProductPrice(id: number) {
      try {
        await axios.delete(`/api/product-prices/${id}`);
        const index = this.productPrices.findIndex((p) => p.id === id);
        if (index !== -1) {
          this.productPrices.splice(index, 1);
          this.total--;
        }
      } catch (error) {
        console.error('Failed to delete product price:', error);
        throw error;
      }
    },

    // Example fetching of products, priceTypes, uoms, sizes if needed
    async fetchAll() {
      try {
        const [productsRes, priceTypesRes, uomsRes, sizesRes] = await Promise.all([
          axios.get('/api/products'),
          axios.get('/api/price-types'),
          axios.get('/api/uoms'),
          axios.get('/api/sizes'),
        ]);
        this.products = productsRes.data.data;
        this.priceTypes = priceTypesRes.data.data;
        this.uoms = uomsRes.data.data;
        this.sizes = sizesRes.data.data;
      } catch (error) {
        console.error('Failed to fetch auxiliary data:', error);
      }
    },
  },
});
