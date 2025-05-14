<template>
  <Head title="Price Types" />
  <AppLayout>
    <div class="px-4 py-6">
      <div class="flex justify-between items-center mb-6">
        <Button @click="showCreateModal = true">
          <Plus class="h-4 w-4" />
          Add New
        </Button>
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
            <Button variant="outline" @click="showCreateModal = false">Cancel</Button>
            <Button @click="handleCreate">Save</Button>
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
            <Button variant="outline" @click="showEditModal = false">Cancel</Button>
            <Button @click="handleUpdate">Update</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
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
const { priceTypes } = storeToRefs(priceTypeStore)

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
