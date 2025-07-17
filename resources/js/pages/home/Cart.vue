<template>

    <Head title="Belanja" />
    <AppLayout>
        <!-- Product Catalog -->
        <section class="flex flex-col md:col-span-4 lg:col-span-4">
            <div class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <!-- Product Catalog -->
                <section class="w-full space-y-4 bg-white p-4">
                    <div class="mb-4 flex flex-col gap-2 px-2 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-500">Katalog Produk</h2>
                        <input type="text" v-model="searchText" @input="onSearchInput" placeholder="Cari produk..."
                            class="w-full rounded border px-3 py-1 text-sm sm:ml-auto sm:w-52" />
                    </div>

                    <!-- Product Grid -->
                    <div class="grid grid-cols-2 gap-1 sm:grid-cols-5 xl:grid-cols-4">
                        <div v-for="product in products" :key="product.product_id" @click="viewProductDetail(product)"
                            class="cursor-pointer rounded-xl border border-gray-200 bg-green-50 p-3 shadow-sm transition hover:bg-green-100 hover:shadow-md"
                            :title="`${product.product_name} - ${formatRupiah(product.price)}`">
                            <div class="mb-2 h-24 w-full">
                                <!-- <img v-if="product.image_path" :src="getImageUrl(product.image_path)" alt="product"
                  class="w-full h-full object-cover rounded-lg cursor-pointer" @click.stop="openFullscreenImage(getImageUrl(product.image_path))" /> -->
                                <img v-if="product.image_path" :src="getImageUrl(product.image_path)" alt="product"
                                    class="h-full w-full cursor-pointer rounded-lg object-cover" />
                                <div v-else
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-gray-200 text-xs text-gray-400">
                                    No Image
                                </div>
                            </div>
                            <p class="justify-between truncate text-sm font-semibold text-gray-900">
                                {{ product.product_name }}
                                <template v-if="product.discount && product.price">
                                    <span v-if="product.discount > 0"
                                        class="ml-1 rounded bg-orange-500 px-2 py-0.5 text-[14px] font-bold text-white md:text-xs">
                                        - {{ Math.round((product.discount / product.price) * 100) }}%
                                    </span>
                                </template>
                            </p>

                            <div class="mt-1 text-sm">
                                <template v-if="product.discount && product.discount > 0">
                                    <p class="text-xs text-gray-400 line-through">{{ formatRupiah(product.price) }}</p>
                                    <p>
                                        {{ formatRupiah(product.price_sell || product.price) }}
                                        <br /><span class="text-xs text-green-500">(Diskon {{
                                            formatRupiah(product.discount) }})</span>
                                    </p>
                                </template>
                                <p v-else class="font-semibold text-gray-700">{{ formatRupiah(product.price) }}</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </section>
        <div class="grid min-h-screen gap-8 overflow-x-hidden bg-white p-2 md:grid-cols-[1fr_400px]">
            <!-- Floating Cart Icon -->
            <div class="absolute right-8 top-5 z-50 hidden md:block">
                <button @click="toggleCart" class="relative rounded-full bg-white p-2 shadow hover:shadow-md">
                    🛒
                    <span v-if="cartQty > 0"
                        class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs text-white">
                        {{ cartQty }}
                    </span>
                </button>
            </div>

            <div class="fixed right-8 top-5 z-50 block md:hidden">
                <button @click="toggleCart" class="relative rounded-full bg-white p-2 shadow hover:shadow-md">
                    🛒
                    <span v-if="cartQty > 0"
                        class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs text-white">
                        {{ cartQty }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Cart Backdrop -->
        <div v-if="showCart" class="fixed inset-0 z-30 bg-black bg-opacity-30 md:hidden" @click="showCart = false" />

        <!-- Cart Sidebar -->
        <aside :class="[
            'fixed inset-y-0 right-0 z-40 w-80 max-w-full transform shadow-xl transition-transform duration-300',
            showCart ? 'translate-x-0' : 'translate-x-full',
        ]" class="flex flex-col h-screen bg-gray-100 dark:bg-gray-800 dark:text-blue-400">
            <!-- Header -->
            <div class="px-4 py-4 border-b border-gray-300 dark:border-gray-600">
                <h2 class="text-xl font-bold">Keranjang Belanja</h2>
            </div>

            <!-- Order Items (scrollable area) -->
            <div ref="orderList" class="flex-1 overflow-y-auto px-4 py-3 space-y-4" style="min-height: 0">
                <div v-if="cartItems.length === 0" class="mt-10 text-center text-gray-400">
                    Cart is empty
                </div>

                <div v-for="item in cartItems" :key="item.product_id"
                    class="flex items-center gap-3 border-b pb-3 border-gray-300 dark:border-gray-700">
                    <img v-if="item.image_path" :src="getImageUrl(item.image_path)" alt="product"
                        class="h-12 w-12 rounded object-cover" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100">{{ item.product_name
                            }}</p>
                        <p class="text-xs text-gray-500">Stock: {{ item.qty_available }}</p>
                        <p class="text-xs text-gray-800 dark:text-gray-200">
                            {{ formatRupiah((item.price_sell ?? 0) > 0 ? (item.price_sell ?? 0) : (item.price ?? 0)) }}
                        </p>
                        <p class="text-xs text-gray-800 dark:text-gray-200">Size: {{ item.size_id }}</p>
                        <p class="text-xs text-gray-800 dark:text-gray-200">Diskon: {{ formatRupiah(item.discount ?? 0)
                            }}</p>
                    </div>
                    <div class="flex items-center space-x-1">
                        <button @click="decreaseQty(item)" :disabled="item.quantity <= 1"
                            class="rounded bg-gray-500 px-2 py-1 text-white disabled:opacity-50">-</button>
                        <span class="w-8 text-center text-sm text-gray-800 dark:text-gray-100">{{ item.quantity
                            }}</span>
                        <button @click="increaseQty(item)" class="rounded bg-gray-500 px-2 py-1 text-white">+</button>
                    </div>
                    <button @click="removeFromCart(item.cartItemId)"
                        class="text-lg text-red-500 hover:text-red-700">&times;</button>
                </div>
            </div>

            <!-- Footer always visible -->
            <div class="px-4 py-4 border-t border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900">
                <p class="text-sm font-semibold">Total Diskon: {{ formatRupiah(cartTotalDiscount) }}</p>
                <p class="text-sm font-semibold">Total: {{ formatRupiah(cartTotal) }}</p>
                <button @click="proceedToCheckout"
                    class="mt-2 w-full rounded-md bg-indigo-600 py-2 text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    Lanjut Checkout
                </button>
            </div>
        </aside>





        <!-- Product Detail Modal -->
        <div v-if="showDetailModal"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black bg-opacity-50 px-4"
            @click.self="closeDetailModal">
            <div
                class="relative flex max-h-[90vh] w-full max-w-sm flex-col rounded-xl bg-white sm:max-w-md sm:p-0 md:max-w-lg lg:max-w-xl">
                <button @click="closeDetailModal"
                    class="absolute right-3 top-2 text-2xl font-bold text-gray-600 hover:text-gray-900 sm:text-xl">
                    &times;
                </button>
                <!-- Scrollable content  -->
                <div class="flex-1 overflow-y-auto p-4 sm:p-6">
                    <div v-if="selectedProduct">
                        <!-- Gallery -->
                        <div v-if="selectedProduct?.gallery_images && selectedProduct.gallery_images.length > 0">
                            <Swiper :slides-per-view="1" :space-between="10" :modules="modules"
                                :pagination="{ clickable: true }" :navigation="true"
                                class="mb-4 h-40 w-full rounded-lg sm:h-48 md:h-60">
                                <SwiperSlide v-for="(image, index) in selectedProduct.gallery_images" :key="index">
                                    <template v-if="isImage(image.extension)">
                                        <img :src="getImageUrl(image.path)"
                                            :alt="image.filename || 'product gallery image'"
                                            class="h-full w-full cursor-pointer rounded-lg object-cover transition hover:scale-105"
                                            @click="openFullscreenImage(getImageUrl(image.path))" />
                                    </template>
                                    <template v-else-if="isVideo(image.extension)">
                                        <video :src="getImageUrl(image.path)" controls
                                            class="h-full w-full rounded-lg object-cover"></video>
                                    </template>
                                </SwiperSlide>
                            </Swiper>
                        </div>

                        <!-- Single Image -->
                        <div v-else-if="selectedProduct?.image_path"
                            class="mb-4 h-40 w-full rounded-lg sm:h-48 md:h-60">
                            <img :src="getImageUrl(selectedProduct.image_path)" alt="product"
                                class="h-full w-full cursor-pointer rounded-lg object-cover"
                                @click="openFullscreenImage(getImageUrl(selectedProduct.image_path))" />
                        </div>

                        <!-- No Image -->
                        <div v-else
                            class="mb-4 flex h-40 w-full items-center justify-center rounded-lg bg-gray-200 text-xs text-gray-400 sm:h-48 md:h-60">
                            No Image
                        </div>

                        <!-- Product Name -->
                        <h3 class="mb-2 text-lg font-semibold text-gray-500 sm:text-xl">
                            {{ selectedProduct?.product_name }}
                        </h3>

                        <!-- Stock & Info -->

                        <!-- Price -->
                        <!-- <p class="mb-3 font-semibold text-gray-500 text-gray-800 sm:text-lg">
                            <span v-if="selectedProduct?.discount && selectedProduct.discount > 0" class="mr-2 text-gray-400 line-through">
                                {{ formatRupiah(selectedProduct.price) }}
                            </span>
                            <span>
                                {{
                                    formatRupiah(
                                        (selectedProduct?.price_sell ?? 0) > 0 ? (selectedProduct?.price_sell ?? 0) : (selectedProduct?.price ?? 0),
                                    )
                                }}
                            </span>
                            <span v-if="selectedProduct?.discount && selectedProduct.discount > 0" class="ml-1 text-green-500">
                                (Diskon {{ formatRupiah(selectedProduct.discount ?? 0) }})
                            </span>
                        </p> -->
                        <!-- Pilihan Varian (misal: Merah, Kuning, Hijau) -->
                        <!-- Variant -->
                        <div v-if="selectedProduct?.variant !== 'all'" class="mb-4">
                            <h4 class="mb-1 text-sm font-semibold text-gray-700">Variant</h4>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="variant in uniqueVariants" :key="variant" @click="
                                    () => {
                                        selectedVariant = variant;
                                        selectedSize = null;
                                    }
                                " :class="[
                                    'rounded-full border px-3 py-1 text-sm font-semibold transition',
                                    selectedVariant === variant
                                        ? 'border-indigo-600 bg-indigo-600 text-white'
                                        : 'border-gray-300 bg-gray-200 text-gray-800 hover:bg-gray-300',
                                ]">
                                    {{ variant }}
                                </button>
                            </div>
                        </div>

                        <!-- Size -->
                        <div v-if="selectedVariant || selectedProduct?.variant === 'all'" class="mb-4">
                            <h4 class="mb-1 text-sm font-semibold text-gray-700">Ukuran</h4>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="size in sizesForSelectedVariant" :key="size" @click="selectedSize = size"
                                    :class="[
                                        'rounded-full border px-3 py-1 text-sm font-semibold transition',
                                        selectedSize === size
                                            ? 'border-green-600 bg-green-600 text-white'
                                            : 'border-gray-300 bg-gray-200 text-gray-800 hover:bg-gray-300',
                                    ]">
                                    {{ size }}
                                </button>
                            </div>
                        </div>

                        <div v-if="getSelectedItemDetail" class="mb-4 text-sm text-gray-700">
                            <p>
                                Stok: <strong>{{ getSelectedItemDetail.qty_available }}</strong>
                            </p>
                            <p>

                                <span v-if="!getSelectedItemDetail.price || getSelectedItemDetail.price <= 0">
                                    Harga Belum di Setting
                                </span>
                                <template v-else>
                                    <span v-if="(getSelectedItemDetail.discount ?? 0) > 0"
                                        class="mr-2 text-gray-400 line-through">
                                        Harga {{ formatRupiah(getSelectedItemDetail.price) }}
                                    </span> <br />
                                    <span class="font-bold">
                                        Harga Diskon {{ formatRupiah(getSelectedItemDetail.price_sell ||
                                            getSelectedItemDetail.price) }}
                                    </span><br />
                                    <span v-if="(getSelectedItemDetail.discount ?? 0) > 0"
                                        class="text-green-600 text-xs">
                                        (Diskon: {{ formatRupiah(getSelectedItemDetail.discount ?? 0) }})
                                    </span>
                                </template>
                            </p>
                        </div>

                        <div class="w-full" v-html="selectedProduct?.product_description"></div>

                        <!-- Quantity Control -->
                        <div class="mb-4 flex items-center space-x-3">
                            <button @click="decreaseDetailQty" :disabled="detailQty <= 1"
                                class="rounded bg-gray-200 px-3 py-1 text-sm text-gray-800 disabled:opacity-50">
                                -
                            </button>

                            <input type="number" min="1" :max="getSelectedItemDetail?.qty_available ?? 1"
                                v-model.number="detailQty"
                                class="w-12 rounded border py-1 text-center text-sm text-gray-800 sm:w-16" />

                            <button @click="increaseDetailQty"
                                :disabled="detailQty >= (getSelectedItemDetail?.qty_available ?? 1)"
                                class="rounded px-3 py-1 text-sm disabled:opacity-50">
                                +
                            </button>
                        </div>
                    </div>

                    <!-- Add to Cart -->
                    <div class="border-t p-4">
                        <button @click="addToCart"
                            :disabled="(selectedProduct?.qty_available ?? 0) === 0 || (getSelectedItemDetail?.price_sell ?? 0) === 0"
                            class="w-full rounded bg-blue-600 py-2 text-sm text-white hover:bg-blue-700 disabled:opacity-50 sm:py-3">
                            Tambah Keranjang
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <!-- Fullscreen Image Modal -->
    <div v-if="showFullscreenImageModal"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-75"
        @click.self="closeFullscreenImage">
        <div class="relative">
            <img :src="fullscreenImageUrl" alt="Fullscreen Product Image"
                class="max-h-screen max-w-full object-contain" />
            <button @click="closeFullscreenImage"
                class="absolute right-4 top-4 flex h-12 w-12 items-center justify-center rounded-full bg-black bg-opacity-50 text-4xl font-bold text-white">
                &times;
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { debounce } from '@/lib/debounce';
import { useCartStore } from '@/stores/useCartStore';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import { Navigation, Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { computed, onMounted, ref } from 'vue';

const modules = [Pagination, Navigation];



interface SizeVariant {
    size_id: string;
    variant: string;
    qty_stock: number;
    qty_in_cart: number;
    qty_available: number;
    price: number;
    price_sell?: number;
    discount?: number;
}

interface Product {
    product_id: number; // This is the product ID
    product_name: string;
    product_description: string;
    uom_id: string;
    size_id: string;
    qty_stock: number;
    qty_available: number;
    image_path: string;
    price: number;
    discount?: number;
    price_sell?: number;
    location_id?: number; // Add location_id
    variant?: string;
    sloc_id?: string; // Add sloc_id
    sizes: SizeVariant[];
    quantity: number; // This is quantity in cart, not stock
    cartItemId?: number; // Add this to store the cart item's ID
    gallery_images?: { filename: string; path: string; extension: string }[]; // Add this for multiple images
}

const toast = useToast();
const cartStore = useCartStore();
const products = ref<Product[]>([]);

const searchText = ref('');
const currentPage = ref(1);
const lastPage = ref(1);
const selectedVariant = ref<string | null>(null);
const selectedSize = ref<string | null>(null);

const orderList = ref<HTMLElement | null>(null);

const showDetailModal = ref(false);
const selectedProduct = ref<Product | null>(null);
const detailQty = ref(1);
const showFullscreenImageModal = ref(false);
const fullscreenImageUrl = ref('');

const showCart = ref(false);
const toggleCart = () => (showCart.value = !showCart.value);

const getImageUrl = (path: string) => {
    if (!path) return '';
    if (path.startsWith('storage/')) return '/' + path;
    if (path.startsWith('/storage/')) return path;
    return '/storage/' + path;
};

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

const uniqueVariants = computed(() => {
    if (!selectedProduct.value?.sizes) return [];
    const variants = selectedProduct.value.sizes.map((item) => item.variant);
    return [...new Set(variants)];
});

const sizesForSelectedVariant = computed(() => {
    if (!selectedProduct.value) return [];
    if (selectedProduct.value.variant === 'all') {
        return selectedProduct.value.sizes.map((item) => item.size_id);
    }
    if (!selectedVariant.value) return [];
    return selectedProduct.value.sizes.filter((item) => item.variant === selectedVariant.value).map((item) => item.size_id);
});

const getSelectedItemDetail = computed(() => {
    if (!selectedSize.value || !selectedProduct.value) return null;
    if (selectedProduct.value.variant === 'all') {
        return selectedProduct.value.sizes.find((item) => item.size_id === selectedSize.value);
    }
    if (!selectedVariant.value) return null;
    return selectedProduct.value.sizes.find((item) => item.variant === selectedVariant.value && item.size_id === selectedSize.value);
});

async function fetchProducts() {
    try {
        const response = await axios.get(`/api/stock?page=${currentPage.value}&search=${searchText.value}`);
        products.value = response.data.data.map((product: Product) => ({
            ...product,
            gallery_images: product.gallery_images ? product.gallery_images.map(img => ({ ...img, extension: img.path.split('.').pop() || '' })) : [],
        }));
        lastPage.value = response.data.last_page;
        await cartStore.fetchCartItems();
    } catch (error) {
        console.error('Error fetching products:', error);
        toast.error('Failed to fetch products');
    }
}

function onSearchInput() {
    currentPage.value = 1;
    fetchProducts();
}

function viewProductDetail(product: Product) {
    selectedProduct.value = {
        ...product,
        gallery_images: product.gallery_images ? product.gallery_images.map(img => ({ ...img, extension: img.path.split('.').pop() || '' })) : [],
    };
    detailQty.value = 1;
    selectedVariant.value = product.variant === 'all' ? 'all' : null;
    selectedSize.value = null;
    showDetailModal.value = true;
}

function closeDetailModal() {
    showDetailModal.value = false;
    selectedProduct.value = null;
    detailQty.value = 1;
}

function openFullscreenImage(imageUrl: string) {
    fullscreenImageUrl.value = imageUrl;
    showFullscreenImageModal.value = true;
}

function closeFullscreenImage() {
    showFullscreenImageModal.value = false;
    fullscreenImageUrl.value = '';
}

const cartQty = computed(() => cartStore.cartItems.reduce((total: number, item) => total + (item.quantity || 0), 0));

const addToCart = async () => {
    const productDetail = getSelectedItemDetail.value;

    if (!productDetail) {
        toast.error('Detail produk tidak ditemukan.');
        return;
    }

    if (!selectedSize.value) {
        toast.error('Harap pilih ukuran terlebih dahulu.');
        return;
    }

    // Validasi stok
    if (detailQty.value > productDetail.qty_available) {
        toast.error('Jumlah melebihi stok tersedia');
        return;
    }

    // Hitung harga (gunakan price_sell jika > 0, jika tidak pakai price)
    const price: number = productDetail.price ?? 0;

    const discount: number = productDetail.discount ?? 0;

    // Gunakan price_sell jika ada, jika tidak gunakan price - discount
    const price_sell: number = (productDetail.price_sell ?? 0) > 0 ? (productDetail.price_sell ?? 0) : price - discount;

    try {
        await cartStore.addToCart(
            selectedProduct.value?.product_id ?? 0, // Use selectedProduct for product_id
            detailQty.value,
            productDetail.size_id ?? '',
            selectedProduct.value?.uom_id ?? 'PCS', // Use selectedProduct for uom_id
            price,
            discount,
            price_sell,
            selectedProduct.value?.location_id ?? 2, // Use selectedProduct for location_id
            selectedProduct.value?.variant ?? 'all', // Use selectedProduct for variant
            selectedProduct.value?.sloc_id ?? '', // Use selectedProduct for sloc_id
        );
        toast.success('Product added to cart');
        await fetchProducts();
        closeDetailModal();
    } catch {
        toast.error(cartStore.error || 'Failed to add product to cart');
    }
};


const increaseDetailQty = () => {
    if (selectedProduct.value && detailQty.value < (getSelectedItemDetail.value?.qty_available ?? 0)) {
        detailQty.value++;
    }
};

const decreaseDetailQty = () => {
    if (detailQty.value > 1) {
        detailQty.value--;
    }
};


const increaseQty = debounce(async (item: Product) => {
    const price_sell = item.price - (item.discount ?? 0);

    if (item.quantity < item.qty_available) {
        try {
            await cartStore.addToCart(
                item.product_id ?? 0,
                item.quantity + 1,
                item.size_id ?? '',
                item.uom_id ?? 'PCS',
                item.price,
                item.discount ?? 0,
                item.price_sell ?? price_sell,
                item.location_id ?? 1,
                item.variant ?? 'all',
                item.sloc_id ?? 'GS00',
            );
        } catch {
            toast.error('Error increasing quantity');
        }
    } else {
        toast.error('Stok maksimum tercapai');
    }
}, 300);

const decreaseQty = debounce(async (item: Product) => {
    if (item.quantity > 1) {
        try {
            const price_sell = item.price - (item.discount ?? 0);
            await cartStore.addToCart(
                item.product_id ?? 0,
                item.quantity - 1,
                item.size_id ?? '',
                item.uom_id ?? 'PCS',
                item.price,
                item.discount ?? 0,
                item.price_sell ?? price_sell,
                item.location_id ?? 0,
                item.variant ?? '',
                item.sloc_id ?? '',
            );
        } catch {
            toast.error('Error decreasing quantity');
        }
    }
}, 300);

const removeFromCart = async (cartItemId: number | undefined) => {
    try {
        await cartStore.removeFromCart(cartItemId ?? 0);
        toast.success('Item removed');
    } catch {
        toast.error(cartStore.error || 'Failed to remove item');
    }
};

const cartItems = computed(() => {
    return cartStore.cartItems
        .map((item) => {
            const product = products.value.find((p) => p.product_id === item.product_id);
            return {
                ...product, // Spread product properties
                ...item, // Spread cart item properties (will overwrite product properties if names conflict)
                id: item.product_id, // Ensure 'id' is the product ID for this 'Product' type
                cartItemId: item.id, // Store the cart item's actual ID here
                quantity: item.quantity, // Ensure quantity is from cart item
                price: item.price ?? 0,
                price_sell: item.price_sell ?? 0,
                discount: item.discount ?? 0,
                size_id: item.size_id,
                uom_id: item.uom_id,
            } as Product;
        })
        .filter(Boolean);
});

const cartTotal = computed(() => {
    return cartStore.cartItems.reduce((sum, item) => sum + ((item.price_sell ?? 0 > 0) ? (item.price_sell ?? 0) : item.price) * item.quantity, 0);
});

const cartTotalDiscount = computed(() => {
    return cartStore.cartItems.reduce((sum, item) => {
        const originalPrice = item.price || 0;
        const finalPricePerItem = (item.price_sell ?? 0) > 0 ? (item.price_sell ?? 0) : item.price || 0;
        const discountPerItem = originalPrice - finalPricePerItem;
        return sum + discountPerItem * (item.quantity || 0);
    }, 0);
});

const proceedToCheckout = () => {
    console.log('Proceeding to checkout');
    router.visit('/checkout');
};

const isImage = (extension: string | undefined) => {
    if (!extension) return false;
    const imgExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
    return imgExtensions.includes(extension.toLowerCase());
};

const isVideo = (extension: string | undefined) => {
    if (!extension) return false;
    const videoExtensions = ['mp4', 'webm', 'ogg'];
    return videoExtensions.includes(extension.toLowerCase());
};

onMounted(async () => {
    showCart.value = false; // Ensure cart is closed on mount
    await fetchProducts();
});
</script>

<style scoped>
/* Mobile optimizations */
@media (max-width: 768px) {
    aside {
        width: 100% !important;
        border-radius: 1rem;
        padding-top: 20px;
        padding-bottom: 20px;
        padding-left: 5px;
        padding-right: 5px;
        height: fit-content;
        top: 4rem;
    }

    .fixed-cart-toggle {
        bottom: 2rem;
        right: 2rem;
        left: 2rem;
    }
}

/* Smooth cart drawer transition */
aside {
    padding-top: 20px;
    padding-left: 5px;
    padding-right: 5px;
    transition: transform 0.3s ease-in-out;
}

/* Improve modal backdrop on mobile */
.cart-backdrop {
    backdrop-filter: blur(4px);
}
</style>
