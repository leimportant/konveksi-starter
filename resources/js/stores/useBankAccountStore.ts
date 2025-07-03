import { defineStore } from 'pinia';
import axios from 'axios';

interface BankAccount {
    id: string;
    name: string;
    account_number: string;
}

interface State {
  bankAccount: BankAccount[];
  total: number;
  loaded: boolean;
  isLoading: false,
  loading: boolean; // optional, if you want to track loading state
  currentPage: number;
  lastPage: number;
  filterName: string;
  error: ''
}

export const useBankAccountStore = defineStore('bankAccount', {
   state: (): State => ({
        bankAccount: [],
        total: 0,
        loaded: false,
        loading: false, // optional, if you want to track loading state
        isLoading: false,
        currentPage: 1,
        lastPage: 1,
        filterName: '',
        error: ''
    }),

    actions: {
        async fetchBankAccount(page = 1, perPage = 10) {
            if (this.loaded) return;

            try {
                const response = await axios.get('/api/bank-account', {
                params: {
                    page,
                    perPage,
                    name: this.filterName,  // pakai filterName kalau ada
                }
                });

                this.bankAccount = response.data.data;
                this.total = response.data.total;
                this.currentPage = page;
                this.lastPage = response.data.last_page || 1;
                this.loaded = true;

            } catch (error) {
                console.error('Failed to fetch bank-account', error);
            }
        },
        setFilter(field: string, value: string) {
            if (field === 'name') {
                this.filterName = value;
                this.currentPage = 1;
                this.loaded = false; // <--- Tambahkan ini
                this.fetchBankAccount(1);
            }
        },
        async createBankAccount(id: string, name: string, account_number: string) {
            try {
                await axios.post('/api/bank-account', { id, name, account_number });
                await this.fetchBankAccount();
            } catch (error) {
                throw error;
            }
        },

        async updateBankAccount(id: string, name: string, account_number: string) {
            try {
                await axios.put(`/api/bank-account/${id}`, { id, name, account_number });
                await this.fetchBankAccount();
            } catch (error) {
                throw error;
            }
        },

        async deleteBankAccount(id: string) {
            try {
                await axios.delete(`/api/bank-account/${id}`);
                await this.fetchBankAccount();
            } catch (error) {
                throw error;
            }
        }
    },
});