<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue'
import { useCustomerStore } from '@/stores/useCustomerStore'
import { storeToRefs } from 'pinia'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@/components/ui/table'
import { Trash2, Plus, Edit } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const customerStore = useCustomerStore()
const { customers, currentPage, lastPage, loading } = storeToRefs(customerStore)

const search = ref('')
const perPage = 10

const showCreateModal = ref(false)
const showEditModal = ref(false)

const breadcrumbs = [
  { title: 'Customer', href: '/Customers' }
];

interface CustomerForm {
  id: number
  name: string
  phone_number: string
  address: string
  credit_limit: number
  is_active: 'Y' | 'N'
}

const form = ref<CustomerForm>({
  id: 0,
  name: '',
  phone_number: '',
  address: '',
  credit_limit: 0,
  is_active: 'Y'
})

const resetForm = () => {
  form.value = {
    id: 0,
    name: '',
    phone_number: '',
    address: '',
    credit_limit: 0,
    is_active: 'Y'
  }
}

onMounted(() => {
  customerStore.fetchCustomers(currentPage.value, perPage)
})

const handleSearch = async () => {
  await customerStore.fetchCustomers(1, perPage, search.value)
}

const goToPage = async (page: number) => {
  if (page < 1 || page > lastPage.value) return
  await customerStore.fetchCustomers(page, perPage, search.value)
}

const nextPage = () => goToPage(currentPage.value + 1)
const prevPage = () => goToPage(currentPage.value - 1)

const openCreateModal = () => {
  resetForm()
  showCreateModal.value = true
}

const openEditModal = (customer: CustomerForm) => {
  form.value = { ...customer }
  showEditModal.value = true
}

const handleCreate = async () => {
  try {
   const {...data } = form.value
    await customerStore.createCustomer(data)
    toast.success('Customer created')
    resetForm()
    showCreateModal.value = false
    await customerStore.fetchCustomers(currentPage.value, perPage)
  } catch (e: any) {
    console.error(e)
    toast.error(e.response?.data?.message || 'Failed to create customer')
  }
}

const handleUpdate = async () => {
  try {
    if (!form.value.id) {
      throw new Error('Customer ID is required for update')
    }
    
    await customerStore.updateCustomer(form.value.id, {
      name: form.value.name,
      phone_number: form.value.phone_number,
      address: form.value.address,
      credit_limit: form.value.credit_limit,
      is_active: form.value.is_active
    })
    toast.success('Customer updated')
    showEditModal.value = false
    await customerStore.fetchCustomers(currentPage.value, perPage)
  } catch (e: any) {
    console.error(e)
    toast.error(e.response?.data?.message || 'Failed to update customer')
  }
}

const handleDelete = async (id: number) => {
  if (!confirm('Are you sure you want to delete this customer?')) return

  try {
    await customerStore.deleteCustomer(id)
    toast.success('Customer deleted')
    await customerStore.fetchCustomers(currentPage.value, perPage)
  } catch (e: any) {
    console.error(e)
    toast.error(e.response?.data?.message || 'Failed to delete customer')
  }
}
</script>

<template>
  <Head title="Customer Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
     <div class="px-4 py-4">
    <div class="flex justify-between items-center mb-4">
        <Button @click="openCreateModal" :disabled="loading" class="bg-indigo-600 text-white py-2 h-10 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">
          <Plus class="h-4 w-4 mr-1" /> Tambah
        </Button>
        <Input
          v-model="search"
          placeholder="Search customer name"
          class="w-64"
          @input="handleSearch"
          :disabled="loading"
        />

    </div>

    <!-- Table -->
    <div class="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow class="bg-gray-100">
            <TableHead>Name</TableHead>
            <TableHead>Phone</TableHead>
            <TableHead>Address</TableHead>
            <TableHead>Status</TableHead>
            <TableHead class="w-24">Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="customer in customers" :key="customer.id">
            <TableCell>{{ customer.name }}</TableCell>
            <TableCell>{{ customer.phone_number }}</TableCell>
            <TableCell>{{ customer.address }}</TableCell>
            <TableCell>
              <span
                :class="customer.is_active === 'Y' ? 'text-green-600' : 'text-red-500'"
              >
                {{ customer.is_active === 'Y' ? 'Active' : 'Inactive' }}
              </span>
            </TableCell>
            <TableCell class="flex gap-2">
              <Button variant="ghost" size="icon" @click="openEditModal(customer)">
                <Edit class="h-4 w-4" />
              </Button>
              <Button
                variant="ghost"
                size="icon"
                @click="handleDelete(customer.id)"
                :disabled="loading"
              >
                <Trash2 class="h-4 w-4" />
              </Button>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
      <div v-if="loading" class="text-center py-4 text-gray-500">Loading...</div>
      <div v-if="!loading && customers.length === 0" class="text-center py-4 text-gray-500">No customers found.</div>
    </div>

    <!-- Pagination -->
    <div class="flex justify-end mt-4 space-x-2">
      <Button variant="outline" @click="prevPage" :disabled="currentPage === 1 || loading">Previous</Button>
      <Button
        v-for="page in lastPage"
        :key="page"
        @click="goToPage(page)"
        :variant="page === currentPage ? 'default' : 'outline'"
        :disabled="loading"
      >
        {{ page }}
      </Button>
      <Button variant="outline" @click="nextPage" :disabled="currentPage === lastPage || loading">Next</Button>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-lg w-96">
        <h2 class="text-lg font-semibold mb-4">Add Customer</h2>
        <form @submit.prevent="handleCreate" class="space-y-3">
          <Input v-model="form.name" placeholder="Name" required />
          <Input v-model="form.phone_number" placeholder="Phone" required />
          <Input v-model="form.address" placeholder="Address" required />
          <Input v-model="form.credit_limit" placeholder="Credit Limit" type="number" required />
          <select v-model="form.is_active" class="w-full border rounded px-2 py-1">
            <option value="Y">Active</option>
            <option value="N">Inactive</option>
          </select>
          <div class="flex justify-end gap-2 pt-2">
            <Button type="button" variant="outline" @click="showCreateModal = false"  class="h-10">Cancel</Button>
            <Button type="submit" :disabled="loading">Create</Button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-lg w-96">
        <h2 class="text-lg font-semibold mb-4">Edit Customer</h2>
        <form @submit.prevent="handleUpdate" class="space-y-3">
          <Input v-model="form.name" placeholder="Name" required />
          <Input v-model="form.phone_number" placeholder="Phone" required />
          <Input v-model="form.address" placeholder="Address" required />
          <Input v-model="form.credit_limit" placeholder="Credit Limit" type="number" required />
          <select v-model="form.is_active" class="w-full border rounded px-2 py-1">
            <option value="Y">Active</option>
            <option value="N">Inactive</option>
          </select>
          <div class="flex justify-end gap-2 pt-2">
            <Button type="button" variant="outline" @click="showEditModal = false" class="h-10">Cancel</Button>
            <Button type="submit" :disabled="loading">Update</Button>
          </div>
        </form>
      </div>
    </div>
  </div>
  </AppLayout>
 
</template>
