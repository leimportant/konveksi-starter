<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Order from '@/components/Order/Order.vue';

interface OrderItem { 
  id: number;
  status: string;
  // Add other properties of your order object here as needed
}

const orders = ref<OrderItem[]>([]);
const selectedStatus = ref('all');
const isLoading = ref(true);
const error = ref<string | null>(null);

const availableStatuses = [
  { value: 'all', label: 'All' },
  { value: 'pending', label: 'Pending' },
  { value: 'di kemas', label: 'Di Kemas' },
  { value: 'on progress', label: 'On Progress' },
  { value: 'done', label: 'Done' },
  { value: 'cancel', label: 'Cancel' },
];

const fetchOrders = async () => {
  isLoading.value = true;
  error.value = null;
  try {
    const response = await axios.get('/api/orders/customer', {
      params: {
        status: selectedStatus.value === 'all' ? null : selectedStatus.value,
      },
    });
    orders.value = response.data.data;
  } catch (err) {
    console.error('Error fetching orders:', err);
    error.value = 'Failed to load orders. Please try again later.';
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  fetchOrders();
});

const filteredOrders = computed(() => {
  if (selectedStatus.value === 'all') {
    return orders.value;
  }
  return orders.value.filter(order => order.status === selectedStatus.value);
});
</script>

<template>
  <div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-6">Order History</h1>

    <div class="mb-4">
      <label for="status-filter" class="block text-sm font-medium text-gray-700">Filter by Status:</label>
      <select
        id="status-filter"
        v-model="selectedStatus"
        @change="fetchOrders"
        class="mt-1 block w-full md:w-1/3 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
      >
        <option v-for="status in availableStatuses" :key="status.value" :value="status.value">
          {{ status.label }}
        </option>
      </select>
    </div>

    <div v-if="isLoading" class="text-center text-gray-500">Loading orders...</div>
    <div v-else-if="error" class="text-center text-red-500">{{ error }}</div>
    <div v-else-if="filteredOrders.length === 0" class="text-center text-gray-500">No orders found.</div>
    <div v-else class="space-y-4">
      <Order v-for="order in filteredOrders" :key="order.id" :order="order" />
    </div>
  </div>
</template>

<style scoped>
/* Add any component-specific styles here */
</style>