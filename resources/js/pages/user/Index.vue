<template>
  <Head title="User Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-4">

      <!-- Add Button & Search -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <Button @click="showCreateModal = true"
          class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 flex items-center">
          <Plus class="h-4 w-4 mr-2" />
          Tambah User
        </Button>

        <Input v-model="filterName" placeholder="Search" @input="setFilter('name', $event)" class="w-full sm:w-64"
          aria-label="Search" />
      </div>

      <!-- Table -->
      <div class="overflow-x-auto rounded-md border">
        <Table class="min-w-[800px]">
          <TableHeader>
            <TableRow class="bg-gray-100">
              <TableHead>Name</TableHead>
              <TableHead>Phone Number</TableHead>
              <TableHead>Location</TableHead>
              <TableHead>Email</TableHead>
              <TableHead>Role</TableHead>
              <TableHead>Status</TableHead>
              <TableHead class="w-24">Actions</TableHead>
            </TableRow>
          </TableHeader>

          <TableBody>
            <TableRow v-for="user in users" :key="user.id">
              <TableCell :class="!user.active ? 'line-through text-red-500' : ''">{{ user.name }}</TableCell>
              <TableCell :class="!user.active ? 'line-through text-red-500' : ''">{{ user.phone_number }}</TableCell>
              <TableCell :class="!user.active ? 'line-through text-red-500' : ''">{{ user.location?.name || '-' }}</TableCell>
              <TableCell :class="!user.active ? 'line-through text-red-500' : ''">{{ user.email }}</TableCell>
              <TableCell :class="!user.active ? 'line-through text-red-500' : ''">
                {{ user.roles?.[0]?.name || user.employee_status }}
              </TableCell>
              <TableCell>
                <span :class="user.active ? 'text-green-600' : 'text-red-600'">
                  {{ user.active ? 'Active' : 'Inactive' }}
                </span>
              </TableCell>
              <TableCell class="flex gap-2">
                <Button variant="ghost" size="icon" @click="handleEdit(user.id)">
                  <Edit2 class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="icon" @click="handleDelete(user.id)">
                  <Trash2 class="h-4 w-4" />
                </Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <!-- Pagination -->
      <div class="flex flex-wrap justify-end mt-4 gap-2">
        <button @click="prevPage" :disabled="currentPage === 1 || loading"
          class="px-3 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
          Previous
        </button>

        <template v-for="page in totalPages" :key="page">
          <button @click="goToPage(page)"
            :class="[
              'px-3 py-1 rounded border text-sm',
              page === currentPage
                ? 'bg-blue-600 border-blue-600 text-white'
                : 'border-gray-300 text-gray-700 hover:bg-gray-100'
            ]"
            :disabled="loading">
            {{ page }}
          </button>
        </template>

        <button @click="nextPage" :disabled="currentPage === totalPages || loading"
          class="px-3 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
          Next
        </button>
      </div>

      <!-- Modal Create/Update -->
      <div v-if="showCreateModal"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 min-h-screen">

        <div class="bg-white p-6 rounded-lg w-full max-w-md space-y-4 max-h-[90vh] overflow-y-auto">
          <h2 class="text-lg font-semibold">Tambah User Baru</h2>

          <!-- Form Fields -->
          <div class="space-y-3">
            <!-- Name -->
            <div>
              <label class="block text-sm mb-1">Name</label>
              <input v-model="form.name" type="text" class="w-full border rounded px-3 py-2 text-sm"
                placeholder="Enter name" />
              <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
            </div>

            <!-- Email -->
            <div>
              <label class="block text-sm mb-1">Email</label>
              <input v-model="form.email" type="email" class="w-full border rounded px-3 py-2 text-sm"
                placeholder="Enter email" />
              <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
            </div>

            <!-- Phone Number -->
            <div>
              <label class="block text-sm mb-1">Phone Number</label>
              <input v-model="form.phone_number" type="text" class="w-full border rounded px-3 py-2 text-sm"
                placeholder="Enter phone number" />
              <p v-if="form.errors.phone_number" class="text-red-500 text-xs mt-1">{{ form.errors.phone_number }}</p>
            </div>

            <!-- Location -->
            <div>
              <label class="block text-sm mb-1">Location</label>
              <select v-model="form.location_id" class="w-full border rounded px-3 py-2 text-sm">
                <option value="">Select location</option>
                <option v-for="location in locations" :key="location.id" :value="location.id">
                  {{ location.name }}
                </option>
              </select>
              <p v-if="form.errors.location_id" class="text-red-500 text-xs mt-1">{{ form.errors.location_id }}</p>
            </div>

            <!-- Role -->
            <div>
              <label class="block text-sm mb-1">Role</label>
              <select v-model="form.role" class="w-full border rounded px-3 py-2 text-sm">
                <option value="">Select role</option>
                <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
              </select>
              <p v-if="form.errors.role" class="text-red-500 text-xs mt-1">{{ form.errors.role }}</p>
            </div>

            <!-- Active Checkbox -->
            <div>
              <label class="inline-flex items-center text-sm">
                <input type="checkbox" v-model="form.active" class="form-checkbox mr-2" />
                Active
              </label>
            </div>
          </div>

          <!-- Form Buttons -->
          <div class="flex justify-end gap-2 pt-2">
            <Button variant="outline" @click="showCreateModal = false">Cancel</Button>
            <Button v-if="!form.id" @click="handleCreate">Submit</Button>
            <Button v-else @click="handleUpdate">Update</Button>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>


<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Trash2, Plus, Edit2 } from 'lucide-vue-next';
import { useToast } from "@/composables/useToast";
import { useUserStore } from '@/stores/useUserStore';
import { storeToRefs } from 'pinia';
import axios from 'axios';

// Toast
const toast = useToast();

// State
const showCreateModal = ref(false);
// Add locations ref
const locations = ref<{ id: number; name: string }[]>([]);
// Form
interface UserForm {
  name: string;
  email: string;
  phone_number: string;
  location_id: number | string | null;
  role: number | string | null;
  active: boolean;
  id: number;
}

const form = useForm<UserForm>({
  name: '',
  email: '',
  phone_number: '',
  location_id: null,
  role: null,
  active: true,
  id: 0
});

// Breadcrumbs
const breadcrumbs = [
  { title: 'Users', href: '/users' }
];
// function cellClass(user: any) {
//   return !user.active ? 'line-through text-red-500' : ''
// }
// Store
const userStore = useUserStore();
const { users, currentPage, lastPage, loading, filterName } = storeToRefs(userStore);


const totalPages = computed(() => lastPage.value || 1);

const goToPage = async (page: number) => {
  if (page < 1 || page > totalPages.value) return;
  await userStore.fetchUsers(page);
};
const nextPage = () => goToPage(currentPage.value + 1);
const prevPage = () => goToPage(currentPage.value - 1);

const setFilter = async (field: string, event: Event) => {
  const target = event.target as HTMLInputElement;
  userStore.setFilter(field, target.value);
  await userStore.fetchUsers(); // fetch after filter change
};


// Roles
const roles = ref<{ id: number; name: string }[]>([]);

// Add fetchLocations function
const fetchLocations = async () => {
  try {
    const response = await axios.get('/api/locations');
    locations.value = response.data.data || [];
  } catch (error) {
    console.error(error);
    toast.error("Failed to fetch locations");
  }
};
// On mount
onMounted(() => {
  userStore.fetchUsers();
  fetchRoles();
  fetchLocations();
});

// Fetch roles from API
const fetchRoles = async () => {
  try {
    const response = await axios.get('/api/roles');
    roles.value = response.data.data || [];
  } catch (error) {
    console.error(error);
    toast.error("Failed to fetch roles");
  }
};

// Create user
const handleCreate = async () => {
  try {
    await userStore.createUser({
      name: form.name,
      email: form.email,
      phone_number: form.phone_number,
      location_id: form.location_id as number | null,
      role: form.role as number | null,
      active: form.active
    });

    toast.success("User created successfully");
    form.reset();
    await userStore.fetchUsers();
    showCreateModal.value = false;
  } catch (error: any) {
    const message = error?.response?.data?.message || "Failed to create user";
    toast.error(message);
    if (error?.response?.data?.errors) {
      form.setError(error.response.data.errors);
    }
  }
};

// handle edit
const handleEdit = async (id: number) => {
  const user = users.value.find((user) => user.id === id);
  if (!user) return;

  form.reset();

  form.name = user.name;
  form.email = user.email;
  form.phone_number = user.phone_number;
  form.location_id = user.location_id ?? null;
  form.role = user.roles && user.roles.length > 0 ? user.roles[0].id : null;
  form.active = user.active;
  form.id = user.id;
  showCreateModal.value = true;
};


// Update user
const handleUpdate = async () => {
  try {
    await userStore.updateUser(form.id, {
      name: form.name,
      email: form.email,
      phone_number: form.phone_number,
      location_id: form.location_id as number | null,
      role: form.role as number | null,
      active: form.active
    });

    toast.success("User updated successfully");
    form.reset();
    await userStore.fetchUsers();
    showCreateModal.value = false;
  } catch (error: any) {
    const message = error?.response?.data?.message || "Failed to update user";
    toast.error(message);
    if (error?.response?.data?.errors) {
      form.setError(error.response.data.errors);
    }
  }
};

// Delete user
const handleDelete = async (id: number) => {
  if (!confirm('Are you sure you want to delete this user?')) return;

  try {
    await userStore.deleteUser(id);
    toast.success("User deleted successfully");
    await userStore.fetchUsers();
  } catch (error: any) {
    toast.error(error?.response?.data?.message || "Failed to delete user");
  }
};
</script>
