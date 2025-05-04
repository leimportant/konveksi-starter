<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Edit, Trash2, Plus } from 'lucide-vue-next';
import { useToast } from "@/composables/useToast";
import { useUomStore } from '@/stores/useUomStore';
import { storeToRefs } from 'pinia';

const toast = useToast();

const showCreateModal = ref(false);
const showEditModal = ref(false);
const currentUom = ref<{ id: number; name: string } | null>(null);

const form = useForm({
  name: '',
});

const breadcrumbs = [
  { title: 'UOM', href: '/uoms' }
];

const uomStore = useUomStore();
const { items: uoms } = storeToRefs(uomStore);

onMounted(() => {
  uomStore.fetchUoms();
});

const handleCreate = async () => {
  if (!form.name) return toast.error("Name is required");

  try {
    await uomStore.createUom(form.name);
    toast.success("UOM created successfully");
    form.reset();
    uomStore.loaded = false;
    await uomStore.fetchUoms();
    showCreateModal.value = false;
  } catch (error: any) {
    const nameError = error?.response?.data?.errors?.name?.[0];
    toast.error(nameError || "Failed to create UOM");
  }
};

const handleEdit = (uom: { id: number; name: string }) => {
  currentUom.value = uom;
  form.name = uom.name;
  showEditModal.value = true;
};

const handleUpdate = async () => {
  if (!currentUom.value || !form.name) return toast.error("Name is required");

  try {
    await uomStore.updateUom(currentUom.value.id, form.name);
    toast.success("UOM updated successfully");
    form.reset();
    uomStore.loaded = false;
    await uomStore.fetchUoms();
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
    await uomStore.deleteUom(id);
    toast.success("UOM deleted successfully");
    uomStore.loaded = false;
    await uomStore.fetchUoms();
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to delete UOM");
  }
};
</script>

<template>
  <Head title="UOM Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold">Unit of Measurement</h1>
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
            <TableRow v-for="uom in uoms" :key="uom.id">
              <TableCell>{{ uom.name }}</TableCell>
              <TableCell class="flex gap-2">
                <Button variant="ghost" size="icon" @click="handleEdit(uom)">
                  <Edit class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="icon" @click="handleDelete(uom.id)">
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
          <h2 class="text-lg font-semibold mb-4">Add New UOM</h2>
          <form @submit.prevent="handleCreate">
            <div class="mb-4">
              <Input v-model="form.name" placeholder="UOM Name" required />
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
          <h2 class="text-lg font-semibold mb-4">Edit UOM</h2>
          <form @submit.prevent="handleUpdate">
            <div class="mb-4">
              <Input v-model="form.name" placeholder="UOM Name" required />
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
