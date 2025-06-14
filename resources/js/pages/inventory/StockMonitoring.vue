<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import { useInventoryStore } from '@/stores/useInventoryStore';
import { storeToRefs } from 'pinia';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const store = useInventoryStore();
const { inventoryRpt, loading, error, currentPage, lastPage, total, filters } = storeToRefs(store);

const breadcrumbs = [
  { title: 'Inventory', href: '/inventory' },
];

// Reactive arrays to hold fetched filter options
const locations = ref<{ id: number; name: string }[]>([]);
const slocs = ref<{ id: number; name: string }[]>([]);
const products = ref<{ id: number; name: string }[]>([]);

// Fetch inventory and filter options on mounted
onMounted(async () => {
  await fetchFilters();
  store.fetchInventory();
});

async function fetchFilters() {
  try {
    const [locRes, slocRes, prodRes] = await Promise.all([
      axios.get('/api/locations'),
      axios.get('/api/slocs'),
      axios.get('/api/products'),
    ]);

    locations.value = locRes.data.data ?? [];
    slocs.value = slocRes.data.data ?? [];
    products.value = prodRes.data.data ?? [];
  } catch (e) {
    console.error('Failed to fetch filter data', e);
  }
}

// Watch filters and fetch inventory on change
watch(
  filters,
  () => {
    store.fetchInventory(1);
  },
  { deep: true }
);

const setFilter = (filter: keyof typeof filters.value, event: Event) => {
  const target = event.target as HTMLSelectElement;
  store.setFilter(filter, target.value);
};

const goPrev = () => {
  if (currentPage.value > 1) {
    store.fetchInventory(currentPage.value - 1);
  }
};

const goNext = () => {
  if (currentPage.value < lastPage.value) {
    store.fetchInventory(currentPage.value + 1);
  }
};
</script>

<template>
  <Head title="Inventory Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
        <h1 class="text-2xl font-bold mb-4">Stock Monitoring</h1>
      <!-- Filters -->
      <div class="flex gap-4 mb-4">
        <select class="border rounded px-2 py-1" @change="setFilter('location', $event)" :value="filters.location ?? ''">
          <option value="">All Locations</option>
          <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
        </select>

        <select class="border rounded px-2 py-1" @change="setFilter('sloc', $event)" :value="filters.sloc ?? ''">
          <option value="">All SLocs</option>
          <option v-for="sloc in slocs" :key="sloc.id" :value="sloc.id">{{ sloc.name }}</option>
        </select>

        <select class="border rounded px-2 py-1" @change="setFilter('product', $event)" :value="filters.product ?? ''">
          <option value="">All Products</option>
          <option v-for="prod in products" :key="prod.id" :value="prod.id">{{ prod.name }}</option>
        </select>
      </div>

      <!-- Error message -->
      <p v-if="error" class="text-red-600 mb-4">{{ error }}</p>

      <!-- Loading -->
      <p v-if="loading" class="mb-4">Loading...</p>

      <!-- Inventory Table -->
      <div class="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow class="bg-gray-100">
              <TableHead>Product</TableHead>
              <TableHead>Location</TableHead>
              <TableHead>SLoc</TableHead>
              <TableHead>UOM</TableHead>
              <TableHead>Size</TableHead>
              <TableHead>Stock Masuk</TableHead>
              <TableHead>Stock Keluar (Sold)</TableHead>
              <TableHead>Stock Tersedia</TableHead>
            </TableRow>


          </TableHeader>
          <TableBody>
            <TableRow v-for="item in inventoryRpt" :key="item.product_id">
              <TableCell>{{ item.product_name }}</TableCell>
              <TableCell>{{ item.location_name }}</TableCell>
              <TableCell>{{ item.sloc_name }}</TableCell>
              <TableCell>{{ item.uom_id }}</TableCell>
              <TableCell>{{ item.size_id }}</TableCell>
              <TableCell>{{ item.qty_in }}</TableCell>
              <TableCell>{{ item.qty_out }}</TableCell>
              <TableCell>{{ item.qty_available }}</TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <!-- Pagination -->
      <div class="flex justify-between items-center mt-4">
        <p>Total: {{ total }}</p>
        <div class="flex gap-2">
          <Button :disabled="currentPage <= 1" @click="goPrev">Prev</Button>
          <Button :disabled="currentPage >= lastPage" @click="goNext">Next</Button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
