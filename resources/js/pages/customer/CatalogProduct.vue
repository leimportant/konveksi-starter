<template>
  <Head title="Shopping Product" />
  <AppLayout>
    <div class="p-2 md:p-4 bg-gray-50 min-h-screen flex gap-6">

      <!-- Product Catalog -->
      <div class="flex-1">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-xl font-semibold text-gray-800">Available Products</h2>
          <input
            type="text"
            v-model="searchText"
            @input="onSearchInput"
            placeholder="Search products..."
            class="border rounded px-3 py-1 text-sm w-48"
          />
        </div>
        <div class="grid grid-cols-4 sm:grid-cols-6 xl:grid-cols-4 gap-2">
          <div
            v-for="product in products"
            :key="product.id"
            class="bg-white rounded-2xl p-4 shadow hover:shadow-md transition cursor-pointer border border-gray-200"
            @click="viewProductDetail(product)"
            :title="'Stock: ' + product.qty_stock"
          >
            <img
              v-if="product.image_path"
              :src="getImageUrl(product.image_path)"
              alt="product image"
              class="mb-2 w-full h-24 object-cover rounded-lg"
            />
            <div
              v-else
              class="mb-2 w-full h-24 bg-gray-200 rounded-lg flex items-center justify-center text-gray-400 text-xs"
            >
              No Image
            </div>
            <p class="font-semibold text-xs text-gray-900 truncate">{{ product.product_name }}</p>
            <p class="text-xs text-gray-400">Stock: {{ product.qty_stock }} / {{ product.uom_id }}</p>
            <div class="mt-1">
              <p v-if="product.discount && product.discount > 0">
                <span class="text-xs text-gray-400 line-through block">
                  {{ formatRupiah(product.price) }}
                </span>
                <span v-if="product.price_sell !== undefined">
                  {{ formatRupiah(product.price_sell) }}
                </span>
                <span class="text-green-500 text-xs">
                  (Disc {{ formatRupiah(product.discount ?? 0) }})
                </span>
              </p>
              <p v-else class="text-sm font-semibold text-gray-700">
                {{ formatRupiah(product.price) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center mt-4 space-x-2">
          <button
            @click="prevPage"
            :disabled="currentPage === 1"
            class="px-3 py-1 border rounded disabled:opacity-50"
          >
            Prev
          </button>
          <span class="px-3 py-1 border rounded bg-white">
            Page {{ currentPage }} of {{ lastPage }}
          </span>
          <button
            @click="nextPage"
            :disabled="currentPage === lastPage"
            class="px-3 py-1 border rounded disabled:opacity-50"
          >
            Next
          </button>
        </div>
      </div>

      <!-- Cart Sidebar -->
      <div class="w-96 bg-white rounded-xl shadow p-4 flex flex-col">
        <h2 class="text-xl font-semibold mb-4">Cart</h2>

        <div
          ref="orderList"
          class="flex-1 overflow-y-auto border rounded p-2 space-y-3 max-h-[500px]"
        >
          <div
            v-if="selectedProducts.length === 0"
            class="text-gray-400 text-center mt-10"
          >
            Cart is empty
          </div>

          <div
            v-for="item in selectedProducts"
            :key="item.id"
            class="flex items-center space-x-3 border-b pb-2"
          >
            <img
              v-if="item.image_path"
              :src="getImageUrl(item.image_path)"
              alt="product"
              class="w-14 h-14 object-cover rounded"
            />
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-sm truncate">{{ item.product_name }}</p>
              <p class="text-xs text-gray-400">Stock: {{ item.qty_stock }}</p>
              <p class="text-xs text-gray-700">
                Price: 
                <span v-if="item.discount && item.discount > 0" class="line-through text-gray-400 mr-1">
                  {{ formatRupiah(item.price) }}
                </span>
                <span>
                  {{ formatRupiah(item.price_sell ?? item.price) }}
                </span>
              </p>
            </div>
            <div class="flex items-center space-x-1">
              <button
                @click="decreaseQty(item)"
                class="px-2 py-1 bg-gray-200 rounded disabled:opacity-50"
                :disabled="item.quantity <= 1"
                aria-label="Decrease quantity"
              >-</button>
              <span class="w-6 text-center">{{ item.quantity }}</span>
              <button
                @click="increaseQty(item)"
                class="px-2 py-1 bg-gray-200 rounded"
                aria-label="Increase quantity"
              >+</button>
            </div>
            <button
              @click="removeFromCart(item.id)"
              class="text-red-500 hover:text-red-700"
              aria-label="Remove item"
            >
              &times;
            </button>
          </div>
        </div>

        <div class="mt-4 border-t pt-4">
          <p class="font-semibold text-lg">
            Total: {{ formatRupiah(cartTotal) }}
          </p>
          <button
            @click="checkout"
            :disabled="selectedProducts.length === 0"
            class="mt-3 w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 disabled:opacity-50"
          >
            Checkout
          </button>
        </div>
      </div>

      <!-- Product Detail Modal -->
      <div
        v-if="showDetailModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        @click.self="closeDetailModal"
      >
        <div class="bg-white rounded-xl p-6 w-96 max-w-full relative">
          <button
            class="absolute top-2 right-3 text-gray-600 hover:text-gray-900 text-xl font-bold"
            @click="closeDetailModal"
            aria-label="Close detail modal"
          >
            &times;
          </button>

          <div>
            <img
              v-if="selectedProduct?.image_path"
              :src="getImageUrl(selectedProduct.image_path)"
              alt="product image"
              class="mb-4 w-full h-48 object-cover rounded-lg"
            />
            <div
              v-else
              class="mb-4 w-full h-48 bg-gray-200 rounded-lg flex items-center justify-center text-gray-400 text-xs"
            >
              No Image
            </div>

            <h3 class="text-xl font-semibold mb-2">{{ selectedProduct?.product_name }}</h3>
            <p class="text-sm text-gray-600 mb-2">
              Stock: {{ selectedProduct?.qty_stock }} / {{ selectedProduct?.uom_id }}
            </p>
            <p class="mb-3 text-lg font-semibold text-gray-800">
              <span v-if="selectedProduct?.discount && selectedProduct.discount > 0" class="line-through text-gray-400 mr-2">
                {{ formatRupiah(selectedProduct.price) }}
              </span>
              <span>
                {{ formatRupiah(selectedProduct?.price_sell ?? selectedProduct?.price ?? 0) }}
              </span>
              <span v-if="selectedProduct?.discount && selectedProduct.discount > 0" class="text-green-500 ml-1">
                (Disc {{ formatRupiah(selectedProduct.discount ?? 0) }})
              </span>
            </p>

            <div class="flex items-center space-x-3 mb-4">
              <button
                @click="decreaseDetailQty"
                class="px-3 py-1 bg-gray-200 rounded disabled:opacity-50"
                :disabled="detailQty <= 1"
                aria-label="Decrease quantity"
              >-</button>
              <input
                type="number"
                min="1"
                :max="selectedProduct?.qty_stock ?? 1"
                v-model.number="detailQty"
                class="w-16 text-center border rounded py-1"
              />
              <button
                @click="increaseDetailQty"
                class="px-3 py-1 bg-gray-200 rounded"
                :disabled="detailQty >= (selectedProduct?.qty_stock ?? 1)"
                aria-label="Increase quantity"
              >+</button>
            </div>

            <button
              @click="addToCartFromDetail"
              class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 disabled:opacity-50"
              :disabled="(selectedProduct?.qty_stock ?? 0) === 0"
            >
              Tambah Keranjang
            </button>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, nextTick, computed, onMounted } from 'vue';
import { useToast } from '@/composables/useToast';
import axios from 'axios';

interface Product {
  id: number;
  product_id?: number;
  product_name: string;
  uom_id: string;
  qty_stock: number;
  image_path: string;
  price: number;
  discount?: number;
  price_sell?: number;
  quantity: number;
}

const toast = useToast();
const products = ref<Product[]>([]);
const selectedProducts = ref<Product[]>([]);

const searchText = ref('');
const currentPage = ref(1);
const lastPage = ref(1);

const orderList = ref<HTMLElement | null>(null);

const showDetailModal = ref(false);
const selectedProduct = ref<Product | null>(null);
const detailQty = ref(1);

const getImageUrl = (path: string) => {
  if (!path) return '';
  if (path.startsWith('storage/')) return '/' + path;
  if (path.startsWith('/storage/')) return path;
  return '/storage/' + path;
};

const formatRupiah = (value: number): string => {
  if (typeof value !== 'number' || isNaN(value)) return 'Rp 0,00';
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 2,
  }).format(value);
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

function onSearchInput() {
  currentPage.value = 1;
  fetchProducts();
}

function viewProductDetail(product: Product) {
  selectedProduct.value = { ...product };
  detailQty.value = 1;
  showDetailModal.value = true;
}

function closeDetailModal() {
  showDetailModal.value = false;
  selectedProduct.value = null;
  detailQty.value = 1;
}

const addToCartFromDetail = () => {
  if (!selectedProduct.value) return;

  const productId = selectedProduct.value.product_id ?? selectedProduct.value.id;
  const existing = selectedProducts.value.find((item) => item.id === productId);
  if (existing) {
    existing.quantity += detailQty.value;
  } else {
    selectedProducts.value.push({
      id: productId,
      product_name: selectedProduct.value.product_name,
      uom_id: selectedProduct.value.uom_id,
      qty_stock: selectedProduct.value.qty_stock,
      image_path: selectedProduct.value.image_path,
      price: selectedProduct.value.price,
      discount: selectedProduct.value.discount,
      price_sell: selectedProduct.value.price_sell,
      quantity: detailQty.value,
    });
  }
  toast.success(`${detailQty.value} item(s) added to cart`);
  closeDetailModal();
  nextTick(() => {
    if (orderList.value) orderList.value.scrollTop = orderList.value.scrollHeight;
  });
};

const increaseDetailQty = () => {
  if (detailQty.value < (selectedProduct.value?.qty_stock ?? 1)) {
    detailQty.value++;
  }
};

const decreaseDetailQty = () => {
  if (detailQty.value > 1) {
    detailQty.value--;
  }
};

const increaseQty = (item: Product) => {
  if (item.quantity < item.qty_stock) {
    item.quantity++;
  } else {
    toast.error('Stock limit reached');
  }
};

const decreaseQty = (item: Product) => {
  if (item.quantity > 1) {
    item.quantity--;
  }
};

const removeFromCart = (id: number) => {
  selectedProducts.value = selectedProducts.value.filter((item) => item.id !== id);
};

const cartTotal = computed(() => {
  return selectedProducts.value.reduce((total, item) => {
    const price = item.price_sell ?? item.price;
    return total + price * item.quantity;
  }, 0);
});

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--;
    fetchProducts();
  }
};

const nextPage = () => {
  if (currentPage.value < lastPage.value) {
    currentPage.value++;
    fetchProducts();
  }
};

function checkout() {
  if (selectedProducts.value.length === 0) {
    toast.error('Cart is empty');
    return;
  }
  // Implement checkout logic here...
  toast.success('Checkout successful');
  selectedProducts.value = [];
}

onMounted(() => {
  fetchProducts();
});
</script>
