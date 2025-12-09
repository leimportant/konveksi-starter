import { defineStore } from 'pinia'
import axios from 'axios'

interface Product {
  id: number;
  name: string;
  description: string;
  price: number;
  price_sell?: number;
  image: string;
  stock: number;
  category_id: number;
  size_id: string;
  variant: string;
  uom_id: string;
  discount?: number;
}

interface CartItem {
  id: number;
  product_id: number;
  quantity: number;
  price: number;
  discount?: number;
  price_sell?: number;
  price_sell_grosir?: number;
  price_grosir?: number;
  discount_grosir?: number;
  size_id?: string;
  variant?: string;
  sloc_id?: string;
  location_id?: number;
  uom_id?: string;
  created_by: number;
  updated_by?: number;
  created_at: string;
  updated_at?: string;
  product: Product;
}

interface State {
  cartItems: CartItem[]
  loading: boolean
  error: string | null
}

export const useCartStore = defineStore('cart', {
  state: (): State => ({
    cartItems: [],
    loading: false,
    error: null,
  }),

  actions: {
    async fetchCartItems() {
      this.loading = true
      try {
        const response = await axios.get('/api/cart-items');
        const cartItemsData = response.data;

        const itemsWithProducts = await Promise.all(cartItemsData.map(async (item: any) => {
          if (!item.product) {
            const productResponse = await axios.get(`/api/products/${item.product_id}`);
            return {
              ...item,
              product: productResponse.data
            };
          }
          return item;
        }));

        this.cartItems = itemsWithProducts;
        this.error = null
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Error fetching cart items'
      } finally {
        this.loading = false
      }
    },
    async addToCart(productId: number, quantity: number, sizeId: string, uomId: string, price: number, discount: number, price_sell: number, location_id: number, variant: string, sloc_id: string) {
      this.loading = true
      try {
        const response = await axios.post('/api/cart-items/add', {
          product_id: productId,
          quantity: quantity,
          price: price,
          location_id: location_id,
          variant: variant,
          sloc_id: sloc_id,
          discount: discount,
          price_sell: price_sell, // Assuming price_sell is the same as price for now
          size_id: sizeId,
          uom_id: uomId
        })
        this.error = null
        await this.fetchCartItems() // Refresh cart items after adding
        return response.data
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Error adding to cart'
        throw error
      } finally {
        this.loading = false
      }
    },

    async removeFromCart(cartItemId: number) {
      this.loading = true
      try {
        await axios.delete(`/api/cart-items/${cartItemId}/remove`)
        this.error = null
        await this.fetchCartItems() // Refresh cart items after removing
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Error removing from cart'
        throw error
      } finally {
        this.loading = false
      }
    },

    async clearCart() {
      this.loading = true
      try {
        await axios.delete('/api/cart-items/clear')
        this.cartItems = [] // Clear cart items locally
        this.error = null
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Error clearing cart'
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})