import { defineStore } from 'pinia'
import axios from 'axios'

interface PriceType {
    id: number
    name: string
    is_active: true | false 
  }

interface State {
  priceTypes: PriceType[];
  total: number;
  loaded: boolean;
  loading: boolean; // optional, if you want to track loading state
  currentPage: number;
  lastPage: number;
  filterName: string;
  error?: string; // optional, to track errors
}


export const usePriceTypeStore = defineStore('priceType', {
    state: (): State => ({
        priceTypes: [],
        total: 0,
        loaded: false,
        loading: false, // optional, if you want to track loading state
        currentPage: 1,
        lastPage: 1,
        filterName: '',
        error: undefined, // optional, to track errors
    }),

    actions: {
        async fetchPriceTypes(page = 1, perPage = 10) {
            this.loading = true
            try {
                const response = await axios.get('/api/price-types', {
                params: {
                    page,
                    perPage,
                    name: this.filterName,  // pakai filterName kalau ada
                }
                });
                this.priceTypes = response.data.data;
                this.total = response.data.total;
                this.currentPage = page;
                this.lastPage = response.data.last_page || 1;
                this.loaded = true;

            } catch (error: any) {
                this.error = error.response?.data?.message || 'Failed to fetch price types'
                throw error
            } finally {
                this.loading = false
            }
        },
        setFilter(field: string, value: string) {
            if (field === 'name') {
            this.filterName = value;
            this.currentPage = 1;
            this.fetchPriceTypes(1);
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