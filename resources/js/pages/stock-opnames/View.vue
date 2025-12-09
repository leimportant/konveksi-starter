<script setup lang="ts">
import { onMounted, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import { useStockOpnameStore } from '@/stores/useStockOpnameStore';

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@/components/ui/table';

const toast = useToast();
const page = usePage();

// Ambil ID dari route param Inertia
const stockOpnameId = page.props.id as string;

if (!stockOpnameId || stockOpnameId === '0') {
  console.error('Invalid stockOpnameId:', stockOpnameId);
  toast.error('Invalid stock opname ID');
}

const loading = ref(false);
const stockOpname = ref<any>(null);

const store = useStockOpnameStore();

const fetchData = async () => {
  try {
    loading.value = true;
    await store.fetchOpnameById(stockOpnameId);
    stockOpname.value = store.items.find((opname) => opname.id === stockOpnameId);
  } catch (error) {
    console.error('Failed to load stock opname:', error);
    toast.error('Failed to load stock opname data');
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchData();
});

const breadcrumbs = [
  { title: 'Stock Opname', href: '/stock-opnames' },
  { title: 'Detail', href: `/stock-opnames/${stockOpnameId}` }
];

// Fungsi untuk format tanggal: dd/mm/yyyy hh:mm
function formatDate(dateString: string): string {
  if (!dateString) return '-';
  const date = new Date(dateString);
  const day = String(date.getDate()).padStart(2, '0');
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const year = date.getFullYear();
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  return `${day}/${month}/${year} ${hours}:${minutes}`;
}
</script>

<template>
  <Head title="View Stock Opname" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-4">
      <h1 class="text-sm font-bold mb-4">Stock Opname Detail</h1>

      <div v-if="stockOpname" class="bg-white shadow rounded p-6 space-y-6">
        <!-- Header Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div><strong>Location:</strong> {{ stockOpname.location?.name || '-' }}</div>
          <div><strong>Product:</strong> {{ stockOpname.product?.name || '-' }}</div>
          <div><strong>SLOC:</strong> {{ stockOpname.sloc_id }}</div>
          <div><strong>UOM:</strong> {{ stockOpname.uom_id }}</div>
          <div class="md:col-span-2"><strong>Remark:</strong> {{ stockOpname.remark || '-' }}</div>
          <div><strong>Created At:</strong> {{ formatDate(stockOpname.created_at) }}</div>
        </div>

        <!-- Items Table -->
        <div class="mt-6">
          <h2 class="text-lg font-semibold mb-2">Size Details</h2>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Size</TableHead>
                <TableHead>Variant</TableHead>
                <TableHead>System Qty</TableHead>
                <TableHead>Physical Qty</TableHead>
                <TableHead>Difference</TableHead>
                <TableHead>Note</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="(item, index) in stockOpname.items" :key="index">
                <TableCell>{{ item.size_id }}</TableCell>
                 <TableCell>{{ item.variant }}</TableCell>
                <TableCell>{{ item.qty_system }}</TableCell>
                <TableCell>{{ item.qty_physical }}</TableCell>
                <TableCell :class="item.difference < 0 ? 'text-red-600' : 'text-green-600'">
                  {{ item.difference }}
                </TableCell>
                <TableCell>{{ item.note || '-' }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <!-- Back Button -->
        <div class="flex justify-end mt-4">
          <button @click="router.visit('/stock-opnames')" 
                  class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded">
            Back
          </button>
        </div>
      </div>

      <div v-else-if="loading" class="text-center text-gray-600">Loading...</div>
      <div v-else class="text-center text-red-600">No data found.</div>
    </div>
  </AppLayout>
</template>
