<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select-input';

const loading = ref(false);
const error = ref('');

// Form data
const formData = ref({
  product_id: '',
  location_id: '',
  uom_id: '',
  sloc_id: '',
  qty: 0
});

// Dropdown options
const products = ref([]);
const locations = ref([]);
const uoms = ref([]);
const slocs = ref([]);

// Fetch dropdown data
const fetchDropdownData = async () => {
  try {
    const [productsRes, locationsRes, uomsRes, slocsRes] = await Promise.all([
      axios.get('/api/products'),
      axios.get('/api/locations'),
      axios.get('/api/uoms'),
      axios.get('/api/slocs')
    ]);

    products.value = productsRes.data.data;
    locations.value = locationsRes.data;
    uoms.value = uomsRes.data;
    slocs.value = slocsRes.data;
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to fetch data';
  }
};

// Handle form submission
const handleSubmit = async () => {
  loading.value = true;
  error.value = '';

  try {
    await axios.post('/api/inventories', formData.value);
    router.visit('/inventory');
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to create inventory';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchDropdownData();
});
</script>

<template>
  <div class="p-6">
    <div class="max-w-4xl mx-auto">
      <h1 class="text-3xl font-semibold mb-8 text-gray-900">Create Inventory</h1>

      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Error message -->
        <div v-if="error" class="bg-red-50 text-red-700 border border-red-300 p-4 rounded-md shadow-sm">
          {{ error }}
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Product -->
          <div>
            <label for="product" class="block text-sm font-medium text-gray-700 mb-2">Product</label>
            <Select
              id="product"
              v-model="formData.product_id"
              :options="products"
              option-label="name"
              option-value="id"
              placeholder="Select Product"
              class="w-full"
            />
          </div>

          <!-- Location -->
          <div>
            <label for="location" class="block text-sm font-medium text-gray-700 mb-2">Location</label>
            <Select
              id="location"
              v-model="formData.location_id"
              :options="locations"
              option-label="name"
              option-value="id"
              placeholder="Select Location"
              class="w-full"
            />
          </div>

          <!-- UOM -->
          <div>
            <label for="uom" class="block text-sm font-medium text-gray-700 mb-2">UOM</label>
            <Select
              id="uom"
              v-model="formData.uom_id"
              :options="uoms"
              option-label="name"
              option-value="id"
              placeholder="Select UOM"
              class="w-full"
            />
          </div>

          <!-- SLOC -->
          <div>
            <label for="sloc" class="block text-sm font-medium text-gray-700 mb-2">SLOC</label>
            <Select
              id="sloc"
              v-model="formData.sloc_id"
              :options="slocs"
              option-label="name"
              option-value="id"
              placeholder="Select SLOC"
              class="w-full"
            />
          </div>

          <!-- Quantity -->
          <div class="md:col-span-2">
            <label for="qty" class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
            <Input
              id="qty"
              v-model="formData.qty"
              type="number"
              min="0"
              placeholder="Enter quantity"
              class="w-full"
            />
          </div>
        </div>

        <!-- Submit button -->
        <div class="flex justify-end">
          <Button
            type="submit"
            :loading="loading"
            class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-md shadow-md transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
          >
            Create Inventory
          </Button>
        </div>
      </form>
    </div>
  </div>
</template>
