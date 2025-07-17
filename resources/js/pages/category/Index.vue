<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Edit, Trash2, Plus } from 'lucide-vue-next';
import { useToast } from '@/composables/useToast';
import { useCategoryStore } from '@/stores/useCategoryStore';
import { storeToRefs } from 'pinia';

const toast = useToast();

const showCreateModal = ref(false);
const showEditModal = ref(false);
const currentCategory = ref<{ id: number; name: string } | null>(null);

const form = useForm({
  name: '',
});

const breadcrumbs = [
  { title: 'Category', href: '/categories' }
];

const categoryStore = useCategoryStore();
const { items: Categorys, currentPage, lastPage, loading, filterName } = storeToRefs(categoryStore);

// totalPages computed dari lastPage store
const totalPages = computed(() => lastPage.value || 1);

// Pagination navigation
const goToPage = async (page: number) => {
  if (page < 1 || page > totalPages.value) return;
  await categoryStore.fetchCategories(page);
};
const nextPage = () => goToPage(currentPage.value + 1);
const prevPage = () => goToPage(currentPage.value - 1);

// Filter handling — cukup panggil setFilter di store
const setFilter = (field: string, event: Event) => {
  const target = event.target as HTMLInputElement;
  categoryStore.setFilter(field, target.value);
};

// Modal control
const openCreateModal = () => {
  form.reset();
  showCreateModal.value = true;
};
const closeCreateModal = () => {
  showCreateModal.value = false;
  form.reset();
};
const closeEditModal = () => {
  showEditModal.value = false;
  currentCategory.value = null;
  form.reset();
};

onMounted(() => {
  categoryStore.fetchCategories();
});

// CRUD handlers
const handleCreate = async () => {
  if (!form.name) return toast.error("Name is required");

  try {
    await categoryStore.createCategory(form.name);
    toast.success("Category created successfully");
    form.reset();
    await categoryStore.fetchCategories(currentPage.value);
    closeCreateModal();
  } catch (error: any) {
    const nameError = error?.response?.data?.errors?.name?.[0];
    toast.error(nameError || "Failed to create Category");
  }
};

const handleEdit = (Category: { id: number; name: string }) => {
  currentCategory.value = Category;
  form.name = Category.name;
  showEditModal.value = true;
};

const handleUpdate = async () => {
  if (!currentCategory.value || !form.name) return toast.error("Name is required");

  try {
    await categoryStore.updateCategory(currentCategory.value.id, form.name);
    toast.success("Category updated successfully");
    form.reset();
    await categoryStore.fetchCategories(currentPage.value);
    closeEditModal();
  } catch (error: any) {
    const nameError = error?.response?.data?.errors?.name?.[0];
    toast.error(nameError || "Failed to update Category");
  }
};

const handleDelete = async (id: number) => {
  if (!confirm('Are you sure you want to delete this Category?')) return;

  try {
    await categoryStore.deleteCategory(id);
    toast.success("Category deleted successfully");
    await categoryStore.fetchCategories(currentPage.value);
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to delete Category");
  }
};
</script>

<template>
  <Head title="Category Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-4">
      <div class="flex justify-between items-center gap-2 mb-2">
        <Button @click="openCreateModal" aria-label="Tambah Kategori Baru" :disabled="loading" class="bg-indigo-600 text-white py-2 h-10 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">
          <Plus class="h-4 w-4" />
          Add
        </Button>

        <Input
          v-model="filterName"
          placeholder="Search Categories"
          @input="setFilter('name', $event)"
          class="w-64"
          aria-label="Search Categories"
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
            <TableRow v-for="Category in Categorys" :key="Category.id">
              <TableCell>{{ Category.name }}</TableCell>
              <TableCell class="flex gap-2">
                <Button variant="ghost" size="icon" @click="handleEdit(Category)" aria-label="Edit category" :disabled="loading">
                  <Edit class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="icon" @click="handleDelete(Category.id)" aria-label="Delete category" :disabled="loading">
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
          <h2 class="text-lg font-semibold mb-4">Tambah Kategori Baru</h2>
          <form @submit.prevent="handleCreate">
            <div class="mb-4">
              <Input v-model="form.name" placeholder="Category Name" required aria-label="Category Name" />
            </div>
            <div class="flex justify-end gap-2">
              <Button type="button" variant="outline" @click="closeCreateModal">Cancel</Button>
              <Button type="submit" class="bg-indigo-600 text-white py-2 h-10 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">Simpan</Button>
            </div>
          </form>
        </div>
      </div>

      <!-- Edit Modal -->
      <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg w-96">
          <h2 class="text-lg font-semibold mb-4">Edit Category</h2>
          <form @submit.prevent="handleUpdate">
            <div class="mb-4">
              <Input v-model="form.name" placeholder="Category Name" required aria-label="Category Name" />
            </div>
            <div class="flex justify-end gap-2">
              <Button type="button" variant="outline" @click="closeEditModal">Cancel</Button>
              <Button type="submit" class="bg-indigo-600 text-white py-2 h-10 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">Simpan</Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
