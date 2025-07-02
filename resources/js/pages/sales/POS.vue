<template>
    <Head title="Point of Sale" />
    <AppLayout>
        <div class="min-h-screen space-y-4 bg-gray-50 p-2 md:p-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-5 md:gap-2">
                <!-- Products Section -->
                <section class="flex flex-col md:col-span-2 lg:col-span-2">
                    <div class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-sm font-semibold text-gray-800">Produk Tersedia</h2>
                        <input
                            type="text"
                            v-model="searchText"
                            @input="onSearchInput"
                            placeholder="Cari produk..."
                            class="focus:ring-primary-500 w-full max-w-xs rounded-md border border-gray-300 px-2 py-1 text-xs focus:outline-none focus:ring-2 md:text-sm"
                        />
                    </div>
                    <div class="flex flex-col gap-2 md:gap-3">
                        <div
                            v-for="product in products"
                            :key="product.id"
                            @click="addToCart(product)"
                            class="flex cursor-pointer flex-row items-center gap-2 rounded-lg border border-gray-200 bg-white p-2 shadow-sm transition hover:shadow-md md:gap-3 md:p-3"
                        >
                            <!-- Column 1: Image -->
                            <div class="flex-shrink-0">
                                <img
                                    v-if="product.image_path"
                                    :src="getImageUrl(product.image_path)"
                                    alt="Gambar produk"
                                    class="h-16 w-16 rounded object-cover"
                                />
                                <div v-else class="flex h-16 w-16 select-none items-center justify-center rounded bg-gray-200 text-xs text-gray-400">
                                    Tidak Ada Gambar
                                </div>
                            </div>
                            <!-- Column 2: Info -->
                            <div class="flex min-w-0 flex-1 flex-col justify-between">
                                <div class="flex items-center gap-2">
                                    <p class="flex-1 truncate text-xs font-semibold text-gray-900 md:text-sm">{{ product.product_name }}</p>
                                    <template v-if="product.discount && product.price">
                                        <span
                                            v-if="product.discount > 0"
                                            class="ml-1 rounded bg-orange-500 px-2 py-0.5 text-[14px] font-bold text-white md:text-xs"
                                        >
                                            {{ Math.round((product.discount / product.price) * 100) }}%
                                        </span>
                                    </template>
                                </div>
                                <p class="text-[10px] text-gray-400 md:text-xs">Stok: {{ product.qty_stock }} / {{ product.uom_id }}</p>
                                <div>
                                    <template v-if="product.discount && product.discount > 0">
                                        <p class="text-[10px] text-gray-400 line-through md:text-xs">{{ formatRupiah(product.price) }}</p>
                                        <p class="text-xs font-semibold text-green-600 md:text-sm">
                                            {{ formatRupiah(product.price_sell ?? product.price - product.discount) }}
                                        </p>
                                        <p class="text-[10px] text-green-500 md:text-xs">(Diskon {{ formatRupiah(product.discount) }})</p>
                                    </template>
                                    <p v-else class="text-xs font-semibold text-gray-700 md:text-sm">{{ formatRupiah(product.price) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Cart Sidebar -->

                <section class="flex flex-col bg-gray-100 md:col-span-2 lg:col-span-3">
                    <div class="flex flex-wrap gap-1 p-2 md:gap-1">
                        <Button
                            variant="outline"
                            size="sm"
                            @click="openDiscountDialog"
                            :disabled="selectedForDiscount.length === 0"
                            class="flex items-center gap-1 bg-indigo-100"
                        >
                            <PercentIcon class="h-4 w-4" /> Disc ({{ selectedForDiscount.length }})
                        </Button>
                        <Button variant="outline" size="sm" @click="showQrScanner = true" class="flex items-center gap-1">
                            <ScanQrCode class="h-4 w-4" /> QR
                        </Button>
                        <Button variant="outline" size="sm" @click="applyCustomer" class="flex items-center gap-1">
                            <UserPlusIcon class="h-4 w-4" /> Customer
                        </Button>
                        <Button variant="outline" size="sm" @click="applyReturn" class="flex items-center gap-1">
                            <ReplaceAll class="h-4 w-4" /> Retur
                        </Button>
                        <Button variant="outline" size="sm" @click="clearCart" class="flex items-center gap-1">
                            <Trash2 class="h-4 w-4" /> Clear
                        </Button>
                    </div>

                    <div v-if="selectedProducts.length === 0" class="select-none py-8 text-center text-xs text-gray-400">
                        Tidak ada item di keranjang
                    </div>

                    <div class="overflow-auto p-2">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="text-xs">#</TableHead>
                                    <TableHead class="text-xs">Nama</TableHead>
                                    <TableHead class="text-center text-xs">Qty</TableHead>
                                    <TableHead class="text-center text-xs">Total</TableHead>
                                    <TableHead class="text-center text-xs">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="item in selectedProducts" :key="item.id">
                                    <TableCell>
                                        <div class="md-grid-cols-2 relative flex grid-cols-1 items-center justify-center">
                                            <input
                                                type="checkbox"
                                                :checked="selectedForDiscount.includes(item.id)"
                                                @change="toggleProductSelection(item.id)"
                                                class="text-primary-600 focus:ring-primary-500 absolute left-0 top-0 rounded border-gray-300"
                                                :title="'Pilih untuk diskon'"
                                            />
                                            <!-- <img
              v-if="item.image_path"
              :src="getImageUrl(item.image_path)"
              alt="product image"
              class="h-12 w-12 object-cover rounded"
            /> -->
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-xs">
                                        <div class="max-w-[140px] truncate font-semibold text-gray-900">{{ item.product_name }}</div>
                                        <span v-if="item.discount && item.discount > 0" class="text-[10px] text-gray-400 line-through">
                                            {{ formatRupiah(item.price) }}
                                        </span>
                                        <span class="block font-semibold text-gray-900">
                                            {{ formatRupiah(item.price_sell || item.price - (item.discount || 0)) }}
                                        </span>
                                        <div v-if="item.discount && item.discount > 0" class="text-[11px] text-green-600">
                                            Diskon: -{{ formatRupiah(item.discount) }}
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <input
                                            type="number"
                                            min="1"
                                            v-model.number="item.quantity"
                                            class="w-12 rounded border px-1 py-0.5 text-center text-xs"
                                        />
                                        <!-- @change="updateQuantity(item)" -->
                                    </TableCell>
                                    <TableCell class="text-right text-xs font-semibold text-gray-900">
                                        {{ formatRupiah(item.quantity * (item.price_sell || item.price)) }}
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <Button variant="ghost" size="icon" @click="removeFromCart(item.id)" class="hover:bg-gray-100">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <div v-if="selectedCustomerName" class="mt-2 flex justify-between p-3">
                        <span class="text-xs font-bold">Pelanggan:</span>
                        <span class="text-xs font-bold">{{ selectedCustomerName }} (#{{ selectedCustomerId }})</span>
                    </div>

                    <div class="mt-2 rounded-lg p-1 shadow-sm  gap-4 p-4">
                        <label class="mb-1 block text-xs font-medium text-gray-900">Metode Pembayaran</label>
                        <select
                            v-model="selectedPaymentMethod"
                            class="focus:ring-primary-500 w-full rounded-lg border border-gray-300 p-1 text-xs focus:outline-none focus:ring-2"
                        >
                            <option value="">Pilih metode pembayaran</option>
                            <option v-for="method in paymentMethods" :key="method.id" :value="method.id">{{ method.name }}</option>
                        </select>
                    </div>

                    <div class="mt-4 border-t pt-2 gap-4 p-4">
                        <div class="mb-2 flex justify-between text-xs font-semibold text-gray-700">
                            <span>Total</span>
                            <span class="font-bold">{{ formattedTotalAmount }}</span>
                        </div>
                        <Button class="w-full text-xs font-semibold rounded-md bg-indigo-600 py-2 text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500" @click="openPaymentDialog" :disabled="isLoading.placingOrder">
                            <span v-if="isLoading.placingOrder">Memproses...</span>
                            <span v-else>Bayar</span>
                        </Button>
                    </div>
                </section>
            </div>

            <!-- Modals... (Your modal markup remains unchanged, just ensure responsive widths, max widths, and padding) -->
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 p-4">
                <div class="w-full max-w-xs rounded-xl bg-white p-6 shadow-lg">
                    <h3 class="mb-4 text-lg font-semibold">Set Discount</h3>
                    <label class="mb-2 block text-sm font-medium">Discount Price</label>
                    <input
                        type="number"
                        v-model.number="discountInput"
                        class="focus:ring-primary-500 mb-4 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2"
                    />
                    <div class="flex justify-end gap-3">
                        <button @click="saveDiscount" class="rounded bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700">Save</button>
                        <button @click="showModal = false" class="rounded bg-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-400">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>

            <!-- Payment Dialog -->
            <div v-if="showPaymentDialog" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 p-4">
                <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-lg">
                    <h3 class="mb-4 text-lg font-semibold">Masukkan Jumlah Bayar</h3>
                    <input
                        type="number"
                        min="0"
                        v-model.number="paidAmount"
                        class="mb-4 w-full rounded border px-3 py-2 text-lg"
                        placeholder="Masukkan jumlah bayar"
                    />
                    <div class="mb-4 flex justify-between">
                        <div>Total:</div>
                        <div class="font-semibold">{{ formattedTotalAmount }}</div>
                    </div>
                    <div class="mb-4 flex justify-between">
                        <div>Kembalian:</div>
                        <div class="font-semibold text-green-600">{{ formatRupiah(changeAmount) }}</div>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <Button variant="outline" @click="showPaymentDialog = false">Batal</Button>
                        <Button
                            @click="confirmPayment"
                            class="rounded-md bg-indigo-600 py-2 text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500"
                            >Konfirmasi</Button
                        >
                    </div>
                </div>
            </div>

            <!-- QR Code Scanner Modal -->
            <Modal :show="showQrScanner" @close="showQrScanner = false" title="Scan QR Code">
                <div class="h-64 w-full">
                    <qrcode-stream @detect="onDetect" />
                </div>
            </Modal>

            <Modal :show="showReturnDialog" @close="closeReturnDialog" title="Retur Barang">
    <div class="space-y-2">
        <label class="block text-xs font-medium">Produk</label>
        <select v-model="returnProductId" class="w-full border rounded p-1 text-xs">
            <option value="">Pilih Produk</option>
            <option v-for="product in products" :key="product.id" :value="product.id">
                {{ product.product_name }} (Stok: {{ product.qty_stock }})
            </option>
        </select>
        <label class="block text-xs font-medium">Qty</label>
        <input type="number" v-model.number="returnQty" min="1" class="w-full border rounded p-1 text-xs" />
        <label class="block text-xs font-medium">Harga Retur</label>
        <input type="number" v-model.number="returnPrice" min="0" class="w-full border rounded p-1 text-xs" />
        <div class="flex justify-end gap-2 mt-2">
            <Button variant="outline" @click="closeReturnDialog">Batal</Button>
            <Button class="bg-red-600 text-white" @click="handleReturnAddToCart">Simpan</Button>
        </div>
        <div v-if="returnError" class="text-xs text-red-600 mt-1">{{ returnError }}</div>
        <div v-if="returnSuccess" class="text-xs text-red-600 mt-1">Retur berhasil ditambahkan ke keranjang!</div>
    </div>
</Modal>

            <!-- Print Preview Modal -->
            <div v-if="showPrintPreview" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-30 p-4">
                <div class="print-area w-[450px] max-w-full rounded bg-white p-2 font-mono text-[10px] leading-tight shadow" ref="printArea">
                    <div class="text-center">
                        <p class="text-[12px] font-bold">{{ locationName }}</p>
                        <p>{{ locationAddress }}</p>
                    </div>

                    <hr class="my-1 border-t border-dashed" />

                    <div class="mb-1">
                        <p>Tanggal&nbsp;: {{ lastOrderDate }}</p>
                        <p>Kasir&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ cashierName }}</p>
                        <p>Nomor&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ transactionNumber }}</p>
                        <p>Customer&nbsp;&nbsp;: {{ selectedCustomerName || '-' }}</p>
                    </div>

                    <hr class="my-1 border-t border-dashed" />

                    <!-- Items -->
                    <div v-for="(item, index) in lastOrderItems" :key="item.product_id" class="mb-1">
                        <p>{{ index + 1 }}. {{ item.product_name }}</p>
                        <div class="flex justify-between">
                            <span>{{ item.quantity }} x {{ formatRupiah(item.price) }}</span>
                            <span>&nbsp;&nbsp;{{ formatRupiah(item.price * item.quantity) }}</span>
                        </div>
                        <div v-if="item.discount">
                            <div class="flex justify-between">
                                <span class="text-green-600">- {{ formatRupiah(item.discount) }}</span>
                                <span>{{ formatRupiah(item.price * item.quantity - item.discount) }}</span>
                            </div>
                        </div>
                    </div>

                    <hr class="my-1 border-t border-dashed" />

                    <!-- Totals -->
                    <div class="flex justify-between">
                        <span>Sub Total&nbsp;&nbsp;&nbsp;&nbsp;:</span>
                        <span>{{ formatRupiah(lastOrderSubTotal) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Jumlah Bayar&nbsp;:</span>
                        <span>{{ formatRupiah(paidAmount || 0) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Kembalian&nbsp;&nbsp;&nbsp;&nbsp;:</span>
                        <span>{{ formatRupiah(changeAmount) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Total Disc&nbsp;&nbsp;&nbsp;:</span>
                        <span>{{ formatRupiah(totalDiscount) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Metode&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</span>
                        <span>{{ lastOrderPaymentMethodName }}</span>
                    </div>

                    <hr class="my-1 border-t border-dashed" />
                    <p class="text-center">SELAMAT BERBELANJA</p>
                    <hr class="my-1 border-t border-dashed" />

                    <!-- Buttons (hidden when printing) -->
                    <div class="no-print mt-2 flex justify-end gap-2">
                        <Button variant="outline" @click="closePrintPreview">TUTUP</Button>
                        <Button
                            @click="doPrintKasir80mm"
                            class="rounded-md bg-indigo-600 py-2 text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500"
                            >CETAK</Button
                        >
                    </div>
                </div>
            </div>
        </div>
        <CustomerDialog :show="showCustomerDialog" @update:show="showCustomerDialog = $event" @customer-selected="handleCustomerSelected" />
    </AppLayout>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';

import { useToast } from '@/composables/useToast';
import type { User } from '@/types';
import { type SharedData } from '@/types';
import { PercentIcon, ScanQrCode, Trash2, UserPlusIcon, ReplaceAll } from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
// import jsQR from 'jsqr';
import CustomerDialog from '@/components/CustomerDialog.vue';
import Modal from '@/components/Modal.vue';
import { QrcodeStream } from 'vue-qrcode-reader';

interface Product {
    id: number;
    product_id?: number;
    product_name: string;
    uom_id: string;
    size_id: string;
    qty_stock: number;
    image_path: string;
    price: number;
    discount?: number;
    price_sell?: number;
    quantity: number;
    isReturn?: boolean;
}

interface PaymentMethod {
    id: string;
    name: string;
}

interface OrderItem {
    product_id: number;
    product_name: string;
    quantity: number;
    price: number;
    discount: number;
    price_sell: number;
    uom_id: string;
    size_id: string; // Optional size ID
    qty_stock: number;
    image_path: string;
}

interface OrderPayload {
    items: OrderItem[];
    payment_method_id: string;
    total_amount: number;
    paid_amount: number;
    customer_id?: number | null;
}

// const showScanner = ref(false);
// const videoElement = ref<HTMLVideoElement>();
// const stream = ref<MediaStream | null>(null);
const showModal = ref(false);

const showQrScanner = ref(false);

const toast = useToast();
const products = ref<Product[]>([]);
const selectedProducts = ref<Product[]>([]);
const paymentMethods = ref<PaymentMethod[]>([]);
const selectedPaymentMethod = ref<string | null>(null);
const isLoading = ref({ placingOrder: false });

const searchText = ref('');
const currentPage = ref(1);
const lastPage = ref(1);
const discountInput = ref<number>(0);
const selectedForDiscount = ref<number[]>([]); // Stores selected product IDs

const showPrintPreview = ref(false);
const showCustomerDialog = ref(false);
const selectedCustomerId = ref<number | null>(null);
const selectedCustomerName = ref<string | null>(null);
const lastOrderItems = ref<OrderItem[]>([]);
const lastOrderTotal = ref(0);
const lastOrderPaymentMethodName = ref('');
const lastOrderDate = ref('');
const transactionNumber = ref('');

const orderList = ref<HTMLElement | null>(null);
const printArea = ref<HTMLElement | null>(null);

const showReturnDialog = ref(false);
const returnProductId = ref<number | ''>('');
const returnQty = ref<number>(1);
const returnPrice = ref<number>(0);
const returnError = ref('');
const returnSuccess = ref(false);

const paidAmount = ref<number | null>(null);
const changeAmount = computed(() => {
    if (paidAmount.value === null) return 0;
    return paidAmount.value - totalAmount.value > 0 ? paidAmount.value - totalAmount.value : 0;
});

function applyReturn() {
    showReturnDialog.value = true;
}

function closeReturnDialog() {
    showReturnDialog.value = false;
    returnProductId.value = '';
    returnQty.value = 1;
    returnPrice.value = 0;
    returnError.value = '';
    returnSuccess.value = false;
}

function handleReturnAddToCart() {
    returnError.value = '';
    returnSuccess.value = false;
    const product = products.value.find(p => p.id === returnProductId.value);
    if (!product) {
        returnError.value = 'Produk harus dipilih';
        return;
    }
    if (!returnQty.value || returnQty.value < 1) {
        returnError.value = 'Qty minimal 1';
        return;
    }
    if (!returnPrice.value || returnPrice.value < 0) {
        returnError.value = 'Harga retur harus diisi';
        return;
    }
    selectedProducts.value.push({
        ...product,
        quantity: -Math.abs(returnQty.value), // negative qty for return
        price: returnPrice.value,
        price_sell: returnPrice.value,
        isReturn: true,
    });
    returnSuccess.value = true;
    setTimeout(() => {
        closeReturnDialog();
    }, 1000);
}

const locationName = ref('TOKO ANINKA');
const locationAddress = ref('Blok A lantai SLG Los F No 91-92');

const page = usePage<SharedData>();
const userLogin = page.props.auth.user as User;
const locationId = (userLogin as any)?.location_id;

async function fetchLocationInfo() {
    if (!locationId) return;
    try {
        const res = await axios.get(`/api/locations/get`);
        locationName.value = res.data.name || 'TOKO ANINKA';
        locationAddress.value = res.data.address || 'Blok A lantai SLG Los F No 91-92';
    } catch {
        locationName.value = 'TOKO ANINKA';
        locationAddress.value = 'Blok A lantai SLG Los F No 91-92';
    }
}

fetchLocationInfo();

// Hitung total diskon dari item
const totalDiscount = computed(() => lastOrderItems.value.reduce((sum, item) => sum + (item.discount ?? 0) * item.quantity, 0));

// Hitung subtotal dari item
const lastOrderSubTotal = computed(() => lastOrderItems.value.reduce((sum, item) => sum + item.price * item.quantity, 0));

const showPaymentDialog = ref(false);

// Cara langsung ambil props
const { user } = defineProps<{ user: User }>();

// Buat computed property dari nama user
const cashierName = computed(() => user?.name ?? 'Kasir');

const getImageUrl = (path: string) => {
    if (!path) return '';
    if (path.startsWith('storage/')) return '/' + path;
    if (path.startsWith('/storage/')) return path;
    return '/storage/' + path;
};

const formatRupiah = (value: number): string => {
    if (typeof value !== 'number' || isNaN(value)) return '0,00';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 2,
    }).format(value);
};

const toggleProductSelection = (productId: number) => {
    const index = selectedForDiscount.value.indexOf(productId);
    if (index === -1) {
        selectedForDiscount.value.push(productId);
    } else {
        selectedForDiscount.value.splice(index, 1);
    }
};

async function fetchProducts() {
    try {
        const response = await axios.get(`/api/stock?page=${currentPage.value}&search=${searchText.value}`);
        products.value = response.data.data;
        lastPage.value = response.data.last_page;
    } catch (error) {
        console.error('Error fetching products:', error);
        toast.error('Failed to fetch products');
    }
}

async function fetchPaymentMethods() {
    try {
        const response = await axios.get('/api/payment-methods');
        paymentMethods.value = response.data.data;
    } catch {
        toast.error('Failed to fetch payment methods');
    }
}

function onSearchInput() {
    currentPage.value = 1;
    fetchProducts();
}

const addToCart = (product: Product) => {
    
    if (product.qty_stock <= 0) {
        toast.error('Stok tidak cukup');
        return;
    }
    // check price = 0 or price_sell 0 toast.error('Harga produk tidak valid');
    if (product.price <= 0 && (product.price_sell === undefined || product.price_sell === null || product.price_sell <= 0)) {
        toast.error('Harga produk belum ditentukan');
        return;
    }
    const existing = selectedProducts.value.find((item) => item.id === product.product_id);
    if (existing) {
        existing.quantity++;
    } else {
        selectedProducts.value.push({
            id: product.product_id || product.id,
            product_name: product.product_name,
            uom_id: product.uom_id,
            size_id: product.size_id || '',
            qty_stock: product.qty_stock,
            image_path: product.image_path,
            price: product.price,
            discount: product.discount,
            price_sell: product.price_sell,
            quantity: 1,
        });
    }
    nextTick(() => {
        if (orderList.value) orderList.value.scrollTop = orderList.value.scrollHeight;
    });
};

function removeFromCart(productId: number) {
    selectedProducts.value = selectedProducts.value.filter((p) => p.id !== productId);
}

// function selectProduct(product: Product) {
//   const exists = selectedProducts.value.some(p => p.id === product.id);
//   if (!exists) {
//     selectedProducts.value.push({ ...product });
//   }
// }

// function updateQuantity(item: Product) {
//     if (item.quantity < 1) {
//         item.quantity = 1;
//         toast.error('Minimal quantity 1');
//         return; // Stop here to avoid checking against stock
//     }

//     const productStock = products.value.find((p) => p.id === item.id)?.qty_stock ?? 0;

//     if (productStock === 0) {
//         item.quantity = 1;
//         toast.error('Stock kosong');
//         return;
//     }

//     if (item.quantity > productStock) {
//         item.quantity = productStock;
//         toast.error('Stock limit reached');
//     }
// }


function openDiscountDialog() {
    if (selectedProducts.value.length === 0) return;

    showModal.value = true;
}

function saveDiscount() {
    selectedProducts.value = selectedProducts.value.map((product) => {
        // Only update discount if product is selected
        if (selectedForDiscount.value.includes(product.id)) {
            return {
                ...product,
                discount: discountInput.value,
                price_sell: product.price - discountInput.value,
            };
        }
        return product;
    });

    // Clear selections after applying discount
    selectedForDiscount.value = [];
    showModal.value = false;
    toast.success('Discount applied successfully');
}

function openPaymentDialog() {
    if (selectedProducts.value.length === 0) {
        toast.error('Please add items to the cart');
        return;
    }
    if (!selectedPaymentMethod.value) {
        toast.error('Please select a payment method');
        return;
    }
    paidAmount.value = null;
    showPaymentDialog.value = true;
}

async function confirmPayment() {
    if (paymentMethods.value.length === 0) {
        toast.error('No payment methods available');
        return;
    }
    const selectedMethod = paymentMethods.value.find(pm => pm.id === selectedPaymentMethod.value);
    if (!selectedMethod || selectedMethod.name !== 'CREDIT') {
        if (paidAmount.value === null || paidAmount.value < totalAmount.value) {
            toast.error('Paid amount must be at least total amount');
            return;
        }
    }

    
    showPaymentDialog.value = false;
    await placeOrder();
}

function clearCart() {
    selectedProducts.value = [];
    selectedPaymentMethod.value = null;
    selectedForDiscount.value = []; // Clear discount selections
}

const applyCustomer = () => {
    showCustomerDialog.value = true;
};

const handleCustomerSelected = (customer: { id: number; name: string }) => {
    selectedCustomerId.value = customer.id;
    selectedCustomerName.value = customer.name;
    showCustomerDialog.value = false;
};

const totalAmount = computed(() => selectedProducts.value.reduce((sum, item) => sum + item.price * item.quantity, 0));
const formattedTotalAmount = computed(() => `${formatRupiah(totalAmount.value)}`);

async function placeOrder() {
    if (selectedProducts.value.length === 0) {
        toast.error('Please add items to the cart');
        return;
    }

    if (!selectedPaymentMethod.value) {
        toast.error('Please select a payment method');
        return;
    }

    isLoading.value.placingOrder = true;

    try {
        // Prepare items
        const items: OrderItem[] = selectedProducts.value.map((p) => {
            const discount = p.discount || 0;
            return {
                product_id: p.product_id ?? p.id,
                product_name: p.product_name,
                quantity: p.quantity,
                price: p.price,
                uom_id: p.uom_id,
                size_id: p.size_id || 'PCS', 
                qty_stock: 0,
                image_path: '',
                discount,
                price_sell: p.price_sell ?? p.price - discount,
            };
        });

        const orderPayload: OrderPayload = {
            items,
            payment_method_id: selectedPaymentMethod.value,
            total_amount: totalAmount.value,
            paid_amount: paidAmount.value!,
            customer_id: selectedCustomerId.value, // Add customer_id here
        };

        const response = await axios.post('/api/pos/orders', orderPayload);

        // For print preview or record
        lastOrderItems.value = items.map((item) => ({
            ...item,
            uom_id: selectedProducts.value.find((p) => (p.product_id ?? p.id) === item.product_id)?.uom_id || '',
        }));

        lastOrderTotal.value = response.data.total_amount;
        lastOrderPaymentMethodName.value = paymentMethods.value.find((pm) => pm.id === selectedPaymentMethod.value)?.name || '';
        lastOrderDate.value = new Date(response.data.created_at).toLocaleString();
        transactionNumber.value = response.data.transaction_number || '';

        showPrintPreview.value = true;
    } catch (error) {
        console.error('Error placing order:', error);
        toast.error('Failed to place order');
    } finally {
        isLoading.value.placingOrder = false;
    }
}

function closePrintPreview() {
    showPrintPreview.value = false;
    clearCart(); // reset cart
    selectedPaymentMethod.value = null; // reset payment method
}

const onDetect = async (detectedCodes: { rawValue: string }[]) => {
    if (detectedCodes.length === 0) return;

    const qrCodeData = detectedCodes[0].rawValue;
    console.log('QR Code detected:', qrCodeData);
    showQrScanner.value = false;

    try {
        const orderIds = qrCodeData.split(',').map(id => id.trim());

        const response = await axios.get('/api/orders/scan', {
            params: { ids: orderIds.join(',') },
        });

        const orders = response.data;

        const combinedOrderItems: OrderItem[] = [];

        orders.forEach((order: any) => {
            if (order?.order_items?.length) {
                combinedOrderItems.push(...order.order_items);
            }
        });

        if (combinedOrderItems.length === 0) {
            toast.error('No order items found for the scanned QR code(s).');
            return;
        }

        const products: Product[] = combinedOrderItems.map((item) => ({
            id: item.product_id,
            product_id: item.product_id,
            product_name: item.product_name,
            uom_id: item.uom_id,
            size_id: item.size_id || 'PCS',
            qty_stock: item.qty_stock,
            image_path: item.image_path,
            price: item.price,
            price_sell: item.price_sell,
            discount: item.discount,
            quantity: item.quantity ?? 1,
        }));

        console.log('Produk dalam pesanan:', products);

        products.forEach((product) => {
            const existingIndex = selectedProducts.value.findIndex(
                (p) => (p.product_id ?? p.id) === (product.product_id ?? product.id)
            );

            if (existingIndex > -1) {
                selectedProducts.value[existingIndex].quantity += product.quantity;
            } else {
                selectedProducts.value.push({ ...product });
            }
        });

        toast.success('Order items added to cart!');
    } catch (error) {
        console.error('Error scanning QR code:', error);
        toast.error('Failed to scan QR code or retrieve order details.');
    }
};


function doPrintKasir80mm() {
    // QZ Tray integration for direct thermal printing
    // @ts-expect-ignore
    const qz = (window as any).qz;
    // Ambil nama kasir dari user login
    const kasirName = name ?? name ?? 'Kasir';
    if (qz) {
        const totalQty = lastOrderItems.value.reduce((sum, item) => sum + item.quantity, 0);
        const sisa = (lastOrderTotal.value || totalAmount.value) - (paidAmount.value || 0);

        const lines: string[] = [];
        lines.push(`     ${locationName.value}`);
        lines.push(` ${locationAddress.value}`);
        lines.push('----------------------------------------');
        lines.push(`Kasir   : ${kasirName}`);
        lines.push(`Tanggal : ${lastOrderDate.value}`);
        lines.push(`Nomor   : ${transactionNumber.value}`);
        lines.push('------------------------------------');
        lines.push(`Customer: ${selectedCustomerName.value || '-'}`);
        lines.push('--------------------------------------');
        lastOrderItems.value.forEach(item => {
            lines.push(`${item.product_name}`);
            lines.push(`${item.quantity} x ${formatRupiah(item.price)}${' '.repeat(20 - (item.quantity + '').length - formatRupiah(item.price).length)}${formatRupiah(item.quantity * item.price)}`);
        });
        lines.push('--------------------------------');
        lines.push(`Total QTY        ${totalQty}`);
        lines.push(`Total Bayar      ${formatRupiah(lastOrderTotal.value || totalAmount.value)}`);
        lines.push(`Jenis Bayar      ${lastOrderPaymentMethodName.value || '-'}`);
        lines.push(`Bayar Cash       ${formatRupiah(paidAmount.value || 0)}`);
        lines.push(`Sisa             ${formatRupiah(sisa > 0 ? sisa : 0)}`);
        lines.push('--------------------------------------');
        lines.push('      ---TERIMA KASIH---');
        lines.push('     aninkafashion.com');
        lines.push('\n\n\n');

        qz.websocket.connect().then(() => {
            return qz.printers.find();
        }).then((printer: string) => {
            const config = qz.configs.create(printer);
            return qz.print(config, [{ type: 'raw', format: 'plain', data: lines.join('\n') }]);
        }).catch((err: any) => {
            alert('Print error: ' + err);
        }).finally(() => {
            qz.websocket.disconnect();
        });
        return;
    }

    // Fallback: browser print
    // ...existing code for browser print...
    const totalQty = lastOrderItems.value.reduce((sum, item) => sum + item.quantity, 0);
    const sisa = (lastOrderTotal.value || totalAmount.value) - (paidAmount.value || 0);

    const printContent = `
    <div style="font-family: 'Courier New', Courier, monospace; font-size: 12px; width: 80mm; padding: 0; margin: 0;">
      <div style="text-align:center; font-weight:bold;">${locationName.value}</div>
      <div style="text-align:center;">${locationAddress.value}</div>
      <div style="text-align:center;">----------------------------------------</div>
      <div style="display:flex; justify-content:space-between;">
        <span>Kasir   : ${cashierName.value}</span>
      </div>
      <div style="display:flex; justify-content:space-between;">
        <span>Tanggal : ${lastOrderDate.value}</span>
      </div>
      <div style="display:flex; justify-content:space-between;">
        <span>Nomor   : ${transactionNumber.value}</span>
      </div>
      <div style="text-align:center;">----------------------------------------</div>
      <div style="display:flex; justify-content:space-between;">
        <span>Customer</span>
        <span>${selectedCustomerName.value || '-'}</span>
      </div>
      <div style="text-align:center;">----------------------------------------</div>
      ${lastOrderItems.value
          .map(
              (item) => `
          <div>${item.product_name}</div>
          <div style="display:flex; justify-content:space-between;">
            <span>${item.quantity} x ${formatRupiah(item.price)}</span>
            <span>${formatRupiah(item.quantity * item.price)}</span>
          </div>
        `,
          )
          .join('')}
      <div style="text-align:center;">----------------------------------------</div>
      <div style="display:flex; justify-content:space-between;">
        <span>Total QTY</span>
        <span>${totalQty}</span>
      </div>
      <div style="display:flex; justify-content:space-between;">
        <span>Total Bayar</span>
        <span>${formatRupiah(lastOrderTotal.value || totalAmount.value)}</span>
      </div>
      <div style="display:flex; justify-content:space-between;">
        <span>Jenis Bayar</span>
        <span>${lastOrderPaymentMethodName.value || '-'}</span>
      </div>
      <div style="display:flex; justify-content:space-between;">
        <span>Bayar Cash</span>
        <span>${formatRupiah(paidAmount.value || 0)}</span>
      </div>
      <div style="display:flex; justify-content:space-between;">
        <span>Sisa</span>
        <span>${formatRupiah(sisa > 0 ? sisa : 0)}</span>
      </div>
      <div style="text-align:center;">----------------------------------------</div>
      <div style="text-align:center; margin-top:8px;">---TERIMA KASIH---</div>
      <div style="text-align:center;">aninkafashion.com</div>
    </div>
  `;

    const printWindow = window.open('', '', 'height=600,width=400');
    if (!printWindow) {
        alert('Please allow pop-ups for printing.');
        return;
    }
    printWindow.document.write(`
    <html>
      <head>
        <title>Print Struk</title>
        <style>
          @media print {
            body { margin: 0; }
            div { font-family: 'Courier New', Courier, monospace; font-size: 12px; }
          }
        </style>
      </head>
      <body>
        ${printContent}
        <script>
          window.onload = function() {
            setTimeout(function() {
              window.print();
              window.onafterprint = function() { window.close(); };
            }, 100);
          };
        <\/script>
      </body>
    </html>
  `);
    printWindow.document.close();
}

fetchProducts();
fetchPaymentMethods();
</script>

<style scoped>
.print-area {
    width: 100%;
    max-width: 600px;
}

@media print {
    .no-print,
    .no-print * {
        display: none !important;
    }
}
.print-area {
    width: 58mm; /* Set to 58mm for thermal printer */
    font-family: 'Courier New', Courier, monospace; /* Monospaced font for better alignment */
    font-size: 10px; /* Adjust font size as needed */
    padding: 5mm; /* Padding around the content */
    box-shadow: none; /* Remove box shadow for print */
}
</style>
