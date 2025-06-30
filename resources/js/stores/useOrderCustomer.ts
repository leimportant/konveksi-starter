import { ref } from 'vue';
import axios from 'axios';

export interface OrderItem {
  id: string;
  customer_id: number;
  total_amount: string;
  status: number;
  is_paid: string;
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

  const fetchOrders = async () => {
    isLoading.value = true;
    error.value = null;
    try {
      const response = await axios.get<PaginatedResponse<OrderItem>>('/api/orders/customer');
      pagination.value = response.data;
      orders.value = response.data.data;
    } catch (err) {
      console.error('Error fetching orders:', err);
      error.value = 'Failed to load orders. Please try again later.';
    } finally {
      isLoading.value = false;
    }
  };

  return {
    orders,
    isLoading,
    error,
    pagination,
    fetchOrders,
  };
}
