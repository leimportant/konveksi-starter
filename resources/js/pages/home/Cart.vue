<template>

  <Head title="Belanja" />
  <AppLayout>

    <!-- Product Catalog -->
    <section class="flex flex-col md:col-span-4 lg:col-span-4">
      <div class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <!-- Product Catalog -->
        <section class="space-y-4 w-full p-4 bg-white">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-2 mb-4">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-500">Katalog Produk</h2>
            <input type="text" v-model="searchText" @input="onSearchInput" placeholder="Cari produk..."
              class="border rounded px-3 py-1 text-sm w-full sm:w-52 sm:ml-auto" />
          </div>


          <!-- Product Grid -->
          <div class="grid grid-cols-2 sm:grid-cols-5 xl:grid-cols-4 gap-1">
            <div v-for="product in products" :key="product.product_id" @click="viewProductDetail(product)"
              class="bg-green-50 hover:bg-green-100 rounded-xl p-3 border border-gray-200 shadow-sm hover:shadow-md transition cursor-pointer"
              :title="`Stock: ${product.qty_stock}`">
              <div class="w-full h-24 mb-2">
                <!-- <img v-if="product.image_path" :src="getImageUrl(product.image_path)" alt="product"
                  class="w-full h-full object-cover rounded-lg cursor-pointer" @click.stop="openFullscreenImage(getImageUrl(product.image_path))" /> -->
                <img v-if="product.image_path" :src="getImageUrl(product.image_path)" alt="product"
                  class="w-full h-full object-cover rounded-lg cursor-pointer" />
                  <div v-else
                  class="w-full h-full bg-gray-200 flex items-center justify-center text-xs text-gray-400 rounded-lg">No
                  Image</div>
              </div>
              <p class="text-sm font-semibold text-gray-900 truncate justify-between">{{ product.product_name }}
                <template v-if="product.discount && product.price">
                  <span v-if="product.discount > 0"
                    class="ml-1 rounded bg-orange-500 px-2 py-0.5 text-[14px] font-bold text-white md:text-xs">
                    - {{ Math.round((product.discount / product.price) * 100) }}%
                  </span>
                </template>
              </p>

              <p class="text-xs text-gray-500">Stock: {{ product.qty_stock }} {{ product.uom_id }}</p>
              <div class="mt-1 text-sm">
                <template v-if="product.discount && product.discount > 0">
                  <p class="line-through text-xs text-gray-400">{{ formatRupiah(product.price) }}</p>
                  <p>
                    {{ formatRupiah(product.price_sell || product.price) }}
                    <br /><span class="text-green-500 text-xs">(Diskon {{ formatRupiah(product.discount) }})</span>
                  </p>

                </template>
                <p v-else class="font-semibold text-gray-700">{{ formatRupiah(product.price) }}</p>
              </div>
            </div>
          </div>

 
        </section>
      </div>
    </section>
    <div class="min-h-screen bg-white p-2 overflow-x-hidden grid gap-8 md:grid-cols-[1fr_400px]">

      <!-- Floating Cart Icon -->
      <div class="hidden md:block absolute top-5 right-8 z-50">
        <button @click="toggleCart" class="relative bg-white p-2 rounded-full shadow hover:shadow-md">
          🛒
          <span v-if="cartQty > 0"
            class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
            {{ cartQty }}
          </span>
        </button>
      </div>

      <div class="block fixed top-5 right-8 z-50 md:hidden">
        <button @click="toggleCart" class="relative bg-white p-2 rounded-full shadow hover:shadow-md">
          🛒
          <span v-if="cartQty > 0"
            class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
            {{ cartQty }}
          </span>
        </button>
      </div>

    </div>

    <!-- Cart Backdrop -->
    <div v-if="showCart" class="fixed inset-0 bg-black bg-opacity-30 z-30 md:hidden" @click="showCart = false" />

    <!-- Cart Sidebar -->
    <aside :class="[
      'fixed inset-y-0 right-0 max-w-full w-80 bg-gray-100 dark:bg-gray-800 dark:text-blue-400 shadow-xl z-40 transform transition-transform duration-300',
      showCart ? 'translate-x-0' : 'translate-x-full'
    ]">

      <h2 class="text-xl font-bold mb-4">Keranjang Belanja</h2>

      <div ref="orderList" class="flex-1 overflow-y-auto space-y-4 pr-1">
        <div v-if="cartItems.length === 0" class="text-gray-400 text-center mt-10">
          Cart is empty
        </div>

        <div v-for="item in cartItems" :key="item.product_id" class="flex items-center gap-3 border-b pb-2">
          <img v-if="item.image_path" :src="getImageUrl(item.image_path)" alt="product"
            class="w-12 h-12 object-cover rounded" />
          <div class="flex-1 min-w-0">
            <p class="font-semibold text-gray-800 text-sm truncate">{{ item.product_name }}</p>

            <p class="text-xs text-gray-400">Stock: {{ item.qty_stock }}</p>
            <p class="text-xs text-gray-800">
              {{ formatRupiah((item.price_sell ?? 0) > 0 ? item.price_sell ?? 0 : item.price ?? 0) }}
            </p>
            <p class="text-xs text-gray-800">Diskon
              {{ formatRupiah((item.discount ?? 0) > 0 ? item.discount ?? 0 : item.discount ?? 0) }}
            </p>
          </div>
          <div class="flex items-center space-x-1">
            <button @click="decreaseQty(item)" :disabled="item.quantity <= 1"
              class="px-2 py-1 bg-gray-500 rounded disabled:opacity-50">-</button>
            <span class="w-8 text-center text-sm text-gray-800 dark:text-gray-500">{{ item.quantity }}</span>
            <button @click="increaseQty(item)" class="px-2 py-1 bg-gray-500 rounded">+</button>
          </div>
          <button @click="removeFromCart(item.cartItemId)"
            class="text-red-500 text-lg hover:text-red-700">&times;</button>
        </div>
      </div>

      <div class="mt-2 pt-4 space-y-2">
        <p class="text-sm font-semibold">Total Diskon: {{ formatRupiah(cartTotalDiscount) }}</p>
        <p class="text-sm font-semibold">Total: {{ formatRupiah(cartTotal) }}</p>
        <button @click="proceedToCheckout"
          class="w-full bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">Lanjut
          Checkout</button>
      </div>
    </aside>

    <!-- Product Detail Modal -->
    <div v-if="showDetailModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 px-4 overflow-y-auto"
      @click.self="closeDetailModal">
      <div class="bg-white rounded-xl p-4 sm:p-6 w-full max-w-sm sm:max-w-md md:max-w-lg lg:max-w-xl relative">
        <button @click="closeDetailModal"
          class="absolute top-2 right-3 text-gray-600 hover:text-gray-900 text-2xl sm:text-xl font-bold">&times;</button>

        <div>
          <!-- Gallery -->
          <div v-if="selectedProduct?.gallery_images && selectedProduct.gallery_images.length > 0">
            <Swiper :slides-per-view="1" :space-between="10" :modules="modules" :pagination="{ clickable: true }"
              :navigation="true" class="mb-4 w-full h-40 sm:h-48 md:h-60 rounded-lg">
              <SwiperSlide v-for="(image, index) in selectedProduct.gallery_images" :key="index">
                <img :src="getImageUrl(image.path)" alt="product gallery image"
                  class="w-full h-full object-cover rounded-lg cursor-pointer" @click="openFullscreenImage(getImageUrl(image.path))" />
              </SwiperSlide>
            </Swiper>
          </div>

          <!-- Single Image -->
          <div v-else-if="selectedProduct?.image_path" class="mb-4 w-full h-40 sm:h-48 md:h-60 rounded-lg">
            <img :src="getImageUrl(selectedProduct.image_path)" alt="product"
              class="w-full h-full object-cover rounded-lg cursor-pointer" @click="openFullscreenImage(getImageUrl(selectedProduct.image_path))" />
          </div>

          <!-- No Image -->
          <div v-else
            class="mb-4 w-full h-40 sm:h-48 md:h-60 bg-gray-200 rounded-lg flex items-center justify-center text-gray-400 text-xs">
            No Image
          </div>

          <!-- Product Name -->
          <h3 class="text-lg text-gray-500 sm:text-xl font-semibold mb-2">
            {{ selectedProduct?.product_name }}
          </h3>

          <!-- Stock & Info -->
          <p class="text-xs sm:text-sm text-gray-600 mb-2">
            Stock: {{ selectedProduct?.qty_stock }} {{ selectedProduct?.uom_id }} - Ukuran {{ selectedProduct?.size_id
            }}
          </p>

          <!-- Price -->
          <p class="mb-3 text-gray-500 sm:text-lg font-semibold text-gray-800">
            <span v-if="selectedProduct?.discount && selectedProduct.discount > 0"
              class="line-through text-gray-400 mr-2">
              {{ formatRupiah(selectedProduct.price) }}
            </span>
            <span>
              {{ formatRupiah(
                (selectedProduct?.price_sell ?? 0) > 0
                  ? selectedProduct?.price_sell ?? 0
                  : selectedProduct?.price ?? 0
              ) }}
            </span>
            <span v-if="selectedProduct?.discount && selectedProduct.discount > 0" class="text-green-500 ml-1">
              (Diskon {{ formatRupiah(selectedProduct.discount ?? 0) }})
            </span>
          </p>

          <!-- Quantity Control -->
          <div class="flex items-center space-x-3 mb-4">
            <button @click="decreaseDetailQty" :disabled="detailQty <= 1"
              class="px-3 py-1 text-gray-800 text-sm bg-gray-200 rounded disabled:opacity-50">-</button>

            <input type="number" min="1" :max="selectedProduct?.qty_stock ?? 1" v-model.number="detailQty"
              class="w-12 sm:w-16 text-center border rounded py-1 text-sm text-gray-800" />


            <button @click="increaseDetailQty" :disabled="detailQty >= (selectedProduct?.qty_stock ?? 1)"
              class="px-3 py-1 text-sm bg-gray-700 rounded disabled:opacity-50">+</button>
          </div>

          <!-- Add to Cart -->
          <button @click="addToCart" :disabled="(selectedProduct?.qty_stock ?? 0) === 0"
            class="w-full bg-blue-600 text-white py-2 sm:py-3 rounded hover:bg-blue-700 disabled:opacity-50 text-sm sm:">
            Tambah Keranjang
          </button>
        </div>
      </div>
    </div>


  </AppLayout>

  <!-- Fullscreen Image Modal -->
  <div v-if="showFullscreenImageModal"
    class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-[9999]"
    @click.self="closeFullscreenImage">
    <div class="relative">
      <img :src="fullscreenImageUrl" alt="Fullscreen Product Image" class="max-w-full max-h-screen object-contain" />
      <button @click="closeFullscreenImage"
        class="absolute top-4 right-4 text-white text-4xl font-bold bg-black bg-opacity-50 rounded-full w-12 h-12 flex items-center justify-center">&times;</button>
    </div>
  </div>
</template>


<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { useCartStore } from '@/stores/useCartStore';
import { useToast } from '@/composables/useToast';
import axios from 'axios';
import { debounce } from '@/lib/debounce';
import { Swiper, SwiperSlide } from 'swiper/vue';
import 'swiper/css';
import 'swiper/css/pagination';
import 'swiper/css/navigation';
import { Pagination, Navigation } from 'swiper/modules';

const modules = [Pagination, Navigation];

const isLoading = ref(false);

interface Product {
  product_id: number; // This is the product ID
  product_name: string;
  uom_id: string;
  size_id: string;
  qty_stock: number;
  image_path: string;
  price: number;
  discount?: number;
  price_sell?: number;
  quantity: number; // This is quantity in cart, not stock
  cartItemId?: number; // Add this to store the cart item's ID
  gallery_images?: { filename: string; path: string }[]; // Add this for multiple images
}

const toast = useToast();
const cartStore = useCartStore();
const products = ref<Product[]>([]);

const searchText = ref('');
const currentPage = ref(1);
const lastPage = ref(1);

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


async function fetchProducts() {
  try {
    const response = await axios.get(`/api/stock?page=${currentPage.value}&search=${searchText.value}`);
    products.value = response.data.data.map((product: Product) => ({
      ...product,
      gallery_images: product.gallery_images || [], // Ensure gallery_images is an array
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
  selectedProduct.value = { ...product, gallery_images: product.gallery_images || [] };
  detailQty.value = 1;
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

const cartQty = computed(() =>
  cartStore.cartItems.reduce((total: number, item) => total + (item.quantity || 0), 0)
);

const addToCart = async () => {
  const product = selectedProduct.value;

  console.log(product);
  if (!product) return;

  // Validasi stok 
  if (detailQty.value > product.qty_stock) {
    toast.error('Jumlah melebihi stok tersedia');
    return;
  }

  // Hitung harga (gunakan price_sell jika > 0, jika tidak pakai price) 
  const price: number = product.price ?? 0;

  const discount: number = product.discount ?? 0;

  // Gunakan price_sell jika ada, jika tidak gunakan price - discount
  const price_sell: number = (product.price_sell ?? 0) > 0
    ? product.price_sell ?? 0
    : price - discount;

  try {
    await cartStore.addToCart(
      product.product_id ?? 0,
      detailQty.value,
      product.size_id ?? "",
      product.uom_id ?? "PCS",
      price,
      discount,
      price_sell
    );
    toast.success('Product added to cart');
    closeDetailModal();
  } catch {
    toast.error(cartStore.error || 'Failed to add product to cart');
  }
};



const increaseDetailQty = debounce(async () => {
  isLoading.value = true;
  if (selectedProduct.value && detailQty.value < selectedProduct.value.qty_stock) {
    detailQty.value++;
  }
  isLoading.value = false;
}, 300);

const decreaseDetailQty = debounce(async () => {
  isLoading.value = true;
  if (detailQty.value > 1) {
    detailQty.value--;
  }
  isLoading.value = false;
}, 300);

const increaseQty = debounce(async (item: Product) => {

  const price_sell = item.price - (item.discount ?? 0);
  isLoading.value = true;
  if (item.quantity < item.qty_stock) {
    try {
      await cartStore.addToCart(item.product_id, 1, item.size_id, item.uom_id, item.price, item.discount ?? 0, item.price_sell ?? price_sell);
    } catch {
      toast.error('Error increasing quantity');
    }
  } else {
    toast.error('Stok maksimum tercapai');
  }
  isLoading.value = false;
}, 300);

const decreaseQty = debounce(async (item: Product) => {
  isLoading.value = true;
  if (item.quantity > 1) {
    try {
      const price_sell = item.price - (item.discount ?? 0);
      await cartStore.addToCart(item.product_id ?? 0, -1, item.size_id, item.uom_id, item.price, item.discount ?? 0, item.price_sell ?? price_sell);
    } catch {
      toast.error('Error decreasing quantity');
    }
  }
  isLoading.value = false;
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
  return cartStore.cartItems.map(item => {

    const product = products.value.find(p => p.product_id === item.product_id);
    return {
      ...product, // Spread product properties
      ...item,    // Spread cart item properties (will overwrite product properties if names conflict)
      id: item.product_id, // Ensure 'id' is the product ID for this 'Product' type
      cartItemId: item.id, // Store the cart item's actual ID here
      quantity: item.quantity, // Ensure quantity is from cart item
      price: item.price ?? 0,
      price_sell: item.price_sell ?? 0,
      discount: item.discount ?? 0,
      size_id: item.size_id,
      uom_id: item.uom_id
    } as Product;
  }).filter(Boolean);
});

const cartTotal = computed(() => {
  return cartStore.cartItems.reduce((sum, item) => sum + (item.price_sell ?? 0 > 0 ? item.price_sell ?? 0 : item.price) * item.quantity, 0);
});

const cartTotalDiscount = computed(() => {
  return cartStore.cartItems.reduce((sum, item) => {
    const originalPrice = item.price || 0;
    const finalPricePerItem = (item.price_sell ?? 0) > 0 ? (item.price_sell ?? 0) : (item.price || 0);
    const discountPerItem = originalPrice - finalPricePerItem;
    return sum + (discountPerItem * (item.quantity || 0));
  }, 0);
});




const proceedToCheckout = () => {
  console.log('Proceeding to checkout');
  router.visit('/checkout');
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
