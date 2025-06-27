<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Order from '@/components/Order/Order.vue';

interface OrderItem {
  id: number;
  status: string;
  total_pembelian: number;
  products_count: number;
  // Tambahkan properti lain sesuai kebutuhan
}

const orders = ref<OrderItem[]>([]);
const activeTab = ref<'all' | 'done'>('all');
const isLoading = ref(true);
const error = ref<string | null>(null);

const fetchOrders = async () => {
  isLoading.value = true;
  error.value = null;
  try {
    const response = await axios.get('/api/orders/customer');
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
  if (activeTab.value === 'done') {
    return orders.value.filter(order => order.status === 'done');
  }
  return orders.value;
});

const totalProduk = computed(() =>
  orders.value.reduce((sum, order) => sum + (order.products_count || 0), 0)
);

const totalOrder = computed(() => orders.value.length);

const totalPembelian = computed(() =>
  orders.value.reduce((sum, order) => sum + (order.total_pembelian || 0), 0)
);
</script>

<template>

  <Head title="Order" />
  <AppLayout>
    <div class="container mx-auto p-4">
      <!-- Dashboard Header -->
      <div class="flex flex-wrap justify-between gap-2 mb-6">
        <div class="bg-indigo-100 shadow rounded-lg p-4 text-center w-[32%] min-w-[100px] break-words">
          <p class="text-indigo-800 text-xs sm:text-sm md:text-base font-medium">Total Produk</p>
          <p class="text-lg sm:text-xl md:text-2xl font-bold text-indigo-900">{{ totalProduk }}</p>
        </div>
        <div class="bg-green-100 shadow rounded-lg p-4 text-center w-[30%] min-w-[100px] break-words">
          <p class="text-green-800 text-xs sm:text-sm md:text-base font-medium">Total Order</p>
          <p class="text-lg sm:text-xl md:text-2xl font-bold text-green-900">{{ totalOrder }}</p>
        </div>
        <div class="bg-yellow-100 shadow rounded-lg p-4 text-center w-[32%] min-w-[100px] break-words">
          <p class="text-yellow-800 text-xs sm:text-sm md:text-base font-medium">Total Pembelian</p>
          <p class="text-lg sm:text-xl md:text-2xl font-bold text-yellow-900">
            Rp {{ totalPembelian.toLocaleString() }}
          </p>
        </div>
      </div>




      <!-- Tab -->
      <div class="mb-4 flex space-x-4 border-b">
        <button @click="activeTab = 'all'"
          :class="['px-4 py-2', activeTab === 'all' ? 'border-b-2 border-indigo-600 font-semibold' : 'text-gray-500']">
          Pesanan
        </button>
        <button @click="activeTab = 'done'"
          :class="['px-4 py-2', activeTab === 'done' ? 'border-b-2 border-indigo-600 font-semibold' : 'text-gray-500']">
          Order Selesai
        </button>
      </div>

      <!-- Order List -->
      <div v-if="isLoading" class="text-center text-gray-500">Loading orders...</div>
      <div v-else-if="error" class="text-center text-red-500">{{ error }}</div>
      <div v-else-if="filteredOrders.length === 0" class="text-center text-gray-500">No orders found.</div>
      <div v-else class="space-y-4">
        <Order v-for="order in filteredOrders" :key="order.id" :order="order" />
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Tambahkan style tambahan jika perlu */
</style>
