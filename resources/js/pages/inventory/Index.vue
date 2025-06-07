<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import { useInventoryStore } from '@/stores/useInventoryStore';
import { storeToRefs } from 'pinia';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Plus } from 'lucide-vue-next';

const store = useInventoryStore();
const { inventory, loading, error, filters, currentPage, lastPage } = storeToRefs(store);

const breadcrumbs = [
  { title: 'Inventory', href: '/inventory' },
];

// Filter options reactive arrays
const locations = ref<{ id: number; name: string }[]>([]);
const slocs = ref<{ id: number; name: string }[]>([]);
const products = ref<{ id: number; name: string }[]>([]);

// Fetch filter options on mounted
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

onMounted(async () => {
  await fetchFilters();
  await store.fetchInventory(1);
});

// Watch filters to reload inventory on change, reset page to 1
watch(
  filters,
  async () => {
    await store.fetchInventory(1);
  },
  { deep: true }
);

// Set filter handler
const setFilter = (filter: keyof typeof filters.value, event: Event) => {
  const target = event.target as HTMLSelectElement;
  store.setFilter(filter, target.value);
};

// Computed total pages
const totalPages = computed(() => lastPage.value || 1);

// Pagination navigation
const goToPage = async (page: number) => {
  if (page < 1 || page > totalPages.value) return;
  await store.fetchInventory(page);
};

const nextPage = () => goToPage(currentPage.value + 1);
const prevPage = () => goToPage(currentPage.value - 1);
</script>

<template>

  <Head title="Inventory Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="flex justify-between items-center mb-6">
        <Button @click="$inertia.visit('/inventory/create')" class="bg-blue-600 text-white hover:bg-blue-700 gap-2">
          <Plus class="h-4 w-4" /> Add
        </Button>
      </div>

      <!-- Filters -->
      <div class="flex gap-4 mb-4">
        <select class="border rounded px-2 py-1" @change="setFilter('location', $event)"
          :value="filters.location ?? ''">
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

      <!-- Error -->
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
              <TableHead>Qty</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="item in inventory"
              :key="`${item.product.id}-${item.location.id}-${item.sloc.id}-${item.size_id}`">
              <TableCell>{{ item.product.name }}</TableCell>
              <TableCell>{{ item.location.name }}</TableCell>
              <TableCell>{{ item.sloc.name }}</TableCell>
              <TableCell>{{ item.uom_id }}</TableCell>
              <TableCell>{{ item.size_id }}</TableCell>
              <TableCell>{{ item.qty }}</TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <!-- Pagination -->
      <div class="flex justify-end mt-4 space-x-2">
        <button @click="prevPage" :disabled="currentPage === 1"
          class="px-3 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
          Previous
        </button>

        <template v-for="page in totalPages" :key="page">
          <button @click="goToPage(page)" :class="['px-3 py-1 rounded border text-sm',
            page === currentPage ? 'bg-blue-600 border-blue-600 text-white'
              : 'border-gray-300 text-gray-700 hover:bg-gray-100']">
            {{ page }}
          </button>
        </template>

        <button @click="nextPage" :disabled="currentPage === totalPages"
          class="px-3 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
          Next
        </button>
      </div>
    </div>
  </AppLayout>
</template>
