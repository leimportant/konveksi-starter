<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, watch, computed } from 'vue';
import { useInventoryStore } from '@/stores/useInventoryStore';
import { storeToRefs } from 'pinia';
import { Button } from '@/components/ui/button';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

const store = useInventoryStore();
const {
  stockMonitoringReport,
  loading,
  error,
  currentPage,
  lastPage,
  total,
  filters,
} = storeToRefs(store);

const breadcrumbs = [
  { title: 'Inventory', href: '/inventory' },
];

onMounted(() => {
  store.fetchStockMonitoringReport(1);
});

watch(filters, () => {
  store.fetchStockMonitoringReport(1);
}, { deep: true });

const setFilter = (filter: keyof typeof filters.value, value: string | number | null) => {
  store.setFilter(filter, value);
};

const prevPage = () => {
  if (currentPage.value > 1) {
    store.fetchStockMonitoringReport(currentPage.value - 1);
  }
};

const nextPage = () => {
  if (currentPage.value < lastPage.value) {
    store.fetchStockMonitoringReport(currentPage.value + 1);
  }
};

const goToPage = (page: number) => {
  store.fetchStockMonitoringReport(page);
};

// Dynamic location columns (extracted from the first item)
const locationKeys = computed(() => {
  if (!stockMonitoringReport.value.length) return [];
  
  const knownKeys = ['product_id', 'product_name', 'uom_id', 'sloc_id', 'size_id'];
  return Object.keys(stockMonitoringReport.value[0])
    .filter(key => !knownKeys.includes(key));
});
</script>

<template>
  <Head title="Stock Monitoring Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-4">
    <div class="flex justify-between items-center mb-4">
          <input
            type="text"
            class="border rounded px-2 py-1"
            :value="filters.productName"
            @input="(e: Event) => setFilter('productName', (e.target as HTMLInputElement).value)"
            placeholder="Search product..."
          />
       </div>

      <!-- Error message -->
      <p v-if="error" class="text-red-600">{{ error }}</p>

      <!-- Loading -->
      <p v-if="loading" class="text-gray-500">Loading...</p>

      <!-- Table -->
      <div v-if="!loading" class="rounded-md border overflow-auto">
  <Table>
  <TableHeader>
    <TableRow class="bg-gray-100">
      <TableHead>Product ID</TableHead>
      <TableHead>Product Name</TableHead>
      <TableHead>SLoc</TableHead>
      <TableHead>UOM</TableHead>
      <TableHead v-for="key in locationKeys" :key="key">
        {{ key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
      </TableHead>
    </TableRow>
  </TableHeader>

  <TableBody>
    <TableRow
      v-for="item in stockMonitoringReport"
      :key="`${item.product_id}-${item.sloc_id}`"
    >
      <TableCell>{{ item.product_id }}</TableCell>
      <TableCell>{{ item.product_name }}</TableCell>
      <TableCell>{{ item.sloc_id }}</TableCell>
      <TableCell>{{ item.uom_id }}</TableCell>
      <TableCell
        v-for="key in locationKeys"
        :key="key"
      >
        {{ item[key]?.qty ?? 0 }}
      </TableCell>
    </TableRow>
  </TableBody>
</Table>

      </div>

      <!-- Pagination -->
      <!-- Pagination -->
            <div class="mt-4 flex justify-end space-x-2">
                <Button
                    @click="prevPage"
                    :disabled="currentPage === 1"
                    class="rounded border border-gray-300 px-3 py-1 text-gray-700 hover:bg-gray-100 disabled:opacity-50"
                >
                    Previous
                </Button>
                <template v-for="page in total" :key="page">
                    <Button
                        @click="goToPage(page)"
                        :class="[
                            'rounded border px-3 py-1 text-sm',
                            page === currentPage ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-100',
                        ]"
                    >
                        {{ page }}
                    </Button>
                </template>
                <Button
                    @click="nextPage"
                    :disabled="currentPage === total"
                    class="rounded border px-3 py-1 text-sm border-gray-300 text-gray-700 hover:bg-gray-100 disabled:opacity-50"
                >
                    Next
                </Button>
            </div>
    </div>
  </AppLayout>
</template>
