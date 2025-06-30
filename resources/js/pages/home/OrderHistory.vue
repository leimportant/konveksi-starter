<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { ref, onMounted, computed } from 'vue';
import OrderItem from '@/components/Order/OrderItem.vue';
import QRCodeDisplay from '@/components/QRCodeDisplay.vue';
import Modal from '@/components/Modal.vue';
import { QrCode } from 'lucide-vue-next';
import { useOrdersCustomer } from '@/stores/useOrderCustomer';

const showQr = ref(false);
const currentQrId = ref('');
const { orders, isLoading, error, fetchOrders } = useOrdersCustomer();
const activeTab = ref<'pending' | 'done' | 'cancel'>('pending');

onMounted(() => {
  fetchOrders();
});

const filteredOrders = computed(() => {
  if (activeTab.value === 'done') return orders.value.filter(order => order.status === 2);
  if (activeTab.value === 'cancel') return orders.value.filter(order => order.status === 3);
  return orders.value.filter(order => order.status === 1);
});

const totalProduk = computed(() =>
  orders.value.reduce((sum, order) => sum + (order.order_items?.length || 0), 0)
);

const totalOrder = computed(() => orders.value.length);

const totalPembelian = computed(() =>
  orders.value.reduce((sum, order) => sum + parseFloat(order.total_amount || '0'), 0)
);

const formattedTotalPembelian = computed(() =>
  new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(totalPembelian.value)
);

const openQrModal = (id: string) => {
  currentQrId.value = id;
  showQr.value = true;
};
</script>

<template>
  <Head title="Riwayat Order" />
  <AppLayout>
    <div class="container mx-auto px-4 py-6">
     <!-- Summary Cards -->
<div class="flex flex-wrap justify-between gap-2 mb-6">
        <div class="bg-indigo-100 shadow rounded-lg p-4 text-center w-[32%] min-w-[100px]">
          <p class="text-indigo-800 text-xs font-medium">Total Produk</p>
          <p class="text-2xl font-bold text-indigo-900">{{ totalProduk }}</p>
        </div>
        <div class="bg-green-100 shadow rounded-lg p-4 text-center w-[30%] min-w-[100px]">
          <p class="text-green-800 text-xs font-medium">Total Order</p>
          <p class="text-2xl font-bold text-green-900">{{ totalOrder }}</p>
        </div>
        <div class="bg-yellow-100 shadow rounded-lg p-4 text-center w-[32%] min-w-[100px]">
          <p class="text-yellow-800 text-xs font-medium">Total Pembelian</p>
          <p class="text-2xl font-bold text-yellow-900">{{ formattedTotalPembelian }}</p>
        </div>
      </div>


      <!-- Tabs -->
      <div class="mb-4 flex flex-wrap gap-2 border-b">
        <button
          @click="activeTab = 'pending'"
          :class="[
            'px-4 py-2 rounded-t text-sm md:text-base',
            activeTab === 'pending' ? 'border-b-2 border-indigo-600 font-semibold' : 'text-gray-500'
          ]"
        >
          Pesanan
        </button>
        <button
          @click="activeTab = 'done'"
          :class="[
            'px-4 py-2 rounded-t text-sm md:text-base',
            activeTab === 'done' ? 'border-b-2 border-indigo-600 font-semibold' : 'text-gray-500'
          ]"
        >
          Selesai
        </button>
        <button
          @click="activeTab = 'cancel'"
          :class="[
            'px-4 py-2 rounded-t text-sm md:text-base',
            activeTab === 'cancel' ? 'border-b-2 border-indigo-600 font-semibold' : 'text-gray-500'
          ]"
        >
          Dibatalkan
        </button>
      </div>

      <!-- Table -->
      <div v-if="isLoading" class="text-center text-gray-500">Memuat pesanan...</div>
      <div v-else-if="error" class="text-center text-red-500">{{ error }}</div>
      <div v-else-if="filteredOrders.length === 0" class="text-center text-gray-500">
        Tidak ada pesanan ditemukan.
      </div>
      <div v-else class="overflow-x-auto">
        <Table class="min-w-full">
          <TableHeader>
            <TableRow>
              <TableHead class="w-[10%]">ID Order</TableHead>
              <TableHead class="w-[10%]">Tanggal</TableHead>
              <TableHead class="w-[10%]">Total</TableHead>
              <TableHead class="w-[10%]">Status</TableHead>
              <TableHead class="w-[60%]">Detail</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="order in filteredOrders" :key="order.id">
              <TableCell>
                <button @click="openQrModal(order.id)" class="flex items-center gap-1 text-indigo-600 hover:underline">
                  <QrCode class="w-4 h-4" /> {{ order.id.slice(0, 8) }}...
                </button>
              </TableCell>
              <TableCell>{{ new Date(order.created_at).toLocaleDateString('id-ID') }}</TableCell>
              <TableCell>
                {{
                  new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                  }).format(Number(order.total_amount))
                }}
              </TableCell>
              <TableCell>
                <span
                  :class="{
                    'text-yellow-600': order.status === 1,
                    'text-green-600': order.status === 2,
                    'text-red-600': order.status === 3,
                  }"
                >
                  {{ order.status === 1 ? 'Pending' : order.status === 2 ? 'Selesai' : 'Dibatalkan' }}
                </span>
              </TableCell>
              <TableCell>
                <div>
                  <h4 class="text-sm font-semibold mb-1">Item:</h4>
                  <div class="space-y-1">
                    <OrderItem v-for="item in order.order_items" :key="item.id" :item="item" />
                  </div>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>

    <!-- Modal QR Code -->
    <Modal :show="showQr" @close="showQr = false">
      <div class="p-4 flex justify-center">
        <QRCodeDisplay :value="currentQrId" :size="256" />
      </div>
    </Modal>
  </AppLayout>
</template>
