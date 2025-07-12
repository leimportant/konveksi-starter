<script setup lang="ts">
import Modal from '@/components/Modal.vue';
import OrderItem from '@/components/Order/OrderItem.vue';
import QRCodeDisplay from '@/components/QRCodeDisplay.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { DateInput } from '@/components/ui/date-input';
import { useOrdersCustomer } from '@/stores/useOrderCustomer';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, watch, ref } from 'vue';
import axios from 'axios';
// import { useOrderCustomer } from '../../composables/useOrderCustomer'; // Remove this line

import debounce from 'lodash-es/debounce';

const showQr = ref(false);
const showShipping = ref(false);
const showMessageModal = ref(false);
const message = ref('');
const currentQrId = ref('');
const selectedOrders = ref<string[]>([]);
const receiverId = ref<number | null>(null);
const filterName = ref('');

const { orders, isLoading, error, pagination, fetchOrderRequest, cancelOrder, deleteOrder, checkShipping, updateShipping, setFilterOrderRequest } = useOrdersCustomer();

const activeTab = ref<'cart' | 'pending' | 'done' | 'cancel' | 'storecart'>('cart');


const shippingInfo = ref<any>(null);
// Tambahan untuk update pengiriman
const resi_number = ref('');
const delivery_provider = ref('');
const estimation_date = ref('');
const deliveryProofFile = ref<File | null>(null);
const shippingOrderId = ref<string | null>(null);



const scrollPage = ref(1);
const perPage = ref(10);

const totalPages = computed(() => Math.ceil((pagination.value?.total || 0) / (pagination.value?.per_page || 1)));

const handleScroll = () => {
    const bottomOfWindow = document.documentElement.scrollTop + window.innerHeight >= document.documentElement.offsetHeight - 100; // 100px from bottom
    if (bottomOfWindow && !isLoading.value && scrollPage.value < totalPages.value) {
        scrollPage.value++;
        fetchOrderRequest({ status: activeTab.value, page: scrollPage.value, per_page: perPage.value, append: true });
    }
};



const {  fetchBankAccount } = useOrdersCustomer();


const toast = useToast();
onMounted(() => {
    scrollPage.value = 1;
    fetchBankAccount();
    fetchOrderRequest({ status: activeTab.value, page: scrollPage.value, per_page: perPage.value });

    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});

watch(activeTab, (newTab) => {
    scrollPage.value = 1;
    fetchOrderRequest({ status: newTab, page: scrollPage.value, per_page: perPage.value });
});


const debouncedSetFilter = debounce((field: string, value: string) => {
    orders.value = []; // Clear orders when filter changes
    scrollPage.value = 1;
    setFilterOrderRequest(field, value, { status: activeTab.value, page: scrollPage.value, per_page: perPage.value, append: false });
}, 400);

const handleFilter = (e: Event) => {
    const target = e.target as HTMLInputElement;
    debouncedSetFilter('name', target.value);
};

    const filteredOrders = computed(() => {
        return orders.value;
    });

const openMessageModal = (ids: string[], targetReceiverId: number) => {
    receiverId.value = targetReceiverId;
    selectedOrders.value = ids;
    showMessageModal.value = true;
};
const openDropdown = ref();

function toggleDropdown(orderId: string) {
    openDropdown.value = openDropdown.value === orderId ? null : orderId;
}

const handleCheckShipping = async (orderId: string) => {
    try {
        const data = await checkShipping(orderId);
        shippingInfo.value = data;
        showShipping.value = true;
    } catch (err) {
        console.log(err);
        alert('Gagal mengambil data pengiriman.');
    }
};

async function handleCancel(orderId: string) {

    try {
        await cancelOrder(orderId);
        // Tampilkan toast sukses
        toast.success('Pesanan berhasil dibatalkan.');

        // Tunggu sedikit sebelum refresh agar toast terlihat
        setTimeout(() => {
                scrollPage.value = 1;
                fetchOrderRequest({ status: activeTab.value, page: scrollPage.value, per_page: perPage.value });
            }, 500); // 0.5 detik
    } catch (err) {
        console.log(err);
        toast.error('Gagal membatalkan pesanan.');
    }
}

async function handleDelete(orderId: string) {
    try {
        await deleteOrder(orderId);
        // Tampilkan toast sukses
        toast.success('Pesanan berhasil dibatalkan.');

        // Tunggu sedikit sebelum refresh agar toast terlihat
        setTimeout(() => {
                scrollPage.value = 1;
                fetchOrderRequest({ status: activeTab.value, page: scrollPage.value, per_page: perPage.value });
            }, 500); // 0.5 detik
    } catch (err) {
        console.log(err);
        toast.error('Gagal membatalkan pesanan.');
    }
}

const sendMessage = async () => {
    try {
        const payload = {
            message: message.value,
            content_data: selectedOrders.value.length > 0 ? selectedOrders.value : null,
            sender_type: 'admin',
            receiver_id: receiverId.value,
            order_id: selectedOrders.value.length > 0 ? selectedOrders.value[0] : null,
        };
        console.log('Sending message payload:', payload);
        await axios.post('/api/chat/send', payload);
        showMessageModal.value = false;
        message.value = '';
        toast.success('Pesan berhasil dikirim');
    } catch (error) {
        console.error('Failed to send message:', error);
        toast.error('Gagal mengirim pesan');
    }
};


function handleFileUpload(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files?.length) {
        deliveryProofFile.value = target.files[0];
    }
}

const handleUpdateShipping = async (orderId: string) => {
    showShipping.value = true;
    shippingOrderId.value = orderId;
};

async function submitShipping() {
    const formData = new FormData();
    if (!shippingOrderId.value) {
        toast.error('Order ID tidak tersedia');
        return;
    }

    formData.append('resi_number', resi_number.value);
    formData.append('delivery_provider', delivery_provider.value);
    formData.append('estimation_date', estimation_date.value);

    if (deliveryProofFile.value) {
        formData.append('delivery_proof', deliveryProofFile.value);
    }

    try {
        const response = await updateShipping(shippingOrderId.value, formData); // pastikan orderId tersedia

        toast.success(response?.message || 'Pengiriman berhasil diperbarui');
        showShipping.value = false;

    } catch (error: any) {
        console.error('Gagal memperbarui pengiriman:', error);
        const message = error?.response?.data?.message || 'Terjadi kesalahan saat memperbarui pengiriman';
        toast.error(message);
    }
}

</script>

<!-- File: OrderRequest.vue -->
<template>

    <Head title="Riwayat Order" />
    <AppLayout>
         <div class="px-4 py-4">
        <section class="px-2 py-2 sm:px-4 sm:py-4 bg-white min-h-screen overflow-x-auto">
            <Input
            :value="filterName"
            placeholder="Search"
            @input="handleFilter"
            class="w-64"
            aria-label="Search"
            />
            <!-- Tabs -->
            <div class="mb-4 flex flex-wrap gap-2 border-b">
                <button @click="activeTab = 'cart'" :class="[
                    'rounded-t px-4 py-2 text-sm md:',
                    activeTab === 'cart' ? 'border-b-2 text-indigo-400 border-indigo-600 font-semibold' : 'text-gray-500',
                ]">
                    Keranjang Konsumen
                </button>
                <button @click="activeTab = 'pending'" :class="[
                    'rounded-t px-4 py-2 text-sm md:',
                    activeTab === 'pending' ? 'border-b-2 text-indigo-400 border-indigo-600 font-semibold' : 'text-gray-500',
                ]">
                    Pesanan Active
                </button>
                <button @click="activeTab = 'done'" :class="[
                    'rounded-t px-4 py-2 text-sm md:',
                    activeTab === 'done' ? 'border-b-2  text-indigo-400 border-indigo-600 font-semibold' : 'text-gray-500',
                ]">
                    Selesai
                </button>
                <button @click="activeTab = 'cancel'" :class="[
                    'rounded-t px-4 py-2 text-sm md:',
                    activeTab === 'cancel' ? 'border-b-2  text-indigo-400 border-indigo-600 font-semibold' : 'text-gray-500',
                ]">
                    Dibatalkan
                </button>
                 <button @click="activeTab = 'storecart'" :class="[
                    'rounded-t px-4 py-2 text-sm md:',
                    activeTab === 'storecart' ? 'border-b-2 text-indigo-400 border-indigo-600 font-semibold' : 'text-gray-500',
                ]">
                    Penjualan Toko
                </button>
            </div>

            <!-- Table Content -->
            <div v-if="isLoading" class="text-center text-gray-500 text-sm py-6">Memuat pesanan...</div>
            <div v-else-if="error" class="text-center text-red-500 text-sm py-6">{{ error }}</div>
            <div v-else-if="filteredOrders.length === 0" class="text-center text-gray-500 text-sm py-6">Tidak ada
                pesanan ditemukan.</div>
            <div v-else class="overflow-x-auto border rounded">
                <Table class="max-h-screen w-full">
                    <TableHeader>
                        <TableRow class="bg-gray-100">
                            <TableHead class="w-[5%]">#</TableHead>
                            <TableHead class="w-[10%]">No. Order</TableHead>
                            <TableHead class="w-[10%]">Customer</TableHead>
                            <TableHead class="w-[10%]">Tanggal</TableHead>
                            <TableHead class="w-[10%]">Total</TableHead>
                            <TableHead class="w-[10%]">Status</TableHead>
                            <TableHead class="w-[10%] text-center">Pembayaran</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <template v-for="order in filteredOrders" :key="order.id">
                            <TableRow class="text-xs sm:text-sm">
                                <TableCell>
                                    <div class="relative bg-white dark:bg-gray-800">
                                        <Button @click="toggleDropdown(order.id)" size="icon" variant="ghost">⋮</Button>
                                        <div v-if="openDropdown === order.id"
                                            class="absolute left-0 top-full z-50 mt-2 w-44 rounded-md bg-white dark:bg-gray-800 text-black dark:text-white shadow-lg ring-1 ring-black ring-opacity-5">
                                            <div class="py-1 text-left text-sm">
                                                <Button variant="ghost" size="icon"
                                                    @click="openMessageModal([order.id], order.customer_id)"
                                                    class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                    Kirim Pesan
                                                </Button>
                                                <Button variant="ghost" size="icon" v-if="order.is_paid == 'Y'"
                                                    @click="handleCancel(order.id)"
                                                    class="w-full text-left px-4 py-2 text-red-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                    Batalkan Pesanan
                                                </Button>
                                                <Button variant="ghost" size="icon" v-if="activeTab === 'cart'"
                                                    @click="handleDelete(order.id)"
                                                    class="w-full text-left px-4 py-2 text-red-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                    Delete Pesanan
                                                </Button>
                                                <Button variant="ghost" size="icon"
                                                    v-if="order.resi_number == '' && order.is_paid == 'Y' && order.payment_method === 'bank_transfer'"
                                                    @click="handleUpdateShipping(order.id)"
                                                    class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                    Update Resi
                                                </Button>
                                                <Button variant="ghost" size="icon"
                                                    v-if="order.resi_number == '' && order.is_paid == 'Y' && order.payment_method === 'bank_transfer'"
                                                    @click="handleCheckShipping(order.id)"
                                                    class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                    Status Pengiriman
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </TableCell>


                                <TableCell>{{ order.id }}</TableCell>

                                <TableCell>{{ order.customer?.name || order.customer_id + ' - N/A' }}</TableCell>

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
                                    <span :class="{
                                        'text-yellow-600': order.status === 1,
                                        'text-green-600': order.status === 2,
                                        'text-red-600': order.status === 3,
                                    }">
                                         {{ order.status === 1 ? 'Pending' : order.status === 2 ? 'Selesai' : order.status === 3 ? 'Menunggu Konfirmasi' : order.status === 4 ? 'On Progress' : order.status === 5 ? 'Sedang di kemas' :
                                        'Dikirim' }}
                                    </span>
                                </TableCell>

                                <TableCell class="text-center">
                                    <span :class="[
                                        'inline-block rounded-full border px-2 py-0.5 text-xs',
                                        order.payment_method === 'bank_transfer'
                                            ? 'border-yellow-600 text-yellow-600'
                                            : order.payment_method === 'cod_store'
                                                ? 'border-green-600 text-green-600'
                                                : 'border-gray-300 text-gray-500',
                                    ]">
                                        {{
                                            order.payment_method === 'bank_transfer'
                                                ? 'Transfer'
                                                : order.payment_method === 'cod_store'
                                                    ? 'Bayar Di Toko'
                                                    : '-'
                                        }}
                                    </span>
                                </TableCell>
                            </TableRow>


                            <!-- Detail Order -->
                            <TableRow>
                                <TableCell colspan="7" class="bg-blue-50 p-3 text-xs sm:text-sm">
                                    <h4 class="mb-1 font-medium">Detail Pesanan:</h4>
                                    <div class="space-y-1">
                                        <OrderItem v-for="item in order.order_items" :key="item.id" :item="item" />
                                    </div>
                                    <div class="mt-1 text-right font-semibold">
                                        Subtotal:
                                        {{
                                            new Intl.NumberFormat('id-ID', {
                                                style: 'currency',
                                                currency: 'IDR',
                                            }).format(Number(order.total_amount))
                                        }}
                                    </div>
                                </TableCell>
                            </TableRow>
                        </template>
                    </TableBody>
                </Table>
            </div>


        </section>
        </div>
        <!-- Modals -->
        <Modal :show="showMessageModal" @close="showMessageModal = false">
            <div class="space-y-4 p-4 sm:p-6">
                <h2 class="text-center font-semibold text-gray-800 sm:text-lg">Kirim Pesan</h2>
                <Textarea v-model="message"
                    class="h-32 w-full resize-none rounded-md border border-gray-300 p-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    placeholder="Tulis pesan..." />
                <div class="flex justify-end gap-2 pt-2">
                    <Button @click="showMessageModal = false" variant="outline">Batal</Button>
                    <Button @click="sendMessage" class="bg-indigo-600 text-white">Kirim</Button>
                </div>
            </div>
        </Modal>

        <Modal :show="showQr" @close="showQr = false">
            <div class="flex justify-center p-4">
                <QRCodeDisplay :value="currentQrId" :size="256" />
            </div>
        </Modal>

        <Modal :show="showShipping" @close="showShipping = false">
            <div class="space-y-3 p-4 text-sm">
                <h3 class="font-semibold">Update Pengiriman</h3>
                <div>
                    <label>No Order:</label>
                    <Input v-model="shippingOrderId" readonly />
                </div>
                <div>
                    <label>No Resi:</label>
                    <Input v-model="resi_number" placeholder="Masukkan nomor resi" />
                </div>
                <div>
                    <label>Jasa Pengirim:</label>
                    <Input v-model="delivery_provider" placeholder="JNE/J&T/Sicepat dll" />
                </div>
                <div>
                    <label>Estimasi Tiba:</label>
                    <DateInput id="estimation_date" v-model="estimation_date" />
                </div>
                <div>
                    <label>Bukti Pengiriman (opsional):</label>
                    <input type="file" @change="handleFileUpload" />
                </div>
                <div class="flex justify-end gap-2">
                    <Button @click="showShipping = false" variant="outline">Batal</Button>
                    <Button @click="submitShipping" class="bg-blue-600 text-white">Simpan</Button>
                </div>
            </div>
        </Modal>

 
    </AppLayout>
</template>
