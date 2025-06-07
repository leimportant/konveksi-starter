<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Trash2, Plus, Edit } from 'lucide-vue-next';
import { useToast } from "@/composables/useToast";
import { useSlocStore } from '@/stores/useSlocStore';
import { storeToRefs } from 'pinia';

const toast = useToast();

const showCreateModal = ref(false);
const showEditModal = ref(false);
const currentSloc = ref<{ id: string; name: string } | null>(null);

const form = useForm({
  id: '',
  name: '',
});

const breadcrumbs = [
  { title: 'Sloc', href: '/slocs' }
];

const slocStore = useSlocStore();
const { items: slocs, currentPage, lastPage, loading, filterName } = storeToRefs(slocStore);


const totalPages = computed(() => lastPage.value || 1);

const goToPage = async (page: number) => {
  if (page < 1 || page > totalPages.value) return;
  await slocStore.fetchSlocs(page);
};
const nextPage = () => goToPage(currentPage.value + 1);
const prevPage = () => goToPage(currentPage.value - 1);

const setFilter = async (field: string, event: Event) => {
  const target = event.target as HTMLInputElement;
  slocStore.setFilter(field, target.value);
  await slocStore.fetchSlocs(); // fetch after filter change
};


onMounted(() => {
  slocStore.fetchSlocs();
});

const handleCreate = async () => {
  if (!form.id) return toast.error("Id is required");
  if (!form.name) return toast.error("Name is required");

  try {
    await slocStore.createSloc(form.id, form.name);
    toast.success("Sloc created successfully");
    form.reset();
    slocStore.loaded = false;
    await slocStore.fetchSlocs();
    showCreateModal.value = false;
  } catch (error: any) {
    const nameError = error?.response?.data?.errors?.name?.[0];
    toast.error(nameError || "Failed to create Sloc");
  }
};

const handleEdit = (sloc: { id: string; name: string }) => {
  currentSloc.value = sloc;
  form.id = sloc.id;
  form.name = sloc.name;
  showEditModal.value = true;
};

const handleUpdate = async () => {
  if (!currentSloc.value || !form.name) return toast.error("Name is required");
  

  try {
    await slocStore.updateSloc(currentSloc.value.id, form.name);
    toast.success("Sloc updated successfully");
    form.reset();
    slocStore.loaded = false;
    await slocStore.fetchSlocs();
    showEditModal.value = false;
    currentSloc.value = null;
  } catch (error: any) {
    const nameError = error?.response?.data?.errors?.name?.[0];
    toast.error(nameError || "Failed to update Sloc");
  }
};

const handleDelete = async (id: string) => {
  if (!confirm('Are you sure you want to delete this Sloc?')) return;

  try {
    await slocStore.deleteSloc(id);
    toast.success("Sloc deleted successfully");
    slocStore.loaded = false;
    await slocStore.fetchSlocs();
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to delete Sloc");
  }
};
</script>

<template>
  <Head title="Sloc Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="flex justify-between items-center mb-6">
        <Button @click="showCreateModal = true">
          <Plus class="h-4 w-4" />
          Add
        </Button>

         <Input
          v-model="filterName"
          placeholder="Search"
         @input="setFilter('name', $event)"
          class="w-64"
          aria-label="Search"
        />

      </div>

      <div class="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow class="bg-gray-100">
              <TableHead>Id</TableHead>
              <TableHead>Name</TableHead>
              <TableHead class="w-24">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="sloc in slocs" :key="sloc.id">
              <TableCell>{{ sloc.id }}</TableCell>
              <TableCell>{{ sloc.name }}</TableCell>
              <TableCell class="flex gap-2">
                <Button variant="ghost" size="icon" @click="handleEdit(sloc)">
                  <Edit class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="icon" @click="handleDelete(sloc.id)">
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
          <h2 class="text-lg font-semibold mb-4">Add New</h2>
          <form @submit.prevent="handleCreate">
            <div class="mb-4">
              <Input v-model="form.id" placeholder="Sloc Id" required />
            </div>
            <div class="mb-4">
              <Input v-model="form.name" placeholder="Sloc Name" required />
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
          <h2 class="text-lg font-semibold mb-4">Edit Sloc</h2>
          <form @submit.prevent="handleUpdate">
            <div class="mb-4">
              <Input v-model="form.id" placeholder="ID" required readonly/>
            </div>
            <div class="mb-4">
              <Input v-model="form.name" placeholder="Sloc Name" required />
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
