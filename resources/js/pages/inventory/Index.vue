<template>
  <div class="p-4 md:p-6 space-y-6">
    <h1 class="text-2xl font-semibold">Inventory List</h1>

    <!-- Filters -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <select
        v-model="filters.location"
        @change="setFilter('location', ($event.target as HTMLSelectElement)?.value)"
        class="w-full px-3 py-2 border rounded-lg shadow-sm focus:outline-none focus:ring focus:border-blue-300"
      >
        <option value="">All Locations</option>
        <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
      </select>

      <select
        v-model="filters.sloc"
        @change="setFilter('sloc', ($event.target as HTMLSelectElement)?.value)"
        class="w-full px-3 py-2 border rounded-lg shadow-sm focus:outline-none focus:ring focus:border-blue-300"
      >
        <option value="">All SLOCs</option>
        <option v-for="sloc in slocs" :key="sloc.id" :value="sloc.id">{{ sloc.name }}</option>
      </select>

      <div class="relative">
        <input
          type="text"
          v-model="productSearch"
          @input="handleProductSearch"
          placeholder="Search products..."
          class="w-full px-3 py-2 border rounded-lg shadow-sm focus:outline-none focus:ring focus:border-blue-300"
        />
        <div v-if="products.length > 0 && productSearch" class="absolute z-10 w-full mt-1 bg-white border rounded-lg shadow-lg max-h-60 overflow-y-auto">
          <div
            v-for="prod in products"
            :key="prod.id"
            @click="() => { setFilter('product', String(prod.id)); productSearch = prod.name; }"
            class="px-4 py-2 hover:bg-gray-100 cursor-pointer"
          >
            {{ prod.name }}
          </div>
        </div>
      </div>

      <button
        @click="() => fetchInventory()"
        class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
      >
        Apply Filters
      </button>
    </div>

    <!-- Table -->
    <div>
      <div v-if="loading" class="text-gray-600 text-center py-6">Loading...</div>
      <div v-else-if="error" class="text-red-600 text-center py-6">Error: {{ error }}</div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full table-auto border rounded-lg overflow-hidden shadow-sm">
          <thead>
            <tr class="bg-gray-100 text-left">
              <th class="px-4 py-2">Location</th>
              <th class="px-4 py-2">Sloc</th>
              <th class="px-4 py-2">Product</th>
              <th class="px-4 py-2">Qty</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(item, index) in inventoryStore.inventory"
              :key="index"
              class="border-t hover:bg-gray-50"
            >
              <td class="px-4 py-2">{{ item.location?.name }}</td>
              <td class="px-4 py-2">{{ item.sloc?.name }}</td>
              <td class="px-4 py-2">{{ item.product?.name }}</td>
              <td class="px-4 py-2">{{ item.qty }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div class="flex justify-between items-center mt-6">
      <button
        @click="() => fetchInventory(currentPage - 1)"
        :disabled="currentPage <= 1"
        class="flex items-center px-4 py-2 bg-gray-100 border rounded-md disabled:opacity-50 hover:bg-gray-200 transition"
      >
        <ChevronLeft class="w-4 h-4 mr-1" /> Previous
      </button>

      <span class="text-sm text-gray-700">Page {{ currentPage }} of {{ lastPage }}</span>

      <button
        @click="() => fetchInventory(currentPage + 1)"
        :disabled="currentPage >= lastPage"
        class="flex items-center px-4 py-2 bg-gray-100 border rounded-md disabled:opacity-50 hover:bg-gray-200 transition"
      >
        Next <ChevronRight class="w-4 h-4 ml-1" />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useInventoryStore } from '@/stores/useInventoryStore';
import axios from 'axios';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

interface Location {
  id: number;
  name: string;
}
interface Sloc {
  id: number;
  name: string;
}
interface Product {
  id: number;
  name: string;
}

const locations = ref<Location[]>([]);
const slocs = ref<Sloc[]>([]);
const products = ref<Product[]>([]);
const productSearch = ref('');
const searchTimeout = ref<ReturnType<typeof setTimeout>>();

const searchProducts = async (query: string) => {
  try {
    const response = await axios.get(`/api/products-search?search=${query}`);
    products.value = response.data.data;
  } catch (error) {
    console.error('Error searching products:', error);
  }
};

const handleProductSearch = (event: Event) => {
  const value = (event.target as HTMLInputElement).value;
  productSearch.value = value;
  
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value);
  }

  searchTimeout.value = setTimeout(() => {
    if (value) {
      searchProducts(value);
    } else {
      fetchProducts();
    }
  }, 300);
};

const fetchProducts = async () => {
  try {
    const response = await axios.get('/api/products');
    products.value = response.data.data;
  } catch (error) {
    console.error('Error fetching products:', error);
  }
};

const fetchDropdownData = async () => {
  try {
    const [locResponse, slocResponse] = await Promise.all([
      axios.get('/api/locations'),
      axios.get('/api/slocs')
    ]);
    locations.value = locResponse.data.data;
    slocs.value = slocResponse.data.data;
    await fetchProducts();
  } catch (error) {
    console.error('Error fetching dropdown data:', error);
  }
};

const inventoryStore = useInventoryStore();
const { filters, loading, error, currentPage, lastPage } = inventoryStore;
const { fetchInventory, setFilter } = inventoryStore;

onMounted(async () => {
  await Promise.all([fetchDropdownData(), fetchInventory()]);
});
</script>
