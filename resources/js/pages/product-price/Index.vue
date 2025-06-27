<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Edit, Trash2, Plus } from 'lucide-vue-next';
import { useToast } from "@/composables/useToast";
import { useProductPriceStore } from '@/stores/useProductPriceStore';
import { storeToRefs } from 'pinia';
import debounce from 'lodash-es/debounce';
import { Input } from '@/components/ui/input';

const toast = useToast();
const productPriceStore = useProductPriceStore();

const {
  productPrices,
  total,
  filterName,
  loading,
} = storeToRefs(productPriceStore);

const breadcrumbs = [{ title: 'Product Prices', href: '/product-prices' }];

// Pagination
const currentPage = ref(1);
const perPage = 10;
const totalItems = ref(0);
const totalPages = computed(() => Math.max(Math.ceil(totalItems.value / perPage), 1));

const fetchPage = async (page: number) => {
  await productPriceStore.fetchProductPrice(page, perPage);
  totalItems.value = total.value || 0;
  currentPage.value = page;
};

// Debounced filter handling
const debouncedSetFilter = debounce((field: string, value: string) => {
  productPriceStore.setFilter(field, value);
}, 400);

const handleInput = (e: Event) => {
  const target = e.target as HTMLInputElement;
  debouncedSetFilter('name', target.value);
};

onMounted(async () => {
  await fetchPage(currentPage.value);
});

// Pagination navigation
const goToPage = async (page: number) => {
  if (page < 1 || page > totalPages.value) return;
  await fetchPage(page);
};
const nextPage = () => goToPage(currentPage.value + 1);
const prevPage = () => goToPage(currentPage.value - 1);

// Helpers
const formatDate = (dateStr: string) => {
  if (!dateStr) return 'N/A';
  const date = new Date(dateStr);
  if (isNaN(date.getTime())) return 'Invalid Date';
  return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
};

const formatNumber = (value: number | string) => {
  const num = typeof value === 'string' ? parseFloat(value) : value;
  return new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(num || 0);
};

const handleDelete = async (id: number) => {
  if (!confirm('Are you sure?')) return;
  try {
    await productPriceStore.deleteProductPrice(id);
    toast.success("Deleted");
    await fetchPage(currentPage.value);
  } catch (err: any) {
    toast.error(err?.response?.data?.message || "Failed to delete");
  }
};
</script>

<template>
  <Head title="Product Prices" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-4">
      <div class="flex justify-between items-center mb-6">
        <Button @click="$inertia.visit('/product-prices/create')"  class="bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">
          <Plus class="h-4 w-4" />
          Add
        </Button>
        <Input
          v-model="filterName"
          placeholder="Search"
          @input="handleInput"
          class="w-64"
          aria-label="Search"
          :disabled="loading"
        />
      </div>

      <div class="rounded-md border overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow class="bg-gray-100">
              <TableHead>ID</TableHead>
              <TableHead>Product</TableHead>
              <TableHead>Cost Price</TableHead>
              <TableHead>Effective Date</TableHead>
              <TableHead>Status</TableHead>
              <TableHead class="w-24">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="price in productPrices" :key="price.id">
              <TableCell>{{ price.id }}</TableCell>
              <TableCell>{{ price.product?.name || 'N/A' }}</TableCell>
              <TableCell>{{ formatNumber(price.cost_price) }}</TableCell>
              <TableCell>{{ formatDate(price.effective_date) }}</TableCell>
              <TableCell>
                <span :class="price.is_active ? 'text-green-600' : 'text-red-600'">
                  {{ price.is_active ? 'Active' : 'Inactive' }}
                </span>
              </TableCell>
              <TableCell class="flex gap-2">
                <Button variant="ghost" size="icon" @click="$inertia.visit(`/product-prices/${price.id}/edit`)">
                  <Edit class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="icon" @click="handleDelete(price.id)">
                  <Trash2 class="h-4 w-4" />
                </Button>
              </TableCell>
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
          <button @click="goToPage(page)" :class="[
            'px-3 py-1 rounded border text-sm',
            page === currentPage ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-100'
          ]">
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
