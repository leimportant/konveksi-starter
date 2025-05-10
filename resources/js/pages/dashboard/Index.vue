<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import axios from 'axios';

const stats = ref({
  total_order: 0,
  total_transactions: 0,
  total_products: 0,
  total_users: 0
});

const loading = ref(true);

onMounted(async () => {
  try {
    const response = await axios.get('/api/dashboard');
    stats.value = response.data.stats;
  } catch (error) {
    console.error('Error fetching dashboard stats:', error);
  } finally {
    loading.value = false;
  }
});

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' }
];
</script>

<template>
  <Head title="Dashboard" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="max-w-7xl mx-auto">
        <h2 class="text-2xl font-semibold mb-6">Dashboard Overview</h2>
        
        <div v-if="loading" class="text-center py-4">
          Loading...
        </div>
        
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <!-- Total Orders -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-gray-500 text-sm font-medium">Total Orders</h3>
            <p class="text-3xl font-semibold mt-2">{{ stats.total_order }}</p>
          </div>

          <!-- Total Transactions -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-gray-500 text-sm font-medium">Total Transactions</h3>
            <p class="text-3xl font-semibold mt-2">{{ stats.total_transactions }}</p>
          </div>

          <!-- Total Products -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-gray-500 text-sm font-medium">Total Products</h3>
            <p class="text-3xl font-semibold mt-2">{{ stats.total_products }}</p>
          </div>

          <!-- Total Users -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-gray-500 text-sm font-medium">Total Users</h3>
            <p class="text-3xl font-semibold mt-2">{{ stats.total_users }}</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>