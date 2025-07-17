<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Trash2, Plus } from 'lucide-vue-next';
import { useToast } from "@/composables/useToast";
import { useSizeStore } from '@/stores/useSizeStore';
import { storeToRefs } from 'pinia';
import debounce from 'lodash-es/debounce';

const toast = useToast();

const showCreateModal = ref(false);
const showEditModal = ref(false);
const currentUom = ref<{ id: number; name: string } | null>(null);

const form = useForm({
  name: '',
});

const breadcrumbs = [
  { title: 'Ukuran', href: '/sizes' }
];

const useStore = useSizeStore();

const { sizes: sizes, currentPage, lastPage, loading, filterName } = storeToRefs(useStore);

const totalPages = computed(() => lastPage.value || 1);

const goToPage = async (page: number) => {
  if (page < 1 || page > totalPages.value) return;
  await useStore.fetchSizes(page);
};
const nextPage = () => goToPage(currentPage.value + 1);
const prevPage = () => goToPage(currentPage.value - 1);



const debouncedSetFilter = debounce((field: string, value: string) => {
  useStore.setFilter(field, value);
}, 400);

const handleInput = (e: Event) => {
  const target = e.target as HTMLInputElement;
  debouncedSetFilter('name', target.value);
};


onMounted(() => {
    useStore.fetchSizes();
});

const handleCreate = async () => {
  if (!form.name) return toast.error("Name is required");

  try {
    await useStore.createSize(form.name);
    toast.success("Size created successfully");
    form.reset();
    useStore.loaded = false;
    await useStore.fetchSizes();
    showCreateModal.value = false;
  } catch (error: any) {
    const nameError = error?.response?.data?.errors?.name?.[0];
    toast.error(nameError || "Failed to create Size");
  }
};

// const handleEdit = (uom: { id: number; name: string }) => {
//   currentUom.value = uom;
//   form.name = uom.name;
//   showEditModal.value = true;
// };

const handleUpdate = async () => {
  if (!currentUom.value || !form.name) return toast.error("Name is required");

  try {
    await useStore.updateSize(currentUom.value.id, form.name);
    toast.success("Size updated successfully");
    form.reset();
    useStore.loaded = false;
    await useStore.fetchSizes();
    showEditModal.value = false;
    currentUom.value = null;
  } catch (error: any) {
    const nameError = error?.response?.data?.errors?.name?.[0];
    toast.error(nameError || "Failed to update UOM");
  }
};

const handleDelete = async (id: number) => {
  if (!confirm('Are you sure you want to delete this UOM?')) return;

  try {
    await useStore.deleteSize(id);
    useStore.loaded = false;
    await useStore.fetchSizes();
    toast.success("Size deleted successfully");
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to delete UOM");
  }
};
</script>

<template>
  <Head title="Size Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-4">
      <div class="flex justify-between items-center mb-2">
        <Button @click="showCreateModal = true" class="bg-indigo-600 text-white py-2 h-10 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">
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
              <TableHead>Name</TableHead>
              <TableHead class="w-24">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="data in sizes" :key="data.id">
              <TableCell>{{ data.name }}</TableCell>
              <TableCell class="flex gap-2">
                <!-- <Button variant="ghost" size="icon" @click="handleEdit(data)">
                  <Edit class="h-4 w-4" />
                </Button> -->
                <Button variant="ghost" size="icon" @click="handleDelete(data.id)">
                  <Trash2 class="h-4 w-4" />
                </Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

        <!-- Pagination -->
      <div class="flex justify-end mt-4 space-x-2">
        <button
          @click="prevPage"
          :disabled="currentPage === 1 || loading"
          class="px-3 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Previous
        </button>

        <template v-for="page in totalPages" :key="page">
          <button
            @click="goToPage(page)"
            :class="[
              'px-3 py-1 rounded border text-sm',
              page === currentPage ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-100'
            ]"
            :disabled="loading"
          >
            {{ page }}
          </button>
        </template>

        <button
          @click="nextPage"
          :disabled="currentPage === totalPages || loading"
          class="px-3 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Next
        </button>
      </div>
      
      <!-- Create Modal -->
      <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg w-96">
          <h2 class="text-lg font-semibold mb-4">Tambah Size Baru</h2>
          <form @submit.prevent="handleCreate">
            <div class="mb-4">
              <Input v-model="form.name" placeholder="Size Name" required />
            </div>
            <div class="flex justify-end gap-2">
              <Button type="button" variant="outline" @click="showCreateModal = false"  class="h-10">Cancel</Button>
              <Button type="submit" class="bg-indigo-600 text-white py-2 h-10 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">Create</Button>
            </div>
          </form>
        </div>
      </div>

      <!-- Edit Modal -->
      <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg w-96">
          <h2 class="text-lg font-semibold mb-4">Edit Size</h2>
          <form @submit.prevent="handleUpdate">
            <div class="mb-4">
              <Input v-model="form.name" placeholder="Size Name" required />
            </div>
            <div class="flex justify-end gap-2">
              <Button type="button" variant="outline" @click="showEditModal = false" class="h-10">Cancel</Button>
              <Button type="submit" class="bg-indigo-600 text-white py-2 h-10 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">Update</Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
