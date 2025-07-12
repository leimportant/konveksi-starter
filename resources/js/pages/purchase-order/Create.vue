<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { useToast } from '@/composables/useToast';
import { usePurchaseOrder } from '@/stores/usePurchaseOrder';
import Vue3Select from 'vue3-select';
import 'vue3-select/dist/vue3-select.css';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import axios from 'axios'
import { Textarea } from '@/components/ui/textarea';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';


import { Plus, Trash2 } from 'lucide-vue-next';

const toast = useToast();
const { products, createPurchaseOrder } = usePurchaseOrder();
const uoms = ref<{ id: number; name: string }[]>([])
const form = useForm({
  nota_number: '',
  purchase_date: new Date().toISOString().slice(0, 10),
  supplier: '',
  notes: '' as string,
  items: [] as {
    product_id: number;
    qty: number;
    uom_id: string;
    price: number;
    total: number;
  }[],
});

const searchResults = ref<any[]>([]);

const addItem = () => {
  form.items.push({
    product_id: 0,
    qty: 1,
    uom_id: '',
    price: 0,
    total: 0,
  });
};

const removeItem = (index: number) => {
  form.items.splice(index, 1);
};

const updateItemDetails = (index: number) => {
  const selectedProduct = products.find(
    (p: { id: number }) => p.id === form.items[index].product_id
  );
  if (selectedProduct) {
    form.items[index].price = selectedProduct.price;
    calculateItemTotal(index);
  }
};

const calculateItemTotal = (index: number) => {
  const item = form.items[index];
  item.total = item.qty * item.price;
};

const fetchDropdowns = async () => {
  try {
    const [uomRes] = await Promise.all([
      axios.get('/api/uoms'),
    ])
    uoms.value = uomRes.data.data
  } catch (error) {
    toast.error('Gagal mengambil data dropdown')
    console.error(error)
  }
}

const searchProducts = async (search: string) => {
  if (search.length < 2) {
    searchResults.value = [];
    return;
  }
  try {
    const res = await axios.get('/api/products', {
      params: { search },
    });
    searchResults.value = res.data.data;
  } catch (error) {
    console.error('Search error:', error);
    toast.error('Failed to search products');
  }
};

const grandTotal = computed(() => {
  return form.items.reduce((sum, item) => sum + item.total, 0);
});

onMounted(() => {
  fetchDropdowns()
})
const submit = async () => {
  if (form.processing) return;
  form.processing = true;

  try {
    const payload = {
      ...form,
      items: form.items.map(item => ({
        ...item,
        product_id: (item.product_id as any)?.id ?? item.product_id,
        uom_id: item.uom_id || "",
      })),
    };

    await createPurchaseOrder(payload);
    toast.success('Purchase Order has been created successfully.');
    form.reset();
    router.visit('/purchase-order');
    form.items = [];
  } catch (error) {
    console.error('Error creating purchase order:', error);
    toast.error('Failed to create purchase order.');
  } finally {
    form.processing = false;
  }
};

</script>

<template>
  <Head title="Create Purchase Order" />
  <AppLayout title="Create Purchase Order">
    <template #header>
      <h2 class="font-semibold text-lg sm:text-xl text-gray-800 leading-tight">
        Create Purchase Order
      </h2>
    </template>

    <div class="py-6 px-4 sm:px-6 lg:px-8">
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Header Form -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
              <Label for="nota_number">Nota/Kwitansi No.</Label>
              <Input id="nota_number" type="text" v-model="form.nota_number" />
              <div v-if="form.errors.nota_number" class="text-red-500 text-xs mt-1">
                {{ form.errors.nota_number }}
              </div>
            </div>

            <div>
              <Label for="purchase_date">Tanggal</Label>
              <Input id="purchase_date" type="date" v-model="form.purchase_date" />
              <div v-if="form.errors.purchase_date" class="text-red-500 text-xs mt-1">
                {{ form.errors.purchase_date }}
              </div>
            </div>

            <div class="sm:col-span-2">
              <Label for="supplier">Supplier</Label>
              <Input id="supplier" type="text" v-model="form.supplier" />
              <div v-if="form.errors.supplier" class="text-red-500 text-xs mt-1">
                {{ form.errors.supplier }}
              </div>
            </div>

            <div class="sm:col-span-2">
              <Label for="notes">Notes</Label>
              <Textarea id="notes" rows="2" v-model="form.notes" />
              <div v-if="form.errors.notes" class="text-red-500 text-xs mt-1">
                {{ form.errors.notes }}
              </div>
            </div>
          </div>

          <!-- Items -->
          <div>
            <div class="flex justify-between items-center mb-2">
              <h3 class="text-base sm:text-lg font-semibold text-gray-900">Items</h3>
              <Button type="button" @click="addItem" size="sm" class="bg-blue-500 hover:bg-blue-600 text-white">
                <Plus class="w-4 h-4 mr-1" /> Add Item
              </Button>
            </div>

           <Table class="min-w-full text-sm sm:text-base table-auto">
    <TableHeader class="bg-gray-50 text-gray-700">
      <TableRow>
        <TableHead class="px-3 py-2 text-left font-semibold">Product</TableHead>
        <TableHead class="px-3 py-2 text-left font-semibold">Qty</TableHead>
        <TableHead class="px-3 py-2 text-left font-semibold">UOM</TableHead>
        <TableHead class="px-3 py-2 text-left font-semibold">Price</TableHead>
        <TableHead class="px-3 py-2 text-left font-semibold">Total</TableHead>
        <TableHead class="px-3 py-2 text-right font-semibold">
          <span class="sr-only">Actions</span>
        </TableHead>
      </TableRow>
    </TableHeader>

    <TableBody class="bg-white divide-y divide-gray-100">
      <TableRow
        v-for="(item, index) in form.items"
        :key="index"
        class="hover:bg-gray-50"
      >
        <!-- Product -->
        <TableCell class="px-3 py-2 min-w-[180px] align-top">
          <Vue3Select
            v-model="item.product_id"
            :options="searchResults"
            label="name"
            value="id"
            :onSearch="searchProducts"
            placeholder="Select product"
            @input="updateItemDetails(index)"
          />
          <div v-if="form.errors[`items.${index}.product_id`]" class="text-red-500 text-xs mt-1">
            {{ form.errors[`items.${index}.product_id`] }}
          </div>
        </TableCell>

        <!-- Qty -->
        <TableCell class="px-3 py-2 w-24 align-top">
          <Input type="number" v-model.number="item.qty" @input="calculateItemTotal(index)" />
          <div v-if="form.errors[`items.${index}.qty`]" class="text-red-500 text-xs mt-1">
            {{ form.errors[`items.${index}.qty`] }}
          </div>
        </TableCell>

        <!-- UOM -->
        <TableCell class="px-3 py-2 w-28 align-top">
          <select v-model="form.items[index].uom_id" class="w-full rounded-md border  text-xs border-input px-3 py-2" required>
                  <option value="">Select UOM</option>
                  <option v-for="uom in uoms" :key="uom.id" :value="uom.id">
                    {{ uom.name }}
                  </option>
                </select>
          <div v-if="form.errors[`items.${index}.uom_id`]" class="text-red-500 text-xs mt-1">
            {{ form.errors[`items.${index}.uom_id`] }}
          </div>
        </TableCell>

        <!-- Price -->
        <TableCell class="px-3 py-2 w-32 align-top">
          <Input type="number" v-model.number="item.price" @input="calculateItemTotal(index)" />
          <div v-if="form.errors[`items.${index}.price`]" class="text-red-500 text-xs mt-1">
            {{ form.errors[`items.${index}.price`] }}
          </div>
        </TableCell>

        <!-- Total -->
        <TableCell class="px-3 py-2 w-32 align-middle font-medium text-gray-800">
          {{ item.total.toLocaleString('id-ID', { style: 'currency', currency: 'IDR' }) }}
        </TableCell>

        <!-- Actions -->
        <TableCell class="px-3 py-2 w-12 text-right align-middle">
          <Button
            type="button"
            variant="destructive"
            size="icon"
            @click="removeItem(index)"
            class="hover:bg-red-600"
          >
            <Trash2 class="w-4 h-4" />
          </Button>
        </TableCell>
      </TableRow>
    </TableBody>
  </Table>
</div>

     

          <!-- Grand Total -->
          <div class="text-right mt-6">
            <h3 class="text-lg font-bold text-indigo-900">
              Grand Total:
              {{ grandTotal.toLocaleString('id-ID', {
                style: 'currency',
                currency: 'IDR',
              }) }}
            </h3>
          </div>

          <!-- Actions -->

          <div class="flex justify-end gap-2 mt-4">
        <Button @click="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
          :disabled="form.processing">Save</Button>
        <Button :href="route('purchase-order.index')" type="button"
          class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
          Cancel
        </Button>
      </div>

        </form>
    </div>
  </AppLayout>
</template>


