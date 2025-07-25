<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { useCartStore } from '@/stores/useCartStore';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import QrcodeVue from 'qrcode.vue';
import { computed, defineProps, onMounted, ref } from 'vue';

interface Product {
    id: number;
    name: string;
    description: string;
    price: number;
    price_sell?: number;
    image: string;
    stock: number;
    category_id: number;
    size_id: string;
    uom_id: string;
    discount?: number;
}

interface CartItem {
    id: number;
    product_id: number;
    quantity: number;
    price: number;
    discount?: number;
    price_sell?: number;
    price_sell_grosir?: number;
    price_grosir?: number;
    discount_grosir?: number;
    size_id?: string;
    uom_id?: string;
    created_by: number;
    updated_by?: number;
    created_at: string;
    updated_at?: string;
    product: Product;
}

interface User {
    customer_id?: number;
}

const props = defineProps({
    user: Object as () => User,
});

const cartStore = useCartStore();
const toast = useToast();

const showPostCheckoutModal = ref(false);
const orderIdForQr = ref<string | null>(null);
 const orderIdForUpload = ref<string | null>(null);
 const showUploadProofModal = ref(false);
 const bankAccountInfo = ref<string | null>(null);
 const selectedPaymentMethod = ref<string | null>(null);
 const settingMessage = ref<string | null>(null);
 const settingMessage2 = ref<string | null>(null);
 const paymentProofFile = ref<File | null>(null);


const subtotalAmount = computed(() => {
    return cartStore.cartItems.reduce((sum: number, item: CartItem) => {
        return sum + (item.quantity || 0) * (item.price || 0);
    }, 0);
});

const discountAmount = computed(() => {
    return cartStore.cartItems.reduce((sum: number, item: CartItem) => {
        // Assuming item.price is the original price and item.price_sell is the final price after discount
        const originalPrice = item.price || 0;
        const finalPricePerItem = item.price_sell || 0;
        const discountPerItem = originalPrice - finalPricePerItem;
        return sum + discountPerItem * (item.quantity || 0);
    }, 0);
});

const totalAmount = computed(() => subtotalAmount.value - discountAmount.value);

function formatRupiah(value: number | string): string {
    const numericValue = typeof value === 'string' ? parseFloat(value) : value;

    if (!numericValue || numericValue <= 0) {
        return '-'; // atau return 'Harga belum tersedia'
    }

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 2,
    }).format(numericValue);
}

const cartItemValue = computed(() => cartStore.cartItems);

onMounted(async () => {
  
    if (cartStore.cartItems.length === 0) {
        toast.warning('Your cart is empty. Please add items before checking out.');
        router.visit('/home');
    }

    try {
        const response = await axios.get('/api/setting/cod_store');
        settingMessage.value = response.data.message;
    } catch (error) {
        console.error('Error fetching COD store setting:', error);
        settingMessage.value = 'Could not load COD store information.';
    }

    try {
        const resp = await axios.get('/api/setting/bank_transfer');
        settingMessage2.value = resp.data.message;
    } catch (error) {
        console.error('Error fetching bank transfer setting:', error);
        settingMessage2.value = 'Could not load bank transfer information.';
    }
});

const confirmCheckout = async () => {
    if (!selectedPaymentMethod.value) {
        toast.error('Silahkan pilih metode pembayaran.');
        return;
    }

    // Removed initial payment proof check for bank_transfer as per new requirement
    // Payment proof will be uploaded in a separate step for bank_transfer

    // if (selectedPaymentMethod.value === 'bank_transfer' && !paymentProofFile.value) {
    //     toast.error('Silahkan unggah bukti transfer.');
    //     return;
    // }

    if (confirm('Apakah anda yakin ingin proses?')) {
        try {
            interface OrderData {
                customer_id: number | undefined;
                items: { product_id: number; quantity: number; uom_id: string, size_id: string, price: number; discount: number, price_sell: number }[];
                payment_method: string;
                total_amount: number;
                payment_proof?: File | null; // Made optional
                [key: string]: any; // Add string index signature
            }

            const orderData: OrderData = {
                customer_id: props.user?.customer_id,
                items: cartStore.cartItems
                    .filter((item: CartItem) => item.product?.id)
                    .map((item: CartItem) => ({
                        product_id: item.product!.id,
                        quantity: item.quantity,
                        price: item.price || 0, // Original price per unit
                        price_sell: item.price_sell || 0, // Selling price per unit after discount
                        size_id: item.size_id ?? item.product?.size_id ?? '',
                        uom_id: item.uom_id ?? item.product?.uom_id ?? 'PCS',
                        // Calculate discount per item based on the difference between original price and selling price
                        discount: (item.price || 0) - (item.price_sell || 0),
                    })),
                payment_method: selectedPaymentMethod.value,
                total_amount: totalAmount.value,
            };

            // Only include payment_proof if it's not bank_transfer or if it's being uploaded in the second step
            if (selectedPaymentMethod.value !== 'bank_transfer' && paymentProofFile.value) {
                orderData.payment_proof = paymentProofFile.value;
            }

            const formData = new FormData();
            for (const key in orderData) {
                if (orderData[key] !== null) {
                    if (key === 'items') {
                        orderData.items.forEach((item: any, index: number) => {
                            for (const itemKey in item) {
                                formData.append(`items[${index}][${itemKey}]`, item[itemKey]);
                            }
                        });
                    } else {
                        formData.append(key, orderData[key]);
                    }
                }
            }

            const response = await axios.post('/api/orders', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            toast.success('Berhasil!');
            cartStore.clearCart();

            // Handle post-checkout actions based on payment method
            if (selectedPaymentMethod.value === 'cod_store') {
                orderIdForQr.value = response.data.order.id; // Assuming API returns order_id
                showPostCheckoutModal.value = true;
            } else if (selectedPaymentMethod.value === 'bank_transfer') {
                orderIdForUpload.value = response.data.order.id; // Assuming API returns order_id
                showUploadProofModal.value = true; // Show the new modal for bank transfer proof
                bankAccountInfo.value = response.data.bank_account_info; // Assuming API returns bank info
            } else {
                router.visit('/');
            }
        } catch (error) {
            toast.error('Gagal memproses checkout.');
            console.error('Checkout error:', error);
        }
    }
};

const uploadPaymentProof = async () => {
    if (!paymentProofFile.value || !orderIdForUpload.value) {
        toast.error('Silahkan pilih bukti transfer dan pastikan order ID tersedia.');
        return;
    }

    const formData = new FormData();
    formData.append('payment_proof', paymentProofFile.value);

    try {
        await axios.post(`/api/orders/${orderIdForUpload.value}/upload-payment-proof`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        toast.success('Bukti transfer berhasil diunggah!');
        showUploadProofModal.value = false;
        router.visit('/');
    } catch (error) {
        toast.error('Gagal mengunggah bukti transfer.');
        console.error('Upload payment proof error:', error);
    }
};

const goBackToCart = () => {
    router.visit('/home');
};

// const handleFileUpload = (event: Event) => {
//     const target = event.target as HTMLInputElement;
//     if (target.files && target.files.length > 0) {
//         paymentProofFile.value = target.files[0];
//     } else {
//         paymentProofFile.value = null;
//     }
// };
</script>

<template>
    <Head title="Checkout Produk" />
    <AppLayout>
        <div class="container mx-auto px-4 py-8">
            <!-- Modal QR Code -->
            <div v-if="showPostCheckoutModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-600 bg-opacity-75">
                <div class="w-full max-w-md rounded-lg bg-white p-8 text-center shadow-xl">
                    <h2 class="mb-4 text-xl font-bold">Pesanan Dikonfirmasi!</h2>
                    <div v-if="orderIdForQr">
                        <p class="mb-4 text-lg">
                            ID Pesanan Anda: <span class="font-semibold">{{ orderIdForQr }}</span>
                        </p>
                        <p class="text-md mb-6 text-gray-600">Silakan tunjukkan QR code ini di toko untuk melakukan pembayaran.</p>
                        <div class="mb-6 flex justify-center">
                            <qrcode-vue :value="orderIdForQr" :size="200" level="H" />
                        </div>
                        <Button @click="router.visit('/')">Kembali ke Beranda</Button>
                    </div>
                </div>
            </div>

            <!-- Modal Upload Bukti Transfer -->
            <div v-if="showUploadProofModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex justify-center items-center">
                <div class="bg-white p-8 rounded-lg shadow-xl max-w-md w-full">
                    <h2 class="text-2xl font-bold mb-4 text-center">Pesanan Berhasil!</h2>
                    <p class="text-center mb-4">Pesanan Anda berhasil dibuat. Silakan transfer total pembayaran ke rekening berikut:</p>
                    <div v-if="bankAccountInfo" class="bg-gray-100 p-4 rounded-md mb-6">
                        <p class="font-semibold">Informasi Rekening Bank:</p>
                        <div v-html="bankAccountInfo"></div>
                    </div>
                    <p class="text-center mb-4">ID Pesanan: {{ orderIdForUpload }}</p>
                    <label for="paymentProofUpload" class="block text-sm font-medium text-gray-700 mb-2">Unggah Bukti Transfer</label>
                    <input
                        type="file"
                        id="paymentProofUpload"
                        @change="(e) => paymentProofFile = (e.target as HTMLInputElement).files?.[0] || null"
                        class="mt-1 block w-full text-sm text-gray-500
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-full file:border-0
                            file:text-sm file:font-semibold
                            file:bg-blue-50 file:text-blue-700
                            hover:file:bg-blue-100 mb-4"
                    />
                    <div class="flex justify-end gap-2">
                        <Button @click="uploadPaymentProof" class="bg-indigo-600 text-white py-2 h-10 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">Kirim Bukti Pembayaran</Button>
                        <Button @click="router.visit('/')" type="button" variant="outline">Nanti Saja</Button>
                    </div>
                </div>
            </div>

            <div v-if="cartItemValue.length > 0 && !showPostCheckoutModal" class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Rincian Pesanan -->
                <div class="mb-8 rounded-2xl bg-white p-8 shadow-xl lg:col-span-2">
                    <h2 class="mb-6 text-l text-gray-800">Rincian Pesanan</h2>

                    <div
                        v-for="item in cartItemValue"
                        :key="item.product?.id"
                        class="mb-6 flex items-center justify-between border-b border-gray-200 pb-4 last:mb-0 last:border-b-0"
                    >
                        <div class="flex items-center space-x-4">
                            <img
                                v-if="item.product?.image"
                                :src="item.product.image"
                                alt="Gambar Produk"
                                class="h-14 w-14 rounded-lg object-cover shadow-md"
                            />
                            <div class="flex-1">
                                <p class="text-sm text-gray-900">{{ item.product?.name ?? 'Produk Tidak Dikenal' }}</p>
                                <p class="text-sm text-gray-600">Jumlah: {{ item.quantity }}</p>
                                <p class="text-sm text-gray-600">Ukuran: {{ item.size_id }} - {{ item.uom_id }}</p>
                            </div>
                        </div>
                        <p class="text-sm font-bold text-gray-900">{{ formatRupiah(item.price_sell ?? 0) }}</p>
                    </div>

                    <div class="mt-4 space-y-4 border-gray-300 pt-6">
                        <div class="flex items-center justify-between text-sm font-semibold text-gray-800">
                            <span>Subtotal:</span>
                            <span>{{ formatRupiah(subtotalAmount) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm font-semibold text-gray-800">
                            <span>Diskon:</span>
                            <span>- {{ formatRupiah(discountAmount) }}</span>
                        </div>
                        <div class="flex items-center justify-between border-t border-gray-200 pt-4 text-sm font-bold text-gray-900">
                            <span>Total:</span>
                            <span>{{ formatRupiah(totalAmount) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Metode Pembayaran -->
                <div class="rounded-2xl bg-white p-8 shadow-xl lg:col-span-1">
                    <h2 class="mb-6 text-sm font-bold text-gray-800">Metode Pembayaran</h2>
                    <div class="space-y-4">
                        <label
                            class="flex cursor-pointer items-center rounded-sm border border-gray-200 p-4 shadow-sm transition-all duration-200 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:ring-2 has-[:checked]:ring-blue-500"
                        >
                            <input
                                type="radio"
                                name="paymentMethod"
                                value="cod_store"
                                v-model="selectedPaymentMethod"
                                class="form-radio h-5 w-5 text-blue-600"
                            />
                            <span class="ml-4 text-sm font-medium text-gray-800">Bayar di Toko</span>
                        </label>
                        <div class="rounded border border-green-600 p-1 text-xs text-black" v-html="settingMessage"></div>

                        <label
                            class="flex cursor-pointer items-center rounded-sm border border-gray-200 p-4 shadow-sm transition-all duration-200 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:ring-2 has-[:checked]:ring-blue-500"
                        >
                            <input
                                type="radio"
                                name="paymentMethod"
                                value="bank_transfer"
                                v-model="selectedPaymentMethod"
                                class="form-radio h-5 w-5 text-blue-600"
                            />
                            <span class="ml-4 text-sm font-medium text-gray-800">Transfer Bank</span>
                        </label>
                        <div class="rounded border border-green-600 p-1 text-xs text-black" v-html="settingMessage2"></div>

                        <div class="mt-8 flex justify-end space-x-4">
                            <Button @click="goBackToCart" class="w-full bg-orange-600 text-white py-2 rounded-md hover:bg-orange-700 focus:ring-2 focus:ring-indigo-500">Batal</Button>
                            <Button @click="confirmCheckout" class="w-full bg-indigo-600 text-white py-2 h-10 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">Proses</Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pesan Keranjang Kosong -->
            <div v-else class="text-center text-gray-600">
                <p>Keranjang Anda kosong. Silakan tambahkan produk terlebih dahulu sebelum checkout.</p>
                <Button class="mt-4" @click="goBackToCart">Kembali ke Keranjang</Button>
            </div>
        </div>
    </AppLayout>
</template>

