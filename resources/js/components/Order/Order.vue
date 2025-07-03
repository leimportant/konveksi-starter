<script setup lang="ts">
import { ref } from 'vue';
import OrderItem from '@/components/Order/OrderItem.vue';
import QRCodeDisplay from '@/components/QRCodeDisplay.vue';
import Modal from '@/components/Modal.vue';
import { QrCode } from 'lucide-vue-next';
import axios from 'axios';

const showQr = ref(false);

const props = defineProps({
  order: {
    type: Object,
    required: true,
  },
  isAdminView: {
    type: Boolean,
    default: false,
  },
});

const currentStatus = ref(props.order.status);
const availableStatuses = [
  'pending',
  'di kemas',
  'on progress',
  'done',
  'cancel',
];

const updateOrderStatus = async () => {
  try {
    await axios.put(`/api/orders/${props.order.id}/status`, {
      status: currentStatus.value,
    });
    alert('Order status updated successfully!');
  } catch (error) {
    console.error('Error updating order status:', error);
    alert('Failed to update order status.');
  }
};

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value);
};
</script>

<template>
  <div class="bg-white shadow-md rounded-lg p-6 mb-4">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold">Order #{{ order.id }}</h3>
      <span
        :class="{
          'bg-yellow-100 text-yellow-800': currentStatus === 'pending',
          'bg-blue-100 text-blue-800': currentStatus === 'di kemas' || currentStatus === 'on progress',
          'bg-green-100 text-green-800': currentStatus === 'done',
          'bg-red-100 text-red-800': currentStatus === 'cancel',
        }"
        class="px-3 py-1 rounded-full text-sm font-medium"
      >
        {{ currentStatus.toUpperCase() }}
      </span>
    </div>

    <div class="mb-4">
      <p class="text-gray-600">Total Pembayaran: {{ formatCurrency(order.total_amount) }}</p>
      <p class="text-gray-600">Payment Method: {{ order.payment_method }}</p>
      <p class="text-gray-600">Tanggal: {{ new Date(order.created_at).toLocaleDateString() }}</p>
    </div>

    <div class="mb-4">
      <h4 class="text-md font-semibold mb-2" @click="showQr = !showQr"><QrCode></QrCode></h4>
      <Modal :show="showQr" @close="showQr = false">
        <QRCodeDisplay :value="String(order.id)" :size="256" />
      </Modal>
    </div>

    <div class="mb-4">
      <h4 class="text-md font-semibold mb-2">Items:</h4>
      <div class="space-y-2">
        <OrderItem
          v-for="item in order.order_items"
          :key="item.id"
          :item="item"
        />
      </div>
    </div>

    <div v-if="isAdminView" class="mt-4 pt-4 border-t border-gray-200">
      <h4 class="text-md font-semibold mb-2">Update Status:</h4>
      <div class="flex items-center space-x-2">
        <select
          v-model="currentStatus"
          class="block w-full md:w-auto px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
        >
          <option v-for="status in availableStatuses" :key="status" :value="status">
            {{ status.toUpperCase() }}
          </option>
        </select>
        <button
          @click="updateOrderStatus"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Update
        </button>
      </div>
    </div>
  </div>
</template>