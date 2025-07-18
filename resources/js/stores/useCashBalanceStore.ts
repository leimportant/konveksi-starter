// stores/cashBalance.ts
import { defineStore } from 'pinia'
import axios from 'axios'

interface CashBalanceOpenPayload {
    shift_number: number
    opening_balance: number
}

interface CashBalanceClosePayload {
    cash_sales_total: number
    cash_in: number
    cash_out: number
    closing_balance: number
    discrepancy: number
    notes?: string
}

interface CashBalanceItem {
  id: number
  cashier_id: number
  shift_date: string
  shift_number: number
  opening_balance: number
  cash_sales_total: number
  cash_in: number
  cash_out: number
  closing_balance: number | null
  discrepancy: number | null
  notes?: string
  opened_at: string
  closed_at?: string | null
  status: 'open' | 'closed'
}


export const useCashBalanceStore = defineStore('cashBalance', {
    state: () => ({
        items: [] as CashBalanceItem[],
        total: 0,
        lastPage: 1,
        currentPage: 1,
        perPage: 10,
        filterName: '',
        loading: false,
        loaded: false,
        error: null
    }),
    actions: {
        async fetchCashBalances(page = 1, perPage = 10) {
            this.loading = true
            try {
                const response = await axios.get('/api/cash-balance', {
                    params: {
                        page,
                        perPage,
                        name: this.filterName
                    }
                })

                this.items = response.data.data
                this.total = response.data.total
                this.lastPage = response.data.last_page
                this.currentPage = page
                this.perPage = perPage
                this.loaded = true
                this.error = null
            } catch (error) {
                 throw error
            } finally {
                this.loading = false
            }
        },

        setFilter(field: string, value: string) {
            if (field === 'name') {
                this.filterName = value
                this.currentPage = 1
                this.fetchCashBalances(1)
            }
        },

        async createCashBalance(payload: CashBalanceOpenPayload) {
            try {
                const response = await axios.post('/api/cash-balance/open', payload)
                this.error = null
                return response.data
            } catch (error) {
                throw error
            }
        },

        async closing(id: number, payload: CashBalanceClosePayload) {
            try {
                const response = await axios.put(`/api/cash-balance/${id}/close`, payload)
                this.error = null
                return response.data
            } catch (error) {
                throw error
            }
        }

    }
})
