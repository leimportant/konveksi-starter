<script setup lang="ts">
import Modal from '@/components/Modal.vue';
import OrderItem from '@/components/Order/OrderItem.vue';
import QRCodeDisplay from '@/components/QRCodeDisplay.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { useOrdersCustomer } from '@/stores/useOrderCustomer';
import { useBankAccountStore } from '@/stores/useBankAccountStore';
import { Head } from '@inertiajs/vue3';
import { QrCode } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import axios from 'axios';

const showQr = ref(false);
const showShipping = ref(false);
const showMessageModal = ref(false);
const message = ref('');
const currentQrId = ref('');
const selectedOrders = ref<string[]>([]);
const { orders, isLoading, error, pagination, fetchOrders } = useOrdersCustomer();
const activeTab = ref<'pending' | 'done' | 'cancel'>('pending');


const { cancelOrder, checkShipping } = useOrdersCustomer();

const bankAccount = useBankAccountStore();
const shippingInfo = ref<any>(null);

const toast = useToast();
const scrollPage = ref(1);

const totalPages = computed(() => Math.ceil((pagination.value?.total || 0) / (pagination.value?.per_page || 1)));

onMounted(() => {
    orders.value = []; // Clear orders on mount
    scrollPage.value = 1;
    fetchOrders({ status: activeTab.value, page: scrollPage.value });
    bankAccount.fetchBankAccount();
    fetchDashboardData();
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});

watch(activeTab, (status) => {
    orders.value = []; // Clear orders when tab changes
    scrollPage.value = 1;
    fetchOrders({ status, page: scrollPage.value });
});


const filteredOrders = computed(() => orders.value);




const handleScroll = () => {
    const bottomOfWindow = document.documentElement.scrollTop + window.innerHeight >= document.documentElement.offsetHeight - 100; // 100px from bottom
    if (bottomOfWindow && !isLoading.value && scrollPage.value < totalPages.value) {
        scrollPage.value++;
        fetchOrders({ status: activeTab.value, page: scrollPage.value, append: true });
    }
};

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

const openDropdown = ref();

function toggleDropdown(orderId: string) {
    openDropdown.value = openDropdown.value === orderId ? null : orderId;
}


const handleCheckShipping = async (orderId: string) => {
    try {
        const data = await checkShipping(orderId);
        showShipping.value = true;
        shippingInfo.value = data.data;
       
    } catch (err) {
        console.log(err);
        alert('Gagal mengambil data pengiriman.');
    }
};

const getImageUrl = (path: string) => {
  if (!path) return '';
  if (path.startsWith('storage/')) return '/' + path;
  if (path.startsWith('/storage/')) return path;
  return '/storage/' + path;
};


async function handleCancel(orderId: string) {
    const confirmed = window.confirm('Apakah Anda yakin ingin membatalkan pesanan ini?');
    if (!confirmed) return;

    try {
        await cancelOrder(orderId);

        // Tampilkan toast sukses
        toast.success('Pesanan berhasil dibatalkan.');

        // Tunggu sedikit sebelum refresh agar toast terlihat
        setTimeout(() => {
            orders.value = [];
            scrollPage.value = 1;
            fetchOrders({ status: activeTab.value, page: scrollPage.value });
        }, 500); // 0.5 detik
    } catch (err) {
        console.log(err);
        toast.error('Gagal membatalkan pesanan.');
    }
}

const selectedCodStoreOrders = computed(() => {
    return selectedOrders.value.filter((orderId) => {
        const order = orders.value.find((o) => o.id === orderId);
        return order && order.payment_method === 'cod_store';
    });
});



const sendMessage = async () => {
    try {
        const payload = {
            message: message.value,
            content_data: selectedOrders.value.length > 0 ? selectedOrders.value : null,
            sender_type: 'customer',
            order_id: selectedOrders.value.length > 0 ? selectedOrders.value[0] : null
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


const showUploadTransfer = ref(false);
const paymentProofFile = ref<File | null>(null);

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        paymentProofFile.value = target.files[0];
    } else {
        paymentProofFile.value = null;
    }
};

const totalProduk = ref(0);
const totalOrder = ref(0);
const totalPembelian = ref(0);

const fetchDashboardData = async () => {
  try {
    const response = await axios.get('/api/dashboard/customer');
    totalProduk.value = response.data.totalProduk || 0;
    totalOrder.value = response.data.totalOrder || 0;
    totalPembelian.value = response.data.totalPembelian || 0;
  } catch (error) {
    console.error('Failed to fetch dashboard data:', error);
  }
};


const { uploadPaymentProof } = useOrdersCustomer();

const orderIdForUpload = ref<string | null>(null);

const handleUploadTransfer = (orderId: string) => {
    orderIdForUpload.value = orderId;
    showUploadTransfer.value = true;
};

const submitTransfer = async () => {
    if (!paymentProofFile.value) {
        toast.error('Silahkan pilih file bukti transfer terlebih dahulu.');
        return;
    }

    const orderId = orderIdForUpload.value;
    if (!orderId) {
        toast.error('Tidak ada order yang dipilih untuk upload bukti transfer.');
        return;
    }

    try {
        await uploadPaymentProof(orderId, paymentProofFile.value); // ✅ FIXED
        toast.success('Bukti transfer berhasil diunggah!');
        showUploadTransfer.value = false;
        fetchOrders({ status: activeTab.value });
    } catch (error) {
        console.error(error);
        toast.error('Gagal mengunggah bukti transfer.');
    }
};



</script>

<template>

    <Head title="Riwayat Order" />
    <AppLayout>
        <section class="absolute max-h-screen w-full bg-gray-50 p-4 md:p-2">
            <!-- Summary Cards -->
            <div class="mb-6 flex flex-wrap justify-between gap-2">
                <div
                    class="w-full flex-1 flex-col flex-wrap rounded-lg bg-indigo-100 p-4 text-center shadow sm:w-[32%] sm:w-[32.999%]">
                    <p class="text-xs font-medium text-indigo-800">Total Produk</p>
                    <p class="text-medium font-bold text-indigo-900">{{ totalProduk }}</p>
                </div>
                <div
                    class="w-full flex-1 flex-col flex-wrap rounded-lg bg-green-100 p-4 text-center shadow sm:w-[32%] sm:w-[32.999%]">
                    <p class="text-xs font-medium text-green-800">Total Order</p>
                    <p class="text-medium font-bold text-green-900">{{ totalOrder }}</p>
                </div>
                <div
                    class="w-full flex-1 flex-col flex-wrap rounded-lg bg-yellow-100 p-4 text-center shadow sm:w-[32%] sm:w-[32.999%]">
                    <p class="text-xs font-medium text-yellow-800">Total Pembelian</p>
                    <p class="text-small font-bold text-yellow-900">{{ totalPembelian }}</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="mb-4 flex flex-wrap gap-2 border-b">
                <button @click="activeTab = 'pending'" :class="[
                    'rounded-t px-4 py-2 text-sm md:',
                    activeTab === 'pending' ? 'border-b-2 text-indigo-400 border-indigo-600 font-semibold' : 'text-gray-500',
                ]">
                    Pesanan Saya
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
            </div>

            <!-- Table -->
            <div v-if="isLoading" class="text-center text-gray-500">Memuat pesanan...</div>
            <div v-else-if="error" class="text-center text-red-500">{{ error }}</div>
            <div v-else-if="filteredOrders.length === 0" class="text-center text-gray-500">Tidak ada pesanan ditemukan.
            </div>
            <div v-else class="overflow-x-auto">
                <!-- Tombol-tombol dalam satu baris -->
                <div v-if="activeTab === 'pending' && selectedCodStoreOrders.length > 0"
                    class="mb-4 flex items-center gap-3">
                    <Button @click="openQrModal(selectedCodStoreOrders)"
                        class="flex items-center gap-1 rounded-md border border-indigo-600 px-3 py-1 text-indigo-600 hover:underline">
                        <QrCode class="h-4 w-4" /> Generate QR
                    </Button>

                    <!-- <Button @click="openMessageModal(selectedCodStoreOrders)"
                        class="flex items-center gap-1 rounded-md border border-indigo-600 px-3 py-1 text-indigo-600 hover:underline">
                        <MessageCircle class="h-4 w-4" /> Kirim Pesan
                    </Button> -->
                </div>

                <Table class="max-h-screen w-full">
                    <TableHeader>
                        <TableRow class="bg-gray-100">
                            <TableHead class="w-[5%]">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox"
                                        :checked="selectedOrders.length === filteredOrders.length && filteredOrders.length > 0"
                                        @change="toggleSelectAll" />
                                </div>
                            </TableHead>
                            <TableHead class="w-[10%]">No. Order</TableHead>
                            <TableHead class="w-[10%]">Tanggal</TableHead>
                            <TableHead class="w-[10%]">Total</TableHead>
                            <TableHead class="w-[10%]">Status</TableHead>
                            <TableHead class="w-[10%]">Pembayaran</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-for="order in filteredOrders" :key="order.id">
                            <!-- Row 1: utama -->

                            <TableRow>
                                <TableCell>
                                    <div class="flex items-center justify-between gap-2">
                                        <!-- Checkbox -->
                                        <input type="checkbox" :checked="selectedOrders.includes(order.id)"
                                            @change="toggleSelectOrder(order.id)"
                                            :disabled="order.payment_method !== 'cod_store'" />

                                        <!-- Dropdown Menu -->
                                        <div v-if="activeTab == 'pending'"
                                            class="relative inline-block border border-blue-200 text-gray-400 bg-blue-100 text-left">
                                            <button @click="toggleDropdown(order.id)"
                                                class="rounded bg-gray-100 px-2 py-1 text-sm hover:bg-gray-200">
                                                ⋮
                                            </button>
                                            <div v-if="openDropdown === order.id"
                                                class="absolute z-10 mt-2 w-48 rounded-md bg-white text-gray-800 shadow-lg ring-1 ring-black ring-opacity-5">
                                                <div class="py-1 text-sm text-gray-700">
                                                    <Button @click="openMessageModal([order.id])"
                                                        class="block w-full px-4 py-2 hover:bg-gray-100">
                                                        Kirim Pesan
                                                    </Button>
                                                    <Button @click="handleCancel(order.id)"
                                                        class="block w-full px-4 py-2 text-red-500 hover:bg-gray-100">
                                                        Ajukan Pembatalan
                                                    </Button>

                                                    <Button @click="handleCheckShipping(order.id)"
                                                        v-if="order.is_paid == 'Y'"
                                                        class="block w-full px-4 py-2 hover:bg-gray-100">
                                                        Status Pengiriman
                                                    </Button>

                                                    <Button @click="handleUploadTransfer(order.id)"
                                                        v-if="order.is_paid == 'N' && order.payment_method == 'bank_transfer'"
                                                        class="block w-full px-4 py-2 hover:bg-gray-100">
                                                        Upload Bukti Transfer
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell class="text-gray-800 text-sm leading-snug space-y-0.5">
                                    {{ order.id }}
                                </TableCell>
                                <TableCell class="text-gray-800 text-sm leading-snug space-y-0.5">

                                    <div class="text-gray-600 text-xs">{{ new
                                        Date(order.created_at).toLocaleDateString('id-ID') }}</div>
                                </TableCell>

                                <TableCell class="text-gray-800">

                                    {{
                                        new Intl.NumberFormat('id-ID', {
                                            style: 'currency',
                                            currency: 'IDR',
                                        }).format(Number(order.total_amount))
                                    }}
                                </TableCell>
                                <TableCell class="text-gray-800 text-sm space-y-1">

                                    <span class="text-xs font-semibold" :class="{
                                        'text-yellow-600': order.status === 1,
                                        'text-green-600': order.status === 2,
                                        'text-red-600': order.status === 3,
                                    }">
                                        {{ order.status === 1 ? 'Pending' : order.status === 2 ? 'Selesai' : order.status === 3 ? 'Menunggu Konfirmasi' : order.status === 4 ? 'On Progress' : order.status === 5 ? 'Sedang di kemas' :
                                        'Dikirim' }}
                                    </span>
                                </TableCell>


                                <TableCell class="text-gray-800 text-center text-xxs">
                                    <span :class="[
                                        'text-xxs font-small inline-block rounded-full border px-1 py-1',
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

                            <!-- Row 2: detail -->
                            <TableRow>
                                <TableCell colspan="6" class="rounded-md border border-blue-100 bg-blue-50 p-3">
                                    <h4 class="mb-1 text-sm font-semibold">Detail Pesanan:</h4>
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

        <!-- Modal Kirim Pesan -->
        <Modal :show="showMessageModal" @close="showMessageModal = false">
            <div class="space-y-4 p-4 sm:p-6">
                <h2 class="text-center font-semibold text-gray-800 sm:text-lg">Kirim Pesan</h2>

                <Textarea v-model="message"
                    class="h-32 w-full bg-white resize-none rounded-md border border-gray-300 p-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:h-40"
                    placeholder="Tulis pesan..." />

                <div class="flex justify-end gap-2 pt-2">
                    <Button @click="showMessageModal = false" variant="outline" class="px-4 py-2 text-sm"> Batal
                    </Button>
                    <Button @click="sendMessage" class="bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700">
                        Kirim
                    </Button>
                </div>
            </div>
        </Modal>

        <!-- Modal QR Code -->
        <Modal :show="showQr" @close="showQr = false">
            <div class="flex justify-center p-4">
                <QRCodeDisplay :value="currentQrId" :size="256" />
            </div>
        </Modal>

        <!-- Modal Check Shipping -->

         <Modal :show="showShipping" @close="showShipping = false"  class="max-w-lg">
                <h2 class="text-lg font-semibold mb-2">Informasi Pengiriman</h2>
                
                <div class="text-sm text-gray-700 space-y-1">
                    <p><strong>No. Resi:</strong> {{ shippingInfo.resi_number }}</p>
                    <p><strong>Kurir:</strong> {{ shippingInfo.delivery_provider }}</p>
                    <p><strong>Estimasi Tiba:</strong> 
                        {{ new Date(shippingInfo.estimation_date).toLocaleDateString('id-ID') }}
                    </p>
                    <div>Bukti Pengiriman
                        <img v-if="shippingInfo.delivery_proof" :src="getImageUrl(shippingInfo.delivery_proof)" alt="product"
                        class="w-full h-full object-cover rounded-lg cursor-pointer" />
                        <div v-else class="w-full h-full bg-gray-200 flex items-center justify-center text-xs text-gray-400 rounded-lg">No
                        Image
                        </div>   
                    </div>
                </div>

                <div class="mt-4 text-right">
                <button class="px-4 py-2 bg-blue-600 text-white rounded" @click="showShipping = false">Tutup</button>
                </div>
            </Modal>
        <!-- Modal Upload Transfer -->
        <Modal :show="showUploadTransfer" @close="showUploadTransfer = false" class="max-w-lg">
            <div class="mb-4 text-lg font-semibold">Informasi Rekening</div>

            <div v-if="bankAccount.loading" class="text-gray-500">Memuat data rekening...</div>

            <ul v-else class="space-y-2 max-h-48 overflow-y-auto pr-2">
                <li v-for="bank in bankAccount.bankAccount" :key="bank.id"
                    class="p-3 border rounded-lg bg-gray-50 text-sm md:text-base">
                    <div class="font-medium text-gray-700">Bank ID: {{ bank.id }}</div>
                    <div>Atas Nama: <span class="font-semibold">{{ bank.name }}</span></div>
                    <div>No Rekening: <span class="font-mono">{{ bank.account_number }}</span></div>
                </li>
            </ul>

            <div class="mt-6">
                <label class="block mb-1 font-medium">Upload Bukti Transfer</label>
                <input type="file" @change="handleFileChange"
                    class="block w-full text-sm text-gray-700 file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:bg-blue-600 file:text-white hover:file:bg-blue-700" />
            </div>

            <Button class="mt-4 bg-blue-600 hover:bg-blue-700 px-4 py-2 text-white rounded-md w-full"
                @click="submitTransfer">
                Kirim Bukti Transfer
            </Button>
        </Modal>


    </AppLayout>
</template>
