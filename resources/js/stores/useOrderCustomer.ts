import axios from 'axios';
import { ref } from 'vue';

interface customer {
    id: number;
    name: string;
}
export interface OrderItem {
    id: string;
    customer_id: number;
    customer: customer;
    total_amount: string;
    status: number;
    is_paid: string;
    resi_number: string;
    order_items: any[]; // sesuaikan tipe item jika ingin lebih akurat
    created_at: string;
    payment_method: string;
    payment_proof: string | null;
    updated_at: string;
    // tambahkan field lain jika perlu
}

interface PaginatedResponse<T> {
    current_page: number;
    data: T[];
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
}

export function useOrdersCustomer() {
    const orders = ref<OrderItem[]>([]);
    const isLoading = ref(true);
    const error = ref<string | null>(null);
    const pagination = ref<PaginatedResponse<OrderItem> | null>(null);
    const loading = ref(false);
    const rekening = ref(null);

    const fetchBankAccount = async () => {
    loading.value = true;
    try {
      const response = await axios.get('/api/bank-account');
      rekening.value = response.data;
    } catch (error) {
      console.error('Gagal mengambil rekening:', error);
      rekening.value = null;
    } finally {
      loading.value = false;
    }
  };

    const fetchOrders = async (params?: { status?: string; page?: number; per_page?: number; append?: boolean }) => {
        isLoading.value = true;
        error.value = null;
        try {
            const response = await axios.get<PaginatedResponse<OrderItem>>('/api/orders/customer', { params });
            pagination.value = response.data;
            if (params?.append) {
                orders.value = [...orders.value, ...response.data.data];
            } else {
                orders.value = response.data.data;
            }

        } catch (err) {
            console.error('Error fetching orders:', err);
            error.value = 'Failed to load orders. Please try again later.';
        } finally {
            isLoading.value = false;
        }
    };

    const cancelOrder = async (orderId: string) => {
        try {
            const confirmed = window.confirm('Apakah Anda yakin ingin membatalkan pesanan ini?');
            if (!confirmed) return;

            await axios.post(`/api/orders/${orderId}/cancel`);
            // Bisa juga pakai PUT atau PATCH tergantung API

            await fetchOrders();
            // await fetchOrderRequest(filters.value as any); // Cast to any because filters.value might not exactly match the params type

        } catch (err) {
            console.error('Gagal membatalkan pesanan:', err);
            error.value = 'Gagal membatalkan pesanan.';
        }
    };

    const deleteOrder = async (orderId: string) => {
        try {
            const confirmed = window.confirm('Apakah Anda yakin ingin membatalkan pesanan ini?');
            if (!confirmed) return;

            const cartId = Number(orderId.replace(/\D+/g, '')); 
            // convert string into number
            await axios.delete(`/api/cart-items/${cartId}/remove`);
            // Bisa juga pakai PUT atau PATCH tergantung API

            // Update local data atau refresh
            await fetchOrderRequest(filters.value as any);
        } catch (err) {
            console.error('Gagal membatalkan pesanan:', err);
            error.value = 'Gagal membatalkan pesanan.';
        }
    };

    const checkShipping = async (orderId: string) => {
        try {
            const response = await axios.get(`/api/orders/${orderId}/shipping`);
            return response.data; // kembalikan data ke komponen
        } catch (err) {
            console.error('Gagal mengambil status pengiriman:', err);
            throw new Error('Gagal mengambil status pengiriman');
        }
    };

    // untuk order request dari admin
    const fetchOrderRequest = async (params?: { status?: string; page?: number; per_page?: number; append?: boolean }) => {

        isLoading.value = true;
        error.value = null;
        try {
            const response = await axios.get<PaginatedResponse<OrderItem>>('/api/orders/request', { params });
            pagination.value = response.data;
            if (params?.append) {
                orders.value = [...orders.value, ...response.data.data];
            } else {
                orders.value = response.data.data;
            }

        } catch (err) {
            console.error('Error fetching orders:', err);
            error.value = 'Failed to load orders. Please try again later.';
        } finally {
            isLoading.value = false;
        }
    };
    

    async function updateShipping(orderId: string, formData: FormData) {
        try {
            const response = await axios.post(`/api/orders/${orderId}/shipping`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
                });
                return response.data;
        } catch (err) {
            console.error('Error updating shipping:', err);
            throw err;
        }
    }

    async function uploadPaymentProof(orderId: string, file: File) {
        try {
            const formData = new FormData();
            formData.append('payment_proof', file);

            const response = await axios.post(`/api/orders/${orderId}/upload-payment-proof`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            return response.data;
        } catch (err) {
            console.error('Error uploading payment proof:', err);
            throw err;
        }
    }

    const filters = ref<Record<string, string>>({});

    const setFilter = (field: string, value: string, params?: { page?: number; per_page?: number; append?: boolean }) => {
        filters.value[field] = value;
        fetchOrders({ [field]: value, ...params });
    };
    const setFilterOrderRequest = (field: string, value: string, params?: { status?: string; page?: number; per_page?: number; append?: boolean }) => {
        filters.value[field] = value;
        const newParams = { ...params, [field]: value };
        if (params?.status) {
            newParams.status = params.status;
        }
        fetchOrderRequest(newParams);
    };
    
    return {
        orders,
        isLoading,
        error,
        pagination,
        fetchOrders,
        fetchBankAccount,
        setFilter,
        setFilterOrderRequest,
        fetchOrderRequest,
        cancelOrder,
        deleteOrder,
        updateShipping,
        checkShipping,
        uploadPaymentProof,
    };
}
