<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToast } from "@/composables/useToast";
import axios from 'axios';

const toast = useToast();
const loading = ref(false);

// Form data
const formData = ref({
  id: '',
  location_id: '',
  product_id: '',
  sloc_id: '',
  uom_id: '',
  remark: '',
  stock_opname_items: [] as Array<{
    size_id: string;
    qty_system: number;
    qty_physical: number;
    difference: number;
    note: string;
  }>
});

// Dropdown options
interface DropdownItem {
  id: string | number;
  name: string;
  sloc_id?: string;
  uom_id?: string;
  sizes?: Array<{ size_id: string; qty: number }>;
}


const products = ref<DropdownItem[]>([]);
const locations = ref<DropdownItem[]>([]);

// Validation errors
interface ErrorMap {
  [key: string]: string;
}
const errors = ref<ErrorMap>({
  product_id: '',
  location_id: '',
  sloc_id: '',
  uom_id: '',
  'stock_opname_items.size_id': '',
  'stock_opname_items.qty_system': '',
  'stock_opname_items.qty_physical': '',
});

// Fetch dropdown data
const fetchDropdownData = async () => {
  try {
    loading.value = true;
    const [locationsRes] = await Promise.all([
      // axios.get('/api/inventories'),
      axios.get('/api/locations'),
      // axios.get('/api/slocs'),
      // axios.get('/api/uoms'),
      // axios.get('/api/sizes')
    ]);

    // products.value = productsRes;
    locations.value = locationsRes.data.data;
    // slocs.value = slocsRes.data.data;
    // uoms.value = uomsRes.data.data;
    // sizes.value = sizesRes.data.data;
  } catch (error) {
    console.error('Error fetching dropdown data:', error);
    toast.error('Failed to load form data');
  } finally {
    loading.value = false;
  }
};

// Add a new item row
const addItem = () => {
  formData.value.stock_opname_items.push({
    size_id: '',
    qty_system: 0,
    qty_physical: 0,
    difference: 0,
    note: ''
  });
};


// Calculate difference
const calculateDifference = (index: number) => {
  const item = formData.value.stock_opname_items[index];
  item.difference = item.qty_physical - item.qty_system;
};

// Clear validation error
const clearError = (field: string) => {
  if (errors.value[field]) {
    errors.value[field] = '';
  }
};

// Submit form
const submitForm = async () => {
  try {
    loading.value = true;

    // Reset errors
    Object.keys(errors.value).forEach(key => {
      errors.value[key] = '';
    });

    // Generate UUID for id field
    if (!formData.value.id) {
      formData.value.id = crypto.randomUUID();
    }

    // Validate form
    let hasError = false;
    if (!formData.value.product_id) {
      errors.value.product_id = 'Product is required';
      hasError = true;
    }
    if (!formData.value.location_id) {
      errors.value.location_id = 'Location is required';
      hasError = true;
    }
    if (!formData.value.sloc_id) {
      errors.value.sloc_id = 'Storage Location is required';
      hasError = true;
    }
    if (!formData.value.uom_id) {
      errors.value.uom_id = 'UOM is required';
      hasError = true;
    }
    if (formData.value.stock_opname_items.length === 0) {
      toast.error('At least one item is required');
      hasError = true;
    }

    // Validate items
    formData.value.stock_opname_items.forEach((item) => {
      if (!item.size_id) {
        errors.value['stock_opname_items.size_id'] = 'Size is required';
        hasError = true;
      }
    });

    if (hasError) {
      return;
    }

    // Submit form
    await axios.post('/api/stock-opnames', formData.value);
    toast.success('Stock opname created successfully');
    router.visit('/stock-opnames');
  } catch (error: any) {

    // Handle validation errors from backend
    if (error.response?.status === 422 && error.response?.data?.errors) {
      errors.value = error.response.data.errors;

      // Iterate and display all error messages
      for (const key in errors.value) {
        const errorMessages = Array.isArray(errors.value[key]) ? errors.value[key] : [errors.value[key]];
        errorMessages.forEach((errorMsg: string) => {
          toast.error(errorMsg);
        });
      }
    }
    // Jika ada message dari backend (misal error 400, 500, dll)
    else if (error.response?.data?.message) {
      toast.error(error.response.data.message);
    } else {
      toast.error('Terjadi kesalahan saat menyimpan data');
    }

  } finally {
    loading.value = false;
  }
};
// Initialize
onMounted(() => {
  fetchDropdownData();
  addItem(); // Add first item row by default
});

const breadcrumbs = [
  { title: 'Stock Opname', href: '/stock-opnames' },
  { title: 'Create', href: '/stock-opnames/create' }
];
const onProductChange = async () => {
  clearError('product_id');
  if (!formData.value.product_id) return;
  try {
    loading.value = true;
    // Use existing product data from products dropdown
    const product = products.value.find(p => p.id === formData.value.product_id);
    if (product) {
      // Auto-fill sloc_id and uom_id
      formData.value.sloc_id = product.sloc_id || '';
      formData.value.uom_id = product.uom_id || '';
      // Auto-fill stock_opname_items
      formData.value.stock_opname_items = (product.sizes || []).map((size: { size_id: string, qty: number }) => ({
        size_id: String(size.size_id),
        qty_system: size.qty || 0,
        qty_physical: 0,
        difference: 0,
        note: ''
      }));
    }
  } catch (error) {
    console.error('Error processing product change:', error);
    toast.error('Failed to process product change');
  } finally {
    loading.value = false;
  }
};
const onLocationChange = async () => {
  clearError('location_id');
  if (!formData.value.location_id) return;
  try {
    loading.value = true;
    // Fetch inventories filtered by location_id
    const res = await axios.get(`/api/combo/product-inventory?location_id=${formData.value.location_id}`);
    // Map inventories to products dropdown using nested product object
    console.log(res.data);
    products.value = Array.isArray(res.data)
      ? res.data.map((inv: { product_id: string | number, product_name: string, sloc_id?: string, uom_id?: string, sizes: Array<{ size_id: string, qty: number }> }) => ({
        id: inv.product_id,
        name: inv.product_name,
        sloc_id: inv.sloc_id || '',
        uom_id: inv.uom_id || '',
        sizes: inv.sizes || []
      }))
      : [];
    formData.value.product_id = '';
    formData.value.stock_opname_items = [];
  } catch (error) {
    console.error('Error fetching products by location:', error);
    toast.error('Failed to fetch products by location');
  } finally {
    loading.value = false;
  }
};
</script>

<template>

  <Head title="Create Stock Opname" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-4">
      <h1 class="text-sm font-bold mb-4">Create Stock Opname</h1>

      <div class="bg-white rounded-md shadow p-6">
        <form @submit.prevent="submitForm">
          <!-- Main Form -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Location -->
            <div>
              <Label for="location_id">Location</Label>
              <select v-model="formData.location_id" id="location_id" class="w-full border rounded p-2"
                @change="onLocationChange">
                <option value="" disabled>Select location</option>
                <option v-for="location in locations" :key="location.id" :value="location.id">
                  {{ location.name }}
                </option>
              </select>
              <p v-if="errors.location_id" class="text-red-600 text-sm mt-1">{{ errors.location_id }}</p>
            </div>

            <!-- Product -->
            <div>
              <Label for="product_id">Product</Label>
              <select v-model="formData.product_id" id="product_id" class="w-full border rounded p-2"
                @change="onProductChange">
                <option value="" disabled>Select product</option>
                <option v-for="product in products" :key="product.id" :value="product.id">
                  {{ product.name }}
                </option>
              </select>
              <p v-if="errors.product_id" class="text-red-600 text-sm mt-1">{{ errors.product_id }}</p>
            </div>

            <!-- Storage Location -->
            <div>
              <Label for="sloc_id">Storage Location</Label>
              <Input v-model="formData.sloc_id" id="sloc_id" readonly class="w-full border rounded p-2"
                @input="clearError('sloc_id')" />
              <p v-if="errors.sloc_id" class="text-red-600 text-sm mt-1">{{ errors.sloc_id }}</p>
            </div>

            <!-- UOM -->
            <div>
              <Label for="uom_id">Unit of Measure</Label>
              <Input v-model="formData.uom_id" id="uom_id" readonly class="w-full border rounded p-2"
                @change="clearError('uom_id')" />
              <p v-if="errors.uom_id" class="text-red-600 text-sm mt-1">{{ errors.uom_id }}</p>
            </div>

            <!-- Remark -->
            <div class="md:col-span-2">
              <Label for="remark">Remark</Label>
              <Input v-model="formData.remark" id="remark" placeholder="Enter remark" />
            </div>
          </div>

          <!-- Items Table -->
          <div class="mb-6">

            <div class="overflow-x-auto">
              <table class="w-full border-collapse">
                <thead>
                  <tr class="bg-gray-100">
                    <th class="border p-2 text-left">Size</th>
                    <th class="border p-2 text-left">System Qty</th>
                    <th class="border p-2 text-left">Actual Qty</th>
                    <th class="border p-2 text-left">Difference</th>
                    <th class="border p-2 text-left">Note</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in formData.stock_opname_items" :key="index" class="border-b">
                    <!-- Size -->
                    <td class="border p-2">
                      <Input type="text" v-model="item.size_id" class="w-full border rounded p-1" readonly />
                      <p v-if="index === 0 && errors['stock_opname_items.size_id']" class="text-red-600 text-xs mt-1">
                        {{ errors['stock_opname_items.size_id'] }}
                      </p>
                    </td>

                    <!-- System Qty -->
                    <td class="border p-2">
                      <Input type="number" v-model="item.qty_system" class="w-full"
                        @input="calculateDifference(index)" />
                    </td>

                    <!-- Physical Qty -->
                    <td class="border p-2">
                      <Input type="number" v-model="item.qty_physical" class="w-full"
                        @input="calculateDifference(index)" />
                    </td>

                    <!-- Difference -->
                    <td class="border p-2">
                      <Input type="number" v-model="item.difference" class="w-full" readonly />
                    </td>

                    <!-- Note -->
                    <td class="border p-2">
                      <Input v-model="item.note" class="w-full" />
                    </td>

                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex justify-end gap-4">
            <Button type="button" variant="outline" @click="router.visit('/stock-opnames')">
              Cancel
            </Button>
            <Button type="submit" :disabled="loading">
              {{ loading ? 'Saving...' : 'Save' }}
            </Button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>