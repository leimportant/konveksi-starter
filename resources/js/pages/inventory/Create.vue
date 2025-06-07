<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { Head } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useToast } from '@/composables/useToast'
import { watch } from 'vue';
import Vue3Select from 'vue3-select'
import 'vue3-select/dist/vue3-select.css'

const loading = ref(false);
const error = ref('');

const toast = useToast()
// Form data
const formData = ref<{
  product_id: number | { id: number };       // bisa number atau object berisi id
  location_id: number | { id: number };
  sloc_id: string | { id: string };
  uom_id: string;
  size_id?: string | { id: number };
  qty: number;
}>({
  product_id: 0,
  location_id: { id: 1 },
  sloc_id: 'GS00',
  uom_id: 'PCS',
  size_id: '',
  qty: 0
});


// Dropdown options
interface Product {
  id: number;
  name: string;
  uom_id: string;
}

const products = ref<Product[]>([]);

const searchResults = ref<Product[]>([]);

interface Location {
  id: number;
  name: string;
}

interface DropdownOption {
  id: string;
  name: string;
}

const locations = ref<Location[]>([]); // beri tipe eksplisit
const slocs = ref<DropdownOption[]>([]);
const sizes = ref<DropdownOption[]>([]);

const breadcrumbs = [
  { title: 'Add Inventory Stock', href: '/inventory/create' }
]

const searchProducts = async (search: string) => {
  if (search.length < 2) {
    searchResults.value = []
    return
  }
  try {
    const res = await axios.get('/api/products', { params: { search } })
    searchResults.value = res.data.data
  } catch (error) {
    console.error('Search error:', error);
    toast.error('Failed to search products')
  }
}


// Fetch dropdown data
const fetchDropdownData = async () => {
  try {
    const [productsRes, locationsRes, slocsRes, sizeRes] = await Promise.all([
      axios.get('/api/products'),
      axios.get('/api/locations'),
      axios.get('/api/slocs'),
      axios.get('/api/sizes')
    ]);

    products.value = productsRes.data.data;
    locations.value = locationsRes.data.data;
    slocs.value = slocsRes.data.data;
    sizes.value = sizeRes.data.data;
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to fetch data';
  }
};

// Handle form submission
const handleSubmit = async () => {
  loading.value = true;
  error.value = '';

  try {
    // Buat salinan data yang hanya berisi ID (bukan objek)
    const payload = {
      product_id: typeof formData.value.product_id === 'object' ? formData.value.product_id.id : formData.value.product_id,
      location_id: typeof formData.value.location_id === 'object' ? formData.value.location_id.id : formData.value.location_id,
      sloc_id: typeof formData.value.sloc_id === 'object' ? formData.value.sloc_id.id : formData.value.sloc_id,
      size_id: formData.value.size_id ? (typeof formData.value.size_id === 'object' ? formData.value.size_id.id : formData.value.size_id) : '',
      qty: formData.value.qty,
      uom_id: formData.value.uom_id
    };

    console.log('Submitting cleaned payload:', payload);

    await axios.post('/api/inventories', payload);
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



watch(() => formData.value.product_id, (newProduct) => {
  const id = typeof newProduct === 'object' ? newProduct.id : newProduct;
  const selected = products.value.find((p: any) => p.id === id);

  if (selected && selected.uom_id) {
    formData.value.uom_id = selected.uom_id;
  } else {
    formData.value.uom_id = '';
  }
});


</script>

<template>

  <Head title="Add Product Prices" />
  <AppLayout :breadcrumbs="breadcrumbs">


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

            <!-- Location -->
            <div>
              <label for="location" class="block text-sm font-medium text-gray-700 mb-2">Location</label>
              <Vue3Select id="location" v-model="formData.location_id" :options="locations" label="name" value="id"
                placeholder="Select Location" class="w-full" />
            </div>

            <div>
              <label for="product" class="block text-sm font-medium text-gray-700 mb-2">Product</label>
              <Vue3Select v-model="formData.product_id" :options="searchResults" label="name" value="id"
                :onSearch="searchProducts" placeholder="Search a product" />


            </div>

            <!-- SLOC -->
            <div>
              <label for="sloc" class="block text-sm font-medium text-gray-700 mb-2">SLOC</label>
              <Vue3Select id="sloc" v-model="formData.sloc_id" :options="slocs" label="name" value="id"
                placeholder="Select SLOC" class="w-full" />

            </div>
            <div>
              <label for="qty" class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
              <Input id="qty" v-model="formData.qty" type="number" min="0" placeholder="Enter quantity"
                class="w-full" />

            </div>
            <div>

              <label for="size" class="block text-sm font-medium text-gray-700 mb-2">Size</label>
              <Vue3Select id="size" v-model="formData.size_id" :options="sizes" label="name" value="id"
                placeholder="Select Size" class="w-full" />
            </div>

            <div>
              <label for="uom" class="block text-sm font-medium text-gray-700 mb-2">UOM</label>
              <Input id="uom" v-model="formData.uom_id" type="text" placeholder="Unit of Measure" class="w-full"
                readonly />
            </div>
          </div>

          <!-- Submit button -->
          <div class="flex justify-end">
            <Button type="submit" :loading="loading"
              class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-md shadow-md transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
              Create Inventory
            </Button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
