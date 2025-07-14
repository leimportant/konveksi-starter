<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Edit, Trash2, Plus } from 'lucide-vue-next';
import { useToast } from "@/composables/useToast";
import { useBahanStore } from '@/stores/useBahanStore';
import { useCategoryStore } from '@/stores/useCategoryStore';
import { useUomStore } from '@/stores/useUomStore';

import debounce from 'lodash-es/debounce';
import { storeToRefs } from 'pinia';

const toast = useToast();

const showCreateModal = ref(false);
const showUploadModal = ref(false);

const showEditModal = ref(false);
const currentProduct = ref<{ id: number; name: string; uom_id: string } | null>(null);


const form = useForm({
  name: '',
  uom_id: '' 
});

const breadcrumbs = [
  { title: 'Bahan', href: '/bahans' }
];

const bahanStore = useBahanStore();
const categoryStore = useCategoryStore();
const uomStore = useUomStore();

const { items: products, total, loading, filterName } = storeToRefs(bahanStore);
const { items: uoms } = storeToRefs(uomStore);

// Pagination state
const currentPage = ref(1);
const perPage = 10;
const totalProducts = ref(0);

const totalPages = computed(() => Math.ceil(totalProducts.value / perPage));

const fetchPage = async (page: number) => {
  await bahanStore.fetchBahans(page, perPage);
  totalProducts.value = total.value || 0;
  currentPage.value = page;
};

onMounted(async () => {
  await Promise.all([categoryStore.fetchCategories(), uomStore.fetchUoms()]);
  await fetchPage(currentPage.value);
});

const goToPage = async (page: number) => {
  if (page < 1 || page > totalPages.value) return;
  await fetchPage(page);
};

const nextPage = async () => {
  if (currentPage.value < totalPages.value) {
    await goToPage(currentPage.value + 1);
  }
};

const prevPage = async () => {
  if (currentPage.value > 1) {
    await goToPage(currentPage.value - 1);
  }
};

// Debounced filter handling
const debouncedSetFilter = debounce((field: string, value: string) => {
  bahanStore.setFilter(field, value);
}, 400);

const handleInput = (e: Event) => {
  const target = e.target as HTMLInputElement;
  debouncedSetFilter('name', target.value);
};

const handleCreate = async () => {
  if (!form.name) return toast.error("Name is required");
  if (!form.uom_id) return toast.error("UOM is required");

  try {
    await bahanStore.createBahan({
      name: form.name,
      uom_id: form.uom_id
    });
    toast.success("Bahan berhasil di buat");
    form.reset();
    showCreateModal.value = false;
    await fetchPage(currentPage.value); // Refresh page
  } catch (error: any) {
    const nameError = error?.response?.data?.errors?.name?.[0];
    toast.error(nameError || "Gagal buat data");
  }
};

const handleEdit = (product: { id: number; name: string; uom_id: string }) => {
  currentProduct.value = product;
  form.name = product.name;
  form.uom_id = product.uom_id;
  showEditModal.value = true;
};


const handleUpdate = async () => {
  if (!currentProduct.value || !form.name) return toast.error("Name is required");
  if (!form.uom_id) return toast.error("UOM is required");

  const productId = currentProduct.value.id;

  try {
    await bahanStore.updateBahan(productId, {
      name: form.name,
      uom_id: form.uom_id
    });
    toast.success("Bahan berhasil di update");
    form.reset();
    showEditModal.value = false;
    currentProduct.value = null;
    await fetchPage(currentPage.value); // Refresh current page
  } catch (error: any) {
    const nameError = error?.response?.data?.errors?.name?.[0];
    toast.error(nameError || "Gagal buat data");
  }
};

const handleDelete = async (id: number) => {
  if (!confirm('Yakin mau delete data Bahan?')) return;

  try {
    await bahanStore.deleteProduct(id);
    toast.success("Bahan deleted successfully");
    // If deleting last item on page and page > 1, go to previous page
    if (products.value.length === 1 && currentPage.value > 1) {
      await goToPage(currentPage.value - 1);
    } else {
      await fetchPage(currentPage.value);
    }
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to delete Bahan");
  }
};


const getUomName = (uomId: string) => {
  const uom = uoms.value.find(u => u.id === uomId);
  return uom?.name || 'N/A';
};


</script>

<template>

  <Head title="Bahan Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-4">
      <div class="flex justify-between items-center mb-6">
        <Button @click="showCreateModal = true" class="bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">
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

      <div class="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow class="bg-gray-100">
              <TableHead>ID</TableHead>
              <TableHead>Name</TableHead>
              <TableHead>UOM</TableHead>
              <TableHead class="w-24">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="product in products" :key="product.id">
              <TableCell>{{ product.id }}</TableCell>
              <TableCell>{{ product.name }}</TableCell>
              <TableCell>{{ getUomName(product.uom_id) }}</TableCell>

              <TableCell class="flex gap-2">
                <Button variant="ghost" size="icon" @click="handleEdit(product)">
                  <Edit class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="icon" @click="handleDelete(product.id)">
                  <Trash2 class="h-4 w-4" />
                </Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <!-- Pagination Controls -->
      <div class="flex justify-end mt-4 space-x-2">
        <button @click="prevPage" :disabled="currentPage === 1"
          class="px-3 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
          Previous
        </button>

        <template v-for="page in totalPages" :key="page">
          <button @click="goToPage(page)" :class="[
            'px-3 py-1 rounded border text-xs',
            page === currentPage
              ? 'bg-blue-600 border-blue-600 text-white'
              : 'border-gray-300 text-gray-700 hover:bg-gray-100'
          ]">
            {{ page }}
          </button>
        </template>

        <button @click="nextPage" :disabled="currentPage === totalPages"
          class="px-3 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
          Next
        </button>
      </div>

      <!-- upload image -->
     <!-- Modal Overlay -->
<div
  v-if="showUploadModal"
  class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
>
  <!-- Modal Box -->
  <div class="relative bg-white p-6 rounded-lg shadow-xl w-full max-w-sm md:max-w-md lg:max-w-lg xl:max-w-4xl max-h-[90vh] overflow-y-auto">

    <!-- Header dengan tombol X -->
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-lg font-semibold">Upload Media</h2>
      <button
        @click="showUploadModal = false"
        class="text-gray-500 hover:text-gray-800 transition p-1 rounded-full hover:bg-gray-100"
        aria-label="Tutup modal"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
          stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Footer -->
    <div class="mt-4 flex justify-end">
      <Button @click="showUploadModal = false" variant="outline">Tutup</Button>
    </div>
  </div>
</div>



      <!-- Create Modal -->
      <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">

        <div class="bg-white p-6 rounded-lg w-[1000px]">
          <h2 class="text-lg font-semibold mb-4">Buat Bahan Baru</h2>
          <form @submit.prevent="handleCreate">
            <div class="space-y-4">
              <div>
                <label class="block text-xs font-medium mb-1">Name</label>
                <Input v-model="form.name" placeholder="Bahan" class="text-xs" required />
              </div>
              <div>
                <label class="block text-xs font-medium mb-1">UOM</label>
                <select v-model="form.uom_id" class="w-full rounded-md border  text-xs border-input px-3 py-2" required>
                  <option value="">Select UOM</option>
                  <option v-for="uom in uoms" :key="uom.id" :value="uom.id">
                    {{ uom.name }}
                  </option>
                </select>
              </div>
            </div>
  
            <div class="flex justify-end gap-2 mt-4">
              <Button type="button" variant="outline" @click="showCreateModal = false">Cancel</Button>
              <Button type="submit" class="bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">Create</Button>
            </div>
          </form>
        </div>
      </div>

      <!-- Edit Modal -->
      <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg  w-[1000px]">
          <h2 class="text-lg font-semibold mb-4">Edit Product</h2>
          <form @submit.prevent="handleUpdate">
            <div class="space-y-4">
          
              <div>
                <label class="block text-xs font-medium mb-1">Name</label>
                <Input v-model="form.name" placeholder="Bahan" class="text-xs" required />
              </div>
              <div>
                <label class="block text-xs font-medium mb-1">UOM</label>
                <select v-model="form.uom_id" class="w-full text-xs rounded-md border border-input px-3 py-2" required>
                  <option value="">Select UOM</option>
                  <option v-for="uom in uoms" :key="uom.id" :value="uom.id">
                    {{ uom.name }}
                  </option>
                </select>
              </div>
            </div>
            <div class="flex justify-end gap-2 mt-4">
              <Button type="button" variant="outline" @click="showEditModal = false">Cancel</Button>
              <Button type="submit" class="bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">Update</Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
