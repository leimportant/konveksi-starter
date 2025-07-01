<script setup lang="ts">
import Modal from '@/components/Modal.vue';
import OrderItem from '@/components/Order/OrderItem.vue';
import QRCodeDisplay from '@/components/QRCodeDisplay.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { useOrdersCustomer } from '@/stores/useOrderCustomer';
import { Head } from '@inertiajs/vue3';
import { MessageCircle, QrCode } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const showQr = ref(false);
const showMessageModal = ref(false);
const message = ref('');
const currentQrId = ref('');
const selectedOrders = ref<string[]>([]);
const { orders, isLoading, error, fetchOrders } = useOrdersCustomer();
const activeTab = ref<'pending' | 'done' | 'cancel'>('pending');

onMounted(() => {
    fetchOrders();
});

const filteredOrders = computed(() => {
    if (activeTab.value === 'done') return orders.value.filter((order) => order.status === 2);
    if (activeTab.value === 'cancel') return orders.value.filter((order) => order.status === 3);
    return orders.value.filter((order) => order.status === 1);
});

const totalProduk = computed(() => orders.value.reduce((sum, order) => sum + (order.order_items?.length || 0), 0));

const totalOrder = computed(() => orders.value.length);

const totalPembelian = computed(() => orders.value.reduce((sum, order) => sum + parseFloat(order.total_amount || '0'), 0));

const formattedTotalPembelian = computed(() =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(totalPembelian.value),
);

const openQrModal = (ids: string[]) => {
    currentQrId.value = ids.join(','); // Concatenate IDs for single QR code
    showQr.value = true;
};

const toggleSelectAll = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.checked) {
        selectedOrders.value = filteredOrders.value.filter((order) => order.payment_method === 'cod_store').map((order) => order.id);
    } else {
        selectedOrders.value = [];
    }
};

const openMessageModal = (ids: string[]) => {
    selectedOrders.value = ids;
    showMessageModal.value = true;
};

const toggleSelectOrder = (orderId: string) => {
    if (selectedOrders.value.includes(orderId)) {
        selectedOrders.value = selectedOrders.value.filter((id) => id !== orderId);
    } else {
        selectedOrders.value.push(orderId);
    }
};

const selectedCodStoreOrders = computed(() => {
    return selectedOrders.value.filter((orderId) => {
        const order = orders.value.find((o) => o.id === orderId);
        return order && order.payment_method === 'cod_store';
    });
});

const sendMessage = () => {
    console.log('Sending message:', message.value, 'for orders:', selectedOrders.value);
    // Implement actual message sending logic here
    showMessageModal.value = false;
    message.value = '';
};
</script>

<template>
    <Head title="Riwayat Order" />
    <AppLayout>
        <div class="container mx-auto px-4 py-6">
            <!-- Summary Cards -->
            <div class="mb-6 flex min-w-0 flex-wrap justify-between gap-2">
                <div class="w-full min-w-[100px] flex-1 flex-wrap rounded-lg bg-indigo-100 p-4 text-center shadow sm:w-[32%]">
                    <p class="text-xs font-medium text-indigo-800">Total Produk</p>
                    <p class="text-medium font-bold text-indigo-900">{{ totalProduk }}</p>
                </div>
                <div class="w-full min-w-[100px] flex-1 flex-wrap rounded-lg bg-green-100 p-4 text-center shadow sm:w-[32%]">
                    <p class="text-xs font-medium text-green-800">Total Order</p>
                    <p class="text-medium font-bold text-green-900">{{ totalOrder }}</p>
                </div>
                <div class="w-full min-w-[100px] flex-1 flex-wrap rounded-lg bg-yellow-100 p-4 text-center shadow sm:w-[32%]">
                    <p class="text-xs font-medium text-yellow-800">Total Pembelian</p>
                    <p class="text-medium font-bold text-yellow-900">{{ formattedTotalPembelian }}</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="mb-4 flex flex-wrap gap-2 border-b">
                <button
                    @click="activeTab = 'pending'"
                    :class="[
                        'rounded-t px-4 py-2 text-sm md:text-base',
                        activeTab === 'pending' ? 'border-b-2 border-indigo-600 font-semibold' : 'text-gray-500',
                    ]"
                >
                    Pesanan Saya
                </button>
                <button
                    @click="activeTab = 'done'"
                    :class="[
                        'rounded-t px-4 py-2 text-sm md:text-base',
                        activeTab === 'done' ? 'border-b-2 border-indigo-600 font-semibold' : 'text-gray-500',
                    ]"
                >
                    Selesai
                </button>
                <button
                    @click="activeTab = 'cancel'"
                    :class="[
                        'rounded-t px-4 py-2 text-sm md:text-base',
                        activeTab === 'cancel' ? 'border-b-2 border-indigo-600 font-semibold' : 'text-gray-500',
                    ]"
                >
                    Dibatalkan
                </button>
            </div>

            <!-- Table -->
            <div v-if="isLoading" class="text-center text-gray-500">Memuat pesanan...</div>
            <div v-else-if="error" class="text-center text-red-500">{{ error }}</div>
            <div v-else-if="filteredOrders.length === 0" class="text-center text-gray-500">Tidak ada pesanan ditemukan.</div>
            <div v-else class="overflow-x-auto">
                <!-- Tombol-tombol dalam satu baris -->
                <div v-if="activeTab === 'pending' && selectedCodStoreOrders.length > 0" class="mb-4 flex items-center gap-3">
                    <Button
                        @click="openQrModal(selectedCodStoreOrders)"
                        class="flex items-center gap-1 rounded-md border border-indigo-600 px-3 py-1 text-indigo-600 hover:underline"
                    >
                        <QrCode class="h-4 w-4" /> Generate QR
                    </Button>

                    <Button
                        @click="openMessageModal(selectedCodStoreOrders)"
                        class="flex items-center gap-1 rounded-md border border-indigo-600 px-3 py-1 text-indigo-600 hover:underline"
                    >
                        <MessageCircle class="h-4 w-4" /> Kirim Pesan
                    </Button>
                </div>

                <Table class="min-w-full">
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[10%]">
                                <div class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        :checked="selectedOrders.length === filteredOrders.length && filteredOrders.length > 0"
                                        @change="toggleSelectAll"
                                    />
                                </div>
                            </TableHead>
                            <TableHead class="w-[10%]">Tanggal</TableHead>
                            <TableHead class="w-[10%]">Total</TableHead>
                            <TableHead class="w-[10%]">Status</TableHead>
                            <TableHead class="w-[60%]">
                                <div class="flex items-center justify-between">
                                    <span>Detail</span>
                                </div>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="order in filteredOrders" :key="order.id">
                            <TableCell>
                                <div class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        :checked="selectedOrders.includes(order.id)"
                                        @change="toggleSelectOrder(order.id)"
                                        :disabled="order.payment_method !== 'cod_store'"
                                    />
                                </div>
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
                                    <h4 class="mb-1 text-sm font-semibold">Item:</h4>
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

        <!-- Modal Kirim Pesan -->
        <Modal :show="showMessageModal" @close="showMessageModal = false">
            <div class="space-y-4 p-4 sm:p-6">
                <h2 class="text-center text-base font-semibold text-gray-800 sm:text-lg">Kirim Pesan</h2>

                <Textarea
                    v-model="message"
                    class="h-32 w-full resize-none rounded-md border border-gray-300 p-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:h-40"
                    placeholder="Tulis pesan..."
                />

                <div class="flex justify-end gap-2 pt-2">
                    <Button @click="showMessageModal = false" variant="outline" class="px-4 py-2 text-sm"> Batal </Button>
                    <Button @click="sendMessage" class="bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"> Kirim </Button>
                </div>
            </div>
        </Modal>

        <!-- Modal QR Code -->
        <Modal :show="showQr" @close="showQr = false">
            <div class="flex justify-center p-4">
                <QRCodeDisplay :value="currentQrId" :size="256" />
            </div>
        </Modal>
    </AppLayout>
</template>
