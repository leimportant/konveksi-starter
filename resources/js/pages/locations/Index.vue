<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Trash2, Plus, Edit } from 'lucide-vue-next';
import { useToast } from "@/composables/useToast";
import { useLocationStore } from '@/stores/useLocationStore';
import { storeToRefs } from 'pinia';

const toast = useToast();

const showCreateModal = ref(false);
const showEditModal = ref(false);
const currentLocation = ref<{ id: string; name: string } | null>(null);

const form = useForm({
  id: '',
  name: '',
});

const breadcrumbs = [
  { title: 'Location', href: '/location' }
];

const locationStore = useLocationStore();
const { items: locations } = storeToRefs(locationStore);

onMounted(() => {
  locationStore.fetchLocations();
});

const handleCreate = async () => {
  if (!form.id) return toast.error("Id is required");
  if (!form.name) return toast.error("Name is required");

  try {
    await locationStore.createLocation(form.id, form.name);
    toast.success("location created successfully");
    form.reset();
    locationStore.loaded = false;
    await locationStore.fetchLocations();
    showCreateModal.value = false;
  } catch (error: any) {
    const nameError = error?.response?.data?.errors?.name?.[0];
    toast.error(nameError || "Failed to create location");
  }
};

const handleEdit = (location: { id: string; name: string }) => {
  currentLocation.value = location;
  form.id = location.id;
  form.name = location.name;
  showEditModal.value = true;
};

const handleUpdate = async () => {
  if (!currentLocation.value || !form.name) return toast.error("Name is required");
  

  try {
    await locationStore.updateLocation(currentLocation.value.id, form.name);
    toast.success("Location updated successfully");
    form.reset();
    locationStore.loaded = false;
    await locationStore.fetchLocations();
    showEditModal.value = false;
    currentLocation.value = null;
  } catch (error: any) {
    const nameError = error?.response?.data?.errors?.name?.[0];
    toast.error(nameError || "Failed to update Location");
  }
};

const handleDelete = async (id: string) => {
  if (!confirm('Are you sure you want to delete this location?')) return;

  try {
    await locationStore.deleteLocation(id);
    toast.success("location deleted successfully");
    locationStore.loaded = false;
    await locationStore.fetchLocations();
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to delete Location");
  }
};
</script>

<template>
  <Head title="Location Management" />
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
            <TableRow class="bg-gray-100">
              <TableHead>Id</TableHead>
              <TableHead>Name</TableHead>
              <TableHead class="w-24">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="loc in locations" :key="loc.id">
              <TableCell>{{ loc.id }}</TableCell>
              <TableCell>{{ loc.name }}</TableCell>
              <TableCell class="flex gap-2">
                <Button variant="ghost" size="icon" @click="handleEdit(loc)">
                  <Edit class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="icon" @click="handleDelete(loc.id)">
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
          <h2 class="text-lg font-semibold mb-4">Add New</h2>
          <form @submit.prevent="handleCreate">
            <div class="mb-4">
              <Input v-model="form.id" placeholder="location Id" required />
            </div>
            <div class="mb-4">
              <Input v-model="form.name" placeholder="location Name" required />
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
          <h2 class="text-lg font-semibold mb-4">Edit Location</h2>
          <form @submit.prevent="handleUpdate">
            <div class="mb-4">
              <Input v-model="form.id" placeholder="ID" required readonly/>
            </div>
            <div class="mb-4">
              <Input v-model="form.name" placeholder="location Name" required />
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
