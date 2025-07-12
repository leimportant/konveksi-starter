<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { useToast } from '@/composables/useToast';
import { usePurchaseOrder } from '@/stores/usePurchaseOrder';
import axios from 'axios';
import Vue3Select from 'vue3-select';
import 'vue3-select/dist/vue3-select.css';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
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
const { products, uoms, createPurchaseOrder } = usePurchaseOrder();

const form = useForm({
  nota_number: '',
  purchase_date: new Date().toISOString().slice(0, 10),
  supplier: '',
  notes: '' as string,
  items: [] as {
    product_id: string;
    qty: number;
    uom_id: string;
    price: number;
    total: number;
  }[],
});

const searchResults = ref<any[]>([]);

const addItem = () => {
  form.items.push({
    product_id: '',
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
    (p: { id: string }) => p.id === form.items[index].product_id as string
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

const submit = async () => {
  try {
    await createPurchaseOrder(form);
    toast.success('Purchase Order has been created successfully.');
    form.reset();
    form.items = [];
  } catch (error) {
    console.error('Error creating purchase order:', error);
    toast.error('Failed to create purchase order.');
  }
};
</script>

<template>
  <Head title="Create Purchase Order" />
  <AppLayout title="Create Purchase Order">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Create Purchase Order
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <form @submit.prevent="submit">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <div>
                <Label for="nota_number">Nota/Kwitansi No.</Label>
                <Input
                  type="text"
                  id="nota_number"
                  v-model="form.nota_number"
                />
                <div
                  v-if="form.errors.nota_number"
                  class="text-red-500 text-sm"
                >
                  {{ form.errors.nota_number }}
                </div>
              </div>
              <div>
                <Label for="purchase_date">Tanggal</Label>
                <Input
                  type="date"
                  id="purchase_date"
                  v-model="form.purchase_date"
                />
                <div
                  v-if="form.errors.purchase_date"
                  class="text-red-500 text-sm"
                >
                  {{ form.errors.purchase_date }}
                </div>
              </div>
            </div>

            <div class="mb-4">
              <Label for="supplier">Supplier</Label>
              <Input
                type="text"
                id="supplier"
                v-model="form.supplier"
              />
              <div
                v-if="form.errors.supplier"
                class="text-red-500 text-sm"
              >
                {{ form.errors.supplier }}
              </div>
            </div>

            <div class="mb-4">
              <Label for="notes">Notes</Label>
              <Textarea id="notes" v-model="form.notes" />
              <div v-if="form.errors.notes" class="text-red-500 text-sm">
                {{ form.errors.notes }}
              </div>
            </div>

            <h3 class="text-lg font-medium text-gray-900 mb-4">Items</h3>
            <div class="rounded-md border mb-4 overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Product</TableHead>
                    <TableHead>Qty</TableHead>
                    <TableHead>UOM</TableHead>
                    <TableHead>Price</TableHead>
                    <TableHead>Total</TableHead>
                    <TableHead><span class="sr-only">Actions</span></TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow
                    v-for="(item, index) in form.items"
                    :key="index"
                  >
                    <TableCell>
                      <Vue3Select
                        v-model="item.product_id"
                        :options="searchResults"
                        label="name"
                        value="id"
                        :onSearch="searchProducts"
                        placeholder="Search a product"
                        @input="updateItemDetails(index)"
                      />
                      <div
                        v-if="form.errors[`items.${index}.product_id`]"
                        class="text-red-500 text-sm"
                      >
                        {{ form.errors[`items.${index}.product_id`] }}
                      </div>
                    </TableCell>
                    <TableCell>
                      <Input
                        type="number"
                        v-model.number="item.qty"
                        @input="calculateItemTotal(index)"
                      />
                      <div
                        v-if="form.errors[`items.${index}.qty`]"
                        class="text-red-500 text-sm"
                      >
                        {{ form.errors[`items.${index}.qty`] }}
                      </div>
                    </TableCell>
                    <TableCell>
                      <Select v-model="item.uom_id">
                        <SelectTrigger>
                          <SelectValue placeholder="Select UOM" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem
                            v-for="uom in uoms"
                            :key="uom.id"
                            :value="uom.id"
                          >
                            {{ uom.name }}
                          </SelectItem>
                        </SelectContent>
                      </Select>
                      <div
                        v-if="form.errors[`items.${index}.uom_id`]"
                        class="text-red-500 text-sm"
                      >
                        {{ form.errors[`items.${index}.uom_id`] }}
                      </div>
                    </TableCell>
                    <TableCell>
                      <Input
                        type="number"
                        v-model.number="item.price"
                        @input="calculateItemTotal(index)"
                      />
                      <div
                        v-if="form.errors[`items.${index}.price`]"
                        class="text-red-500 text-sm"
                      >
                        {{ form.errors[`items.${index}.price`] }}
                      </div>
                    </TableCell>
                    <TableCell>
                      {{ item.total.toLocaleString('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                      }) }}
                    </TableCell>
                    <TableCell class="text-right">
                      <Button
                        type="button"
                        variant="destructive"
                        size="icon"
                        @click="removeItem(index)"
                      >
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </div>

            <Button type="button" @click="addItem" class="bg-blue-500 text-white mb-2 rounded hover:bg-blue-600">
              <Plus class="h-4 w-4 mr-2" /> Add Item
            </Button>

            <div class="text-right mb-6">
              <h3 class="text-xl font-bold text-indgo-900">
                Grand Total:
                {{ grandTotal.toLocaleString('id-ID', {
                  style: 'currency',
                  currency: 'IDR',
                }) }}
              </h3>
            </div>

            <div class="flex justify-end">
              <Link :href="route('purchase-order.index')">
                <Button variant="outline" class="mr-2">Cancel</Button>
              </Link>
               <Button @click="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600" :disabled="form.processing">Simpan</Button>
              
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
