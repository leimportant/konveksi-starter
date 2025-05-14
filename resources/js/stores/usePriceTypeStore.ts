import { defineStore } from 'pinia'
import axios from 'axios'

interface PriceType {
    id: number
    name: string
    is_active: true | false 
  }

export const usePriceTypeStore = defineStore('priceType', {
    state: () => ({
        priceTypes: [] as PriceType[],
        loading: false,
        loaded: false,  // Add this line
        error: null as string | null
    }),
    actions: {
        async fetchPriceTypes() {
            this.loading = true
            try {
                const { data } = await axios.get('/api/price-types')
                this.priceTypes = data.data
            } catch (error: any) {
                this.error = error.response?.data?.message || 'Failed to fetch price types'
                throw error
            } finally {
                this.loading = false
            }
        },
        async createPriceType(priceTypeData: Omit<PriceType, 'id'>) {
            this.loading = true
            try {
                const response = await axios.post('/api/price-types', priceTypeData)
                await this.fetchPriceTypes()
                return response.data
            } catch (error: any) {
                this.error = error.response?.data?.message || 'Failed to create price type'
                throw error
            } finally {
                this.loading = false
            }
        },
        async updatePriceType(id: number, priceTypeData: Partial<PriceType>) {
            this.loading = true
            try {
                const response = await axios.put(`/api/price-types/${id}`, priceTypeData)
                await this.fetchPriceTypes()
                return response.data
            } catch (error: any) {
                this.error = error.response?.data?.message || 'Failed to update price type'
                throw error
            } finally {
                this.loading = false
            }
        },
        async deletePriceType(id: number) {
            this.loading = true
            try {
                await axios.delete(`/api/price-types/${id}`)
                await this.fetchPriceTypes()
            } catch (error: any) {
                this.error = error.response?.data?.message || 'Failed to delete price type'
                throw error
            } finally {
                this.loading = false
            }
        }
    }
})