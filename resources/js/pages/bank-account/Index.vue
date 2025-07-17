<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Trash2, Plus, Edit } from 'lucide-vue-next';
import { useToast } from "@/composables/useToast";
import { useBankAccountStore } from '@/stores/useBankAccountStore';
import { storeToRefs } from 'pinia';
import debounce from 'lodash-es/debounce';

const toast = useToast();

const showCreateModal = ref(false);
const showEditModal = ref(false);
const currentData = ref<{ id: string; name: string, account_number: string } | null>(null);

const form = useForm({
  id: '',
  name: '',
  account_number: ''
});

const breadcrumbs = [
  { title: 'Bank Account', href: '/bank-account' }
];

const useStore = useBankAccountStore();

const { bankAccount: bankAccount, currentPage, lastPage, loading, filterName } = storeToRefs(useStore);

const totalPages = computed(() => lastPage.value || 1);

const goToPage = async (page: number) => {
  if (page < 1 || page > totalPages.value) return;
  await useStore.fetchBankAccount(page);
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
    useStore.fetchBankAccount();
});

const handleCreate = async () => {
  if (!form.name) return toast.error("Name is required");

  try {
    await useStore.createBankAccount(form.id, form.name, form.account_number);
    toast.success("Bank Account created successfully");
    form.reset();
    useStore.loaded = false;
    await useStore.fetchBankAccount();
    showCreateModal.value = false;
  } catch (error: any) {
    const nameError = error?.response?.data?.errors?.name?.[0];
    toast.error(nameError || "Failed to create Bank Account ");
  }
};

const handleEdit = (bankAccount: { id: string; name: string; account_number: string }) => {
  currentData.value = bankAccount;
  form.id = bankAccount.id;
  form.name = bankAccount.name;
  form.account_number = bankAccount.account_number;
  showEditModal.value = true;
};

const handleUpdate = async () => {
  if (!currentData.value || !form.name) return toast.error("Name is required");
  if (!currentData.value || !form.account_number) return toast.error("Account Numberis required");
  try {
    await useStore.updateBankAccount(currentData.value.id, form.name, form.account_number);
    toast.success("Bank Account updated successfully");
    form.reset();
    useStore.loaded = false;
    await useStore.fetchBankAccount();
    showEditModal.value = false;
    currentData.value = null;
  } catch (error: any) {
    const nameError = error?.response?.data?.errors?.name?.[0];
    toast.error(nameError || "Failed to update Bank Account");
  }
};

const handleDelete = async (id: string) => {
  if (!confirm('Are you sure you want to delete this Bank Account?')) return;

  try {
    await useStore.deleteBankAccount(id);
    useStore.loaded = false;
    await useStore.fetchBankAccount();
    toast.success("Bank Account deleted successfully");
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to delete Bank Account");
  }
};
</script>

<template>
  <Head title="Bank Account Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-4">
      <div class="flex justify-between items-center gap-2 mb-2">
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
              <TableHead>Id</TableHead>
              <TableHead>Name</TableHead>
              <TableHead>No Rekening</TableHead>
              <TableHead class="w-24">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="data in bankAccount" :key="data.id">
              <TableCell>{{ data.id }}</TableCell>
              <TableCell>{{ data.name }}</TableCell>
              <TableCell>{{ data.account_number }}</TableCell>
              <TableCell class="flex gap-2">
                <Button variant="ghost" size="icon" @click="handleEdit(data)">
                  <Edit class="h-4 w-4" />
                </Button>
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
          <h2 class="text-lg font-semibold mb-4">Tambah Bank Account Baru</h2>
          <form @submit.prevent="handleCreate">
            <div class="mb-4">
              <Input v-model="form.id" placeholder="BCA" required />
            </div>
            <div class="mb-4">
              <Input v-model="form.name" placeholder="Nama" required />
            </div>
            <div class="mb-4">
              <Input v-model="form.account_number" placeholder="1234498" required />
            </div>
            <div class="flex justify-end gap-2">
              <Button type="button" variant="outline" @click="showCreateModal = false">Cancel</Button>
              <Button type="submit" class="bg-indigo-600 text-white py-2 h-10 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">Create</Button>
            </div>
          </form>
        </div>
      </div>

      <!-- Edit Modal -->
      <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg w-96">
          <h2 class="text-lg font-semibold mb-4">Edit Bank Account</h2>
          <form @submit.prevent="handleUpdate">
             <div class="mb-4">
              <Input v-model="form.id" placeholder="BCA" required readonly/>
            </div>
            <div class="mb-4">
              <Input v-model="form.name" placeholder="Nama" required />
            </div>
            <div class="mb-4">
              <Input v-model="form.account_number" placeholder="1234498" required />
            </div>
            <div class="flex justify-end gap-2">
              <Button type="button" variant="outline" @click="showEditModal = false">Cancel</Button>
              <Button type="submit" class="bg-indigo-600 text-white py-2 h-10 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">Update</Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
