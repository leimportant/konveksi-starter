<template>
  <Head title="User Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <!-- Add User Button -->
      <div class="flex justify-between items-center mb-6">
        <Button @click="showCreateModal = true">
          <Plus class="h-4 w-4 mr-2" />
          Add User
        </Button>
      </div>

      <!-- Users Table -->
      <div class="rounded-md border">
        <Table>
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
              <TableCell>{{ user.name }}</TableCell>
              <TableCell>{{ user.phone_number }}</TableCell>
              <TableCell>{{ user.location?.name || '-' }}</TableCell>
              <TableCell>{{ user.email }}</TableCell>
              <TableCell>{{ user.role || 'User' }}</TableCell>
              <TableCell>
                <span :class="user.active ? 'text-green-600' : 'text-red-600'">
                  {{ user.active ? 'Active' : 'Inactive' }}
                </span>
              </TableCell>
              <TableCell class="flex gap-2">
                <Button variant="ghost" size="icon" @click="handleDelete(user.id)">
                  <Trash2 class="h-4 w-4" />
                </Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <!-- Create User Modal -->
      <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg w-full max-w-md">
          <h2 class="text-base font-semibold mb-4">Add New User</h2>

          <!-- Name -->
          <div class="mb-3">
            <label class="block text-sm mb-1">Name</label>
            <input
              v-model="form.name"
              type="text"
              class="w-full border rounded px-3 py-2 text-sm"
              placeholder="Enter name"
            />
            <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
          </div>

          <!-- Email -->
          <div class="mb-3">
            <label class="block text-sm mb-1">Email</label>
            <input
              v-model="form.email"
              autocomplete="false"
              type="email"
              class="w-full border rounded px-3 py-2 text-sm"
              placeholder="Enter email"
            />
            <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
          </div>

          <!-- Password -->
          <div class="mb-3">
            <label class="block text-sm mb-1">Phone Number</label>
            <input
              v-model="form.phone_number"
              autocomplete="false"
              type="text"
              class="w-full border rounded px-3 py-2 text-sm"
              placeholder="Enter phone number"
            />
            <p v-if="form.errors.phone_number" class="text-red-500 text-xs mt-1">{{ form.errors.phone_number }}</p>
          </div>

          <div class="mb-3">
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
          <div class="mb-3">
            <label class="block text-sm mb-1">Role</label>
            <select v-model="form.role" class="w-full border rounded px-3 py-2 text-sm">
              <option value="">Select role</option>
              <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
            </select>
            <p v-if="form.errors.role" class="text-red-500 text-xs mt-1">{{ form.errors.role }}</p>
          </div>

          <!-- Active -->
          <div class="mb-4">
            <label class="inline-flex items-center text-sm">
              <input
                type="checkbox"
                v-model="form.active"
                class="form-checkbox mr-2"
              />
              Active
            </label>
          </div>

          <!-- Buttons -->
          <div class="flex justify-end gap-2">
            <Button variant="outline" @click="showCreateModal = false">Cancel</Button>
            <Button @click="handleCreate">Submit</Button>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Trash2, Plus } from 'lucide-vue-next';
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
const form = useForm({
  name: '',
  email: '',
  phone_number: '',
  location_id: '',
  role: '',
  active: true
});

// Breadcrumbs
const breadcrumbs = [
  { title: 'Users', href: '/users' }
];

// Store
const userStore = useUserStore();
const { users } = storeToRefs(userStore);

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
      location_id: form.location_id,
      role: form.role,
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
