<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useReportStore } from '@/stores/useReportStore';
import { onMounted, ref } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

const reportStore = useReportStore();

const breadcrumbs = [
  { title: 'Report Sales Summary', href: '/reports/sales-summary' }
];

const startDate = ref('');
const endDate = ref('');
const searchKey = ref('');

const fetchReport = () => {
  reportStore.fetchSalesSummary(startDate.value, endDate.value, searchKey.value);
};

onMounted(() => {
  // Set default dates for demonstration or initial load
  const today = new Date();
  const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
  startDate.value = firstDayOfMonth.toISOString().split('T')[0];
  endDate.value = today.toISOString().split('T')[0];
  fetchReport();
});
</script>

<template>
  <Head title="Sales Summary Report" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sales Summary Report</h2>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
          <div class="flex space-x-4 mb-4">
            <Input type="date" v-model="startDate" />
            <Input type="date" v-model="endDate" />
            <Input type="text" v-model="searchKey" placeholder="Search by product name" />
            <Button @click="fetchReport">Generate Report</Button>
          </div>

          <div v-if="reportStore.loading">Loading...</div>
          <div v-else-if="reportStore.error" class="text-red-500">Error: {{ reportStore.error.message }}</div>
          <div v-else>
            <Table>
              <TableHeader>
                <TableRow class="bg-gray-100">
                  <TableHead>Customer ID</TableHead>
                  <TableHead>Customer Name</TableHead>
                  <TableHead>Payment Method</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Product Name</TableHead>
                  <TableHead>Quantity</TableHead>
                  <TableHead>Price</TableHead>
                  <TableHead>Total Price</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="item in reportStore.salesSummary" :key="item.product_id">
                  <TableCell>{{ item.customer_id }}</TableCell>
                  <TableCell>{{ item.customer_name }}</TableCell>
                  <TableCell>{{ item.payment_method }}</TableCell>
                  <TableCell>{{ item.status }}</TableCell>
                  <TableCell>{{ item.product_name }}</TableCell>
                  <TableCell>{{ item.qty }}</TableCell>
                  <TableCell>{{ item.price }}</TableCell>
                  <TableCell>{{ item.price_final }}</TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>