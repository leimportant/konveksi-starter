import { defineStore } from 'pinia'
import axios from 'axios'

interface Customer {
  id: number
  name: string
  address: string
  phone_number: string
  credit_limit: number
  is_active: 'Y' | 'N'
  created_at?: string
  updated_at?: string
}

interface State {
  customers: Customer[]
  customer: Customer | null
  loading: boolean
  error: string | null
  total: number
  currentPage: number
  lastPage: number
}

export const useCustomerStore = defineStore('customer', {
  state: (): State => ({
    customers: [],
    customer: null,
    loading: false,
    error: null,
    total: 0,
    currentPage: 1,
    lastPage: 1
  }),

  actions: {
    async fetchCustomers(page = 1, perPage = 10, search = '') {
      this.loading = true
      try {
        const params = new URLSearchParams({
          page: page.toString(),
          perPage: perPage.toString()
        })

        if (search) {
          params.append('name', search)
        }

        const response = await axios.get(`/api/customers?${params}`)
        this.customers = response.data.data
        this.total = response.data.total
        this.currentPage = response.data.current_page
        this.lastPage = response.data.last_page
        this.error = null
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Error fetching customers'
      } finally {
        this.loading = false
      }
    },

    async fetchCustomer(id: number) {
      this.loading = true
      try {
        const response = await axios.get(`/api/customers/${id}`)
        this.customer = response.data
        this.error = null
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Error fetching customer'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createCustomer(data: Partial<Customer>) {
      this.loading = true
      try {
        const response = await axios.post('/api/customers', data)
        this.error = null
        return response.data
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Error creating customer'
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateCustomer(id: number, data: Partial<Customer>) {
      this.loading = true
      try {
        const response = await axios.put(`/api/customers/${id}`, data)
        this.error = null
        return response.data
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Error updating customer'
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteCustomer(id: number) {
      this.loading = true
      try {
        await axios.delete(`/api/customers/${id}`)
        this.customers = this.customers.filter(customer => customer.id !== id)
        this.error = null
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Error deleting customer'
        throw error
      } finally {
        this.loading = false
      }
    }
  }
})