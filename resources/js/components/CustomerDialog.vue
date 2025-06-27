<script setup lang="ts">
import { ref, watch } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import axios from 'axios';

const props = defineProps<{ show: boolean }>();
const emit = defineEmits(['update:show', 'customerSelected']);

const searchQuery = ref('');
const customers = ref<any[]>([]);
const newCustomerName = ref('');
const newCustomerPhone = ref('');
const newCustomerAddress = ref('');
const showAddCustomerForm = ref(false);

const searchCustomers = async () => {
  if (searchQuery.value.length < 2) {
    customers.value = [];
    return;
  }
  try {
    const response = await axios.get(`/api/customers/search?query=${searchQuery.value}`);
    customers.value = response.data;
  } catch (error) {
    console.error('Error searching customers:', error);
  }
};

const addCustomer = async () => {
  try {
    const response = await axios.post('/api/customers', {
      name: newCustomerName.value,
      phone: newCustomerPhone.value,
      address: newCustomerAddress.value,
    });
    emit('customerSelected', response.data);
    emit('update:show', false);
    resetForm();
  } catch (error) {
    console.error('Error adding customer:', error);
  }
};

const selectCustomer = (customer: any) => {
  emit('customerSelected', customer);
  emit('update:show', false);
  resetForm();
};

const resetForm = () => {
  searchQuery.value = '';
  customers.value = [];
  newCustomerName.value = '';
  newCustomerPhone.value = '';
  newCustomerAddress.value = '';
  showAddCustomerForm.value = false;
};

watch(() => props.show, (newVal) => {
  if (!newVal) {
    resetForm();
  }
});
</script>

<template>
  <Dialog :open="show" @update:open="emit('update:show', $event)">
    <DialogContent class="sm:max-w-[425px]">
      <DialogHeader>
        <DialogTitle>Pilih Customer</DialogTitle>
        <DialogDescription>
          Search for an existing customer or add a new one.
        </DialogDescription>
      </DialogHeader>

      <div class="grid gap-4 py-4">
        <div v-if="!showAddCustomerForm">
          <Input
            v-model="searchQuery"
            @input="searchCustomers"
            placeholder="Search customer by name or phone..."
            class="mb-2"
          />
          <div v-if="customers.length > 0" class="border rounded-md max-h-40 overflow-y-auto">
            <div
              v-for="customer in customers"
              :key="customer.id"
              @click="selectCustomer(customer)"
              class="p-2 cursor-pointer hover:bg-gray-100"
            >
              #{{ customer.id }} - {{ customer.name }} ({{ customer.phone }})
            </div>
          </div>
          <p v-else-if="searchQuery.length >= 2" class="text-sm text-gray-500 mt-2">No customers found.</p>
          <Button @click="showAddCustomerForm = true" class="mt-4 w-full bg-indigo-600 py-2 text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">Tambah Customer</Button>
        </div>

        <div v-else>
          <h4 class="text-md font-semibold mb-2">Buat Customer Baru</h4>
          <Input v-model="newCustomerName" placeholder="Name" class="mb-2" />
          <Input v-model="newCustomerPhone" placeholder="Phone" class="mb-2" />
          <Input v-model="newCustomerAddress" placeholder="Address (Optional)" class="mb-2" />
          <div class="flex justify-end gap-2 mt-4">
            <Button variant="outline" @click="showAddCustomerForm = false">Back to Search</Button>
            <Button @click="addCustomer">Save New Customer</Button>
          </div>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>