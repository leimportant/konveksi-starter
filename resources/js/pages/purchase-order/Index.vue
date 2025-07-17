<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { storeToRefs } from 'pinia';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Trash2, Plus, Edit } from 'lucide-vue-next';
import { useToast } from '@/composables/useToast';
import { usePurchaseOrder } from '@/stores/usePurchaseOrder';

const toast = useToast();

interface PurchaseOrderItem {
  id?: number | 0;
  purchase_order_id?: string | "";
  product_id: number | string;
  qty: number;
  uom_id: string;
  price: number;
  total: number;
}

const formatDate = (dateStr: string) => {
  if (!dateStr) return 'N/A';
  const date = new Date(dateStr);
  if (isNaN(date.getTime())) {
    const now = new Date();
    return `${String(now.getDate()).padStart(2, '0')}/${String(now.getMonth() + 1).padStart(2, '0')}/${now.getFullYear()} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
  }
  return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
};
// UI state variables
const showCreateModal = ref(false);

// Form
const form = useForm({
  id: '',
  purchase_date: '',
  supplier: '',
  nota_number: '',
  status: '',
  items: [] as PurchaseOrderItem[], // tambahkan ini
});


// Store
const purchaseOrder = usePurchaseOrder();
const {
  items: purchaseOrders,
  loading,
  currentPage,
  lastPage,
} = storeToRefs(purchaseOrder);

const {
  fetchPurchaseOrders,
  createPurchaseOrder,
  deletePurchaseOrder,
  setFilter,
} = purchaseOrder;

const search = ref('');

// Pagination
const totalPages = computed(() => lastPage.value || 1);

const goToPage = async (page: number) => {
  if (page < 1 || page > totalPages.value) return;
  await fetchPurchaseOrders(page);
};

const nextPage = () => goToPage(currentPage.value + 1);
const prevPage = () => goToPage(currentPage.value - 1);

// Search
const handleSearch = () => {
  setFilter('search', search.value);
};

// Init
onMounted(() => {
  fetchPurchaseOrders();
});

// CRUD handlers
const handleCreate = async () => {
  try {
    await createPurchaseOrder(form);
    toast.success('Purchase Order created successfully');
    form.reset();
    showCreateModal.value = false;
  } catch (error: any) {
    toast.error(error?.response?.data?.message || 'Failed to create Purchase Order');
  }
};





const handleDelete = async (id: string) => {
  if (!confirm('Are you sure you want to delete this Purchase Order?')) return;
  try {
    await deletePurchaseOrder(id);
    toast.success('Purchase Order deleted successfully');
  } catch (error: any) {
    toast.error(error?.response?.data?.message || 'Failed to delete Purchase Order');
  }
};

const breadcrumbs = [
  { title: 'Purchase Orders', href: '/purchase-orders' },
];
</script>

<template>
  <Head title="Purchase Orders" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-4">
      <div class="flex justify-between items-center gap-2 mb-2">
        <Button @click="$inertia.visit('/purchase-order/create')"  class="bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">
          <Plus class="h-4 w-4" />
          Add
        </Button>

        <Input
          v-model="search"
          placeholder="Search"
          @input="handleSearch"
          class="w-64"
          aria-label="Search"
        />
      </div>

      <div class="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow class="bg-gray-100">
              <TableHead>ID</TableHead>
              <TableHead>Date</TableHead>
              <TableHead>Supplier</TableHead>
              <TableHead>Nota Number</TableHead>
              <TableHead>Status</TableHead>
              <TableHead class="w-24">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="order in purchaseOrders" :key="order.id">
              <TableCell>{{ order.id }}</TableCell>
              <TableCell>{{ formatDate(order.purchase_date) }}</TableCell>
              <TableCell>{{ order.supplier }}</TableCell>
              <TableCell>{{ order.nota_number }}</TableCell>
              <TableCell>{{ order.status }}</TableCell>
              <TableCell class="flex gap-2">
                <Button variant="ghost" size="icon" @click="$inertia.visit(`/purchase-order/${order.id}/edit`)">
                  <Edit class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="icon" @click="handleDelete(order.id)" :disabled="loading || order.status === 'completed'">
                  <Trash2 class="h-4 w-4" />
                </Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <!-- Pagination -->
      <div class="flex justify-end mt-4 space-x-2">
        <button @click="prevPage" :disabled="currentPage === 1 || loading" class="px-3 py-1 rounded border text-gray-700 hover:bg-gray-100">
          Previous
        </button>

        <template v-for="page in totalPages" :key="page">
          <button
            @click="goToPage(page)"
            :class="[
              'px-3 py-1 rounded border text-sm',
              page === currentPage ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-100'
            ]"
          >
            {{ page }}
          </button>
        </template>

        <button @click="nextPage" :disabled="currentPage === totalPages || loading" class="px-3 py-1 rounded border text-gray-700 hover:bg-gray-100">
          Next
        </button>
      </div>

      <!-- Create Modal -->
      <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg w-96">
          <h2 class="text-lg font-semibold mb-4">Create Purchase Order</h2>
          <form @submit.prevent="handleCreate">
            <div class="mb-4">
              <Input v-model="form.purchase_date" type="date" placeholder="Date" required />
            </div>
            <div class="mb-4">
              <Input v-model="form.supplier" placeholder="Supplier" required />
            </div>
            <div class="mb-4">
              <Input v-model="form.status" placeholder="Status" required />
            </div>
            <div class="flex justify-end gap-2">
              <Button type="button" variant="outline" @click="showCreateModal = false">Cancel</Button>
              <Button type="submit" class="bg-indigo-600 text-white">Create</Button>
            </div>
          </form>
        </div>
      </div>


    </div>
  </AppLayout>
</template>
