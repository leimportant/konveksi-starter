import { defineStore } from 'pinia'
import axios from 'axios'

interface MealAllowance {
  id: number
  name: string
  amount: number
}

interface PaginationResponse<T> {
  data: T[]
  total: number
  last_page: number
}

interface State {
  mealAllowances: MealAllowance[]
  total: number
  loaded: boolean
  loading: boolean
  currentPage: number
  lastPage: number
  filterName: string
  error?: string
}

export const useMealAllowancesStore = defineStore('mealAllowances', {
  state: (): State => ({
    mealAllowances: [],
    total: 0,
    loaded: false,
    loading: false,
    currentPage: 1,
    lastPage: 1,
    filterName: '',
    error: undefined,
  }),

  actions: {
    async fetchMealAllowances(page = 1, perPage = 50) {
      this.loading = true
      this.error = undefined

      try {
        const { data } = await axios.get<PaginationResponse<MealAllowance>>('/api/meal-allowances', {
          params: {
            page,
            perPage,
            name: this.filterName || undefined,
          },
        })

        this.mealAllowances = data.data
        this.total = data.total
        this.currentPage = page
        this.lastPage = data.last_page || 1
        this.loaded = true
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Gagal memuat data meal allowance'
        throw error
      } finally {
        this.loading = false
      }
    },

    setFilter(field: string, value: string) {
      if (field === 'name') {
        this.filterName = value
        this.currentPage = 1
        this.fetchMealAllowances(1)
      }
    },

    async createMealAllowance(payload: Omit<MealAllowance, 'id'>) {
      this.loading = true
      try {
        const { data } = await axios.post('/api/meal-allowances', payload)
        await this.fetchMealAllowances(this.currentPage)
        return data
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Gagal membuat meal allowance'
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateMealAllowance(id: number, payload: Partial<MealAllowance>) {
      this.loading = true
      try {
        const { data } = await axios.put(`/api/meal-allowances/${id}`, payload)
        await this.fetchMealAllowances(this.currentPage)
        return data
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Gagal memperbarui meal allowance'
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteMealAllowance(id: number) {
      this.loading = true
      try {
        await axios.delete(`/api/meal-allowances/${id}`)
        await this.fetchMealAllowances(this.currentPage)
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Gagal menghapus meal allowance'
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})
