<template>
  <Head title="Price Types" />
  <AppLayout>
    <div class="px-4 py-4">
      <div class="flex justify-between items-center gap-2 mb-2">
        <Button @click="showCreateModal = true" class="bg-indigo-600 text-white py-2 h-10 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">
          <Plus class="h-4 w-4" />
          Tambah data
        </Button>

        <Input
          v-model="filterName"
          placeholder="Search"
          @input="setFilter('name', $event)"
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
              <TableHead>Status</TableHead>
              <TableHead class="w-24">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="priceType in priceTypes" :key="priceType.id">
              <TableCell>{{ priceType.name }}</TableCell>
              <TableCell>
                <Badge :variant="priceType.is_active ? 'default' : 'secondary'">
                  {{ priceType.is_active ? 'Active' : 'Inactive' }}
                </Badge>
              </TableCell>
              <TableCell class="flex gap-2">
                <Button variant="ghost" size="icon" @click="handleEdit(priceType)">
                  <Edit class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="icon" @click="handleDelete(priceType.id)">
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
      <Dialog v-model:open="showCreateModal">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Create Price Type</DialogTitle>
          </DialogHeader>
          <div class="space-y-4">
            <div>
              <Label>Name</Label>
              <Input v-model="form.name" type="text" />
              <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
            </div>
            <div class="flex items-center space-x-2">
              <Checkbox id="is_active" v-model:checked="form.is_active" />
              <Label for="is_active">Active</Label>
            </div>
          </div>
          <DialogFooter>

            <div class="flex justify-end gap-2">
              <Button type="button" variant="outline" @click="showCreateModal = false"  class="h-10">Cancel</Button>
              <Button @click="handleCreate" class="bg-indigo-600 text-white py-2 h-10 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">Save</Button>
            </div>

            <!-- <Button variant="outline" @click="showCreateModal = false" class="h-10">Cancel</Button>
            <Button @click="handleCreate">Save</Button> -->
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <!-- Edit Modal -->
      <Dialog v-model:open="showEditModal">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Edit Price Type</DialogTitle>
          </DialogHeader>
          <div class="space-y-4">
            <div>
              <Label>Name</Label>
              <Input v-model="form.name" type="text" />
              <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
            </div>
            <div class="flex items-center space-x-2">
              <Checkbox id="is_active" v-model:checked="form.is_active" />
              <Label for="is_active">Active</Label>
            </div>
          </div>
          <DialogFooter>
            <div class="flex justify-end gap-2">
              <Button type="button" variant="outline" @click="showCreateModal = false"  class="h-10">Cancel</Button>
              <Button @click="handleUpdate" class="bg-indigo-600 text-white py-2 h-10 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">Update</Button>
            </div>

            <!-- <Button variant="outline" @click="showEditModal = false" class="h-10">Cancel</Button>
            <Button @click="handleUpdate">Update</Button> -->
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ref, onMounted, computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Edit, Trash2, Plus } from 'lucide-vue-next'
import { useToast } from "@/composables/useToast"
import { usePriceTypeStore } from '@/stores/usePriceTypeStore'
import { storeToRefs } from 'pinia'
import { Checkbox } from '@/components/ui/checkbox'
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'

const toast = useToast()
const showCreateModal = ref(false)
const showEditModal = ref(false)
const currentPriceType = ref<{ id: number; name: string; is_active: boolean } | null>(null)

const form = useForm({
  name: '',
  is_active: true as boolean // Cast to boolean type
})

const priceTypeStore = usePriceTypeStore()
const { priceTypes, currentPage, lastPage, loading, filterName } = storeToRefs(priceTypeStore)


const totalPages = computed(() => lastPage.value || 1);

const goToPage = async (page: number) => {
  if (page < 1 || page > totalPages.value) return;
  await priceTypeStore.fetchPriceTypes(page);
};
const nextPage = () => goToPage(currentPage.value + 1);
const prevPage = () => goToPage(currentPage.value - 1);

const setFilter = (field: string, event: Event) => {
  const target = event.target as HTMLInputElement;
  priceTypeStore.setFilter(field, target.value);
};

onMounted(async () => {
  await priceTypeStore.fetchPriceTypes()
})

const handleCreate = async () => {
  if (!form.name) return toast.error("Name is required")

  try {
    await priceTypeStore.createPriceType({
      name: form.name,
      is_active: form.is_active
    })
    toast.success("Price Type created successfully")
    form.reset()
    showCreateModal.value = false
  } catch (error: any) {
    const nameError = error?.response?.data?.errors?.name?.[0]
    toast.error(nameError || "Failed to create Price Type")
  }
}

const handleEdit = (priceType: { id: number; name: string; is_active: boolean }) => {
  currentPriceType.value = priceType
  form.name = priceType.name
  form.is_active = priceType.is_active
  showEditModal.value = true
}

const handleUpdate = async () => {
  if (!currentPriceType.value || !form.name) return toast.error("Name is required")

  try {
    await priceTypeStore.updatePriceType(currentPriceType.value.id, {
      name: form.name,
      is_active: form.is_active
    })
    toast.success("Price Type updated successfully")
    form.reset()
    await priceTypeStore.fetchPriceTypes()  // Remove priceTypeStore.loaded = false
    showEditModal.value = false
    currentPriceType.value = null
  } catch (error: any) {
    const nameError = error?.response?.data?.errors?.name?.[0]
    toast.error(nameError || "Failed to update Price Type")
  }
}

const handleDelete = async (id: number) => {
  try {
    await priceTypeStore.deletePriceType(id)
    toast.success("Price Type deleted successfully")
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to delete Price Type")
  }
}


</script>
