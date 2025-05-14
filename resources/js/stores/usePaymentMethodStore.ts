import { defineStore } from 'pinia';
import axios from 'axios';

interface PaymentMethod  {
    id: string;
    name: string;
}

export const usePaymentMethodStore = defineStore('paymentMethod', {
    state: () => ({
        items: [] as PaymentMethod[],
        loaded: false,
    }),

    actions: {
        async fetchPaymentMethods() {
            if (this.loaded) return;

            try {
                const response = await axios.get('/api/payment-methods');
                this.items = response.data.data;
                this.loaded = true;
            } catch (error) {
                console.error('Failed to fetch payment method', error);
            }
        },

        async createPaymentMethod(name: string) {
            try {
                await axios.post('/api/payment-methods', { name });
                await this.fetchPaymentMethods();
            } catch (error) {
                throw error;
            }
        },

        async updatePaymentMethod(id: number, name: string) {
            try {
                await axios.put(`/api/payment-methods/${id}`, { name });
                await this.fetchPaymentMethods();
            } catch (error) {
                throw error;
            }
        },

        async deletePaymentMethod(id: string) {
            try {
                await axios.delete(`/api/payment-methods/${id}`);
                await this.fetchPaymentMethods();
            } catch (error) {
                throw error;
            }
        }
    },
});