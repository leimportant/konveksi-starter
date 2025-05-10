<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Edit, Trash2, Plus } from 'lucide-vue-next';
import { useToast } from "@/composables/useToast";
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

const categoryStore = useCategoryStore ();
const { items: Categorys } = storeToRefs(categoryStore);

onMounted(() => {
  categoryStore.fetchCategories();
});

const handleCreate = async () => {
  if (!form.name) return toast.error("Name is required");

  try {
    await categoryStore.createCategory(form.name);
    toast.success("Category created successfully");
    form.reset();
    categoryStore.loaded = false;
    await categoryStore.fetchCategories();
    showCreateModal.value = false;
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
    categoryStore.loaded = false;
    await categoryStore.fetchCategories();
    showEditModal.value = false;
    currentCategory.value = null;
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
    categoryStore.loaded = false;
    await categoryStore.fetchCategories();
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to delete Category");
  }
};
</script>

<template>
  <Head title="Category Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="flex justify-between items-center mb-6">
        <Button @click="showCreateModal = true">
          <Plus class="h-4 w-4" />
          Add
        </Button>
      </div>

      <div class="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Name</TableHead>
              <TableHead class="w-24">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="Category in Categorys" :key="Category.id">
              <TableCell>{{ Category.name }}</TableCell>
              <TableCell class="flex gap-2">
                <Button variant="ghost" size="icon" @click="handleEdit(Category)">
                  <Edit class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="icon" @click="handleDelete(Category.id)">
                  <Trash2 class="h-4 w-4" />
                </Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <!-- Create Modal -->
      <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg w-96">
          <h2 class="text-lg font-semibold mb-4">Add New Category</h2>
          <form @submit.prevent="handleCreate">
            <div class="mb-4">
              <Input v-model="form.name" placeholder="Category Name" required />
            </div>
            <div class="flex justify-end gap-2">
              <Button type="button" variant="outline" @click="showCreateModal = false">Cancel</Button>
              <Button type="submit">Create</Button>
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
              <Input v-model="form.name" placeholder="Category Name" required />
            </div>
            <div class="flex justify-end gap-2">
              <Button type="button" variant="outline" @click="showEditModal = false">Cancel</Button>
              <Button type="submit">Update</Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
