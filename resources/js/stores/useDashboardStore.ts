import { defineStore } from 'pinia';
import axios from 'axios';

interface DashboardStats {
    total_order: number;
    total_transactions: number;
    total_products: number;
    total_users: number;
}

interface State {
    stats: DashboardStats;
    loading: boolean;
    loaded: boolean;
}

export const useDashboardStore = defineStore('dashboard', {
    state: (): State => ({
        stats: {
            total_order: 0,
            total_transactions: 0,
            total_products: 0,
            total_users: 0
        },
        loading: false,
        loaded: false
    }),

    actions: {
        async fetchStats() {
            if (this.loaded) return;

            this.loading = true;
            try {
                const response = await axios.get('/api/dashboard');
                this.stats = response.data.stats;
                this.loaded = true;
            } catch (error) {
                console.error('Error fetching dashboard stats:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        }
    }
});