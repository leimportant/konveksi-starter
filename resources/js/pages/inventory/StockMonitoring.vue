<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, watch, computed, ref } from 'vue';
import { useInventoryStore } from '@/stores/useInventoryStore';
import { storeToRefs } from 'pinia';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

const perPage = ref(50);

const store = useInventoryStore();
const {
  stockMonitoringReport,
  loading,
  error,
  currentPage,
  lastPage,
  filters,
} = storeToRefs(store);

const breadcrumbs = [
  { title: 'Inventory', href: '/inventory' },
];

onMounted(() => {
  store.fetchStockMonitoringReport(1, perPage.value);
});

watch(filters, () => {
  store.fetchStockMonitoringReport(1, perPage.value);
}, { deep: true });

const setFilter = (filter: keyof typeof filters.value, value: string | number | null) => {
  store.setFilter(filter, value);
};

const prevPage = () => {
  if (currentPage.value > 1) {
    store.fetchStockMonitoringReport(currentPage.value - 1, perPage.value);
  }
};

const nextPage = () => {
  if (currentPage.value < lastPage.value) {
    store.fetchStockMonitoringReport(currentPage.value + 1, perPage.value);
  }
};

const goToPage = (page: number) => {
  store.fetchStockMonitoringReport(page, perPage.value);
};

// Dynamic location columns (extracted from the first item)
const locationKeys = computed(() => {
  if (!stockMonitoringReport.value.length) return [];
  
  const knownKeys = ['product_id', 'product_name', 'uom_id', 'sloc_id', 'size_id', 'variant'];
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

      <!-- Pagination Controls -->
      <div class="flex justify-end mt-4 space-x-2">
        <button @click="prevPage" :disabled="currentPage === 1"
          class="px-3 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
          Previous
        </button>

        <template v-for="page in lastPage" :key="page">
          <button @click="goToPage(page)" :class="[
            'px-3 py-1 rounded border text-xs',
            page === currentPage
              ? 'bg-blue-600 border-blue-600 text-white'
              : 'border-gray-300 text-gray-700 hover:bg-gray-100'
          ]">
            {{ page }}
          </button>
        </template>

        <button @click="nextPage" :disabled="currentPage === lastPage"
          class="px-3 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
          Next
        </button>
      </div>
    </div>
  </AppLayout>
</template>
