// resources/js/stores/useKasbonStore.ts
import { defineStore } from 'pinia'
import axios from 'axios'

interface Kasbon {
  id?: string
  employee_id: number
  employee: Employee
  amount: number
  description: string
  status: string
  created_by: number
  updated_by: number
  approved_by?: number | null
  rejected_by?: number | null
  created_at?: string
  updated_at?: string
  approved_at?: string | null
  rejected_at?: string | null
}

interface Employee {
  id: number
  name: string
}

interface MutasiKasbonResponse {
  status: string
  data: {
    mutasi: {
      id: string
      kasbon_id: number
      employee_name: string
      amount: number
      type: string
      description: string
      kasbon_status: string
      tanggal: string
      created_at: string
    }[]
    summary: {
      employee_id: number
      total_kasbon: number
      total_pembayaran: number
      saldo: number
    }[]
  }
}

export const useKasbonStore = defineStore('kasbon', {
  state: () => ({
    kasbonList: [] as Kasbon[],
    mutasiList: [] as MutasiKasbonResponse['data']['mutasi'],
    summary: [] as MutasiKasbonResponse['data']['summary'],
    pagination: {
      current_page: 1,
      per_page: 50,
      total: 0,
    },
    loading: false,
    error: null as string | null,
  }),

  actions: {
    async fetchKasbon(page = 1, perPage = 50, filters: { search?: string; status?: string } = {}) {
      this.loading = true
      try {
        const params = {
          page,
          per_page: perPage,
          search: filters.search,
          status: filters.status,
        };
        const response = await axios.get('/api/kasbon', { params })
        this.kasbonList = response.data.data ?? []
        this.pagination.current_page = response.data.current_page ?? 1
        this.pagination.per_page = response.data.per_page ?? 50
        this.pagination.total = response.data.total ?? 0
      } catch (err: any) {
        this.error = err.response?.data?.message ?? err.message
      } finally {
        this.loading = false
      }
    },

    async storeKasbon(payload: Partial<Kasbon>) {
      this.loading = true
      try {
        await axios.post('/api/kasbon/store', payload)
        await this.fetchKasbon()
      } catch (err: any) {
        this.error = err.response?.data?.message ?? err.message
        throw err
      } finally {
        this.loading = false
      }
    },

    async updateKasbon(id: string, payload: Partial<Kasbon>) {
      this.loading = true
      try {
        await axios.put(`/api/kasbon/${id}`, payload)
        await this.fetchKasbon()
      } catch (err: any) {
        this.error = err.response?.data?.message ?? err.message
        throw err
      } finally {
        this.loading = false
      }
    },

    async deleteKasbon(id: string) {
      if (!confirm('Yakin ingin menghapus kasbon ini?')) return
      this.loading = true
      try {
        await axios.delete(`/api/kasbon/${id}`)
        await this.fetchKasbon()
      } catch (err: any) {
        this.error = err.response?.data?.message ?? err.message
      } finally {
        this.loading = false
      }
    },

    async approveKasbon(id: string) {
      this.loading = true
      try {
        await axios.post(`/api/kasbon/${id}/approve`)
        await this.fetchKasbon()
      } catch (err: any) {
        this.error = err.response?.data?.message ?? err.message
        throw err
      } finally {
        this.loading = false
      }
    },

    async rejectKasbon(id: string, remark: string) {
      this.loading = true
      try {
        await axios.post(`/api/kasbon/${id}/reject`, { remark })
        await this.fetchKasbon()
      } catch (err: any) {
        this.error = err.response?.data?.message ?? err.message
        throw err
      } finally {
        this.loading = false
      }
    },

    async fetchMutasi(page = 1, perPage = 50, filters: {
      search?: string
      status?: string
      employee_id?: number
      start_date?: string
      end_date?: string
    } = {}) {
      this.loading = true
      try {
        const params = {
          page,
          per_page: perPage,
          ...filters,
        };

        const response = await axios.get('/api/kasbon/mutasi', { params })
        this.mutasiList = response.data.data ?? []
        this.pagination.current_page = response.data.current_page ?? 1
        this.pagination.per_page = response.data.per_page ?? 50
        this.pagination.total = response.data.total ?? 0

      } catch (err: any) {
        this.error = err.response?.data?.message ?? err.message
        throw err
      } finally {
        this.loading = false
      }
    },
  },
})
