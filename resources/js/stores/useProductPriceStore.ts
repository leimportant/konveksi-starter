import { defineStore } from 'pinia'
import axios from 'axios'

interface ProductPrice {
    id: number
    product_id: number
    price_type_id: number
    price: number
    effective_date: string
    is_active: boolean
    product?: {
        name: string
    }
    price_type?: {
        name: string
    }
}

interface Product {
    id: number
    name: string
}

interface PriceType {
    id: number
    name: string
}

export const useProductPriceStore = defineStore('productPrice', {
    state: () => ({
        productPrices: [] as ProductPrice[],
        products: [] as Product[],
        priceTypes: [] as PriceType[],
        loading: false,
        loaded: false,
        error: null as string | null
    }),

    actions: {
        async fetchAll() {
            this.loading = true
            try {
                const [pricesRes, productsRes, typesRes] = await Promise.all([
                    axios.get('/api/product-prices', { params: { include: 'product,priceType' } }),
                    axios.get('/api/products'),
                    axios.get('/api/price-types')
                ])
                
                this.productPrices = pricesRes.data.data
                this.products = productsRes.data.data
                this.priceTypes = typesRes.data.data
                this.loaded = true
            } catch (error: any) {
                this.error = error.response?.data?.message || 'Failed to fetch data'
                throw error
            } finally {
                this.loading = false
            }
        },

        async createProductPrice(data: Omit<ProductPrice, 'id'>) {
            this.loading = true
            try {
                const response = await axios.post('/api/product-prices', data)
                await this.fetchAll()
                return response.data
            } catch (error: any) {
                this.error = error.response?.data?.message || 'Failed to create product price'
                throw error
            } finally {
                this.loading = false
            }
        },

        async updateProductPrice(id: number, data: Partial<ProductPrice>) {
            this.loading = true
            try {
                const response = await axios.put(`/api/product-prices/${id}`, data)
                await this.fetchAll()
                return response.data
            } catch (error: any) {
                this.error = error.response?.data?.message || 'Failed to update product price'
                throw error
            } finally {
                this.loading = false
            }
        },

        async deleteProductPrice(id: number) {
            this.loading = true
            try {
                await axios.delete(`/api/product-prices/${id}`)
                await this.fetchAll()
            } catch (error: any) {
                this.error = error.response?.data?.message || 'Failed to delete product price'
                throw error
            } finally {
                this.loading = false
            }
        }
    }
})
