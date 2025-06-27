<template>

  <Head title="Point of Sale" />
  <AppLayout>
    <div class="min-h-screen bg-gray-50 p-2 md:p-4 space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3 md:gap-5">
        <!-- Products Section -->
        <section class="md:col-span-2 lg:col-span-2 flex flex-col">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2 gap-2">
            <h2 class="text-lg md:text-2xl font-semibold text-gray-800">Produk Tersedia</h2>
            <input
              type="text"
              v-model="searchText"
              @input="onSearchInput"
              placeholder="Cari produk..."
              class="border border-gray-300 rounded-md px-2 py-1 text-xs md:text-sm w-full max-w-xs focus:outline-none focus:ring-2 focus:ring-primary-500"
            />
          </div>
          <div class="flex flex-col gap-2 md:gap-3">
            <div
              v-for="product in products"
              :key="product.id"
              @click="addToCart(product)"
              class="bg-white rounded-lg p-2 md:p-3 shadow-sm hover:shadow-md cursor-pointer border border-gray-200 transition flex flex-row gap-2 md:gap-3 items-center"
            >
              <!-- Column 1: Image -->
              <div class="flex-shrink-0">
                <img
                  v-if="product.image_path"
                  :src="getImageUrl(product.image_path)"
                  alt="Gambar produk"
                  class="h-16 w-16 object-cover rounded"
                />
                <div v-else
                  class="h-16 w-16 bg-gray-200 rounded flex items-center justify-center text-gray-400 text-xs select-none">
                  Tidak Ada Gambar
                </div>
              </div>
              <!-- Column 2: Info -->
              <div class="flex flex-col justify-between flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <p class="font-semibold text-xs md:text-sm text-gray-900 truncate flex-1">{{ product.product_name }}</p>
                  <template v-if="product.discount && product.price">
                    <span
                      v-if="product.discount > 0"
                      class="bg-orange-500 text-white text-[14px] md:text-xs font-bold rounded px-2 py-0.5 ml-1"
                    >
                      {{ Math.round((product.discount / product.price) * 100) }}%
                    </span>
                  </template>
                </div>
                <p class="text-[10px] md:text-xs text-gray-400">Stok: {{ product.qty_stock }} / {{ product.uom_id }}</p>
                <div>
                  <template v-if="product.discount && product.discount > 0">
                    <p class="text-[10px] md:text-xs text-gray-400 line-through">{{ formatRupiah(product.price) }}</p>
                    <p class="text-xs md:text-sm font-semibold text-green-600">
                      {{ formatRupiah(product.price_sell ?? product.price - product.discount) }}
                    </p>
                    <p class="text-[10px] md:text-xs text-green-500">(Diskon {{ formatRupiah(product.discount) }})</p>
                  </template>
                  <p v-else class="text-xs md:text-sm font-semibold text-gray-700">{{ formatRupiah(product.price) }}</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Cart Sidebar -->
        <aside
          class="bg-gray-100 dark:bg-gray-800 dark:text-blue-400 rounded-xl shadow flex flex-col w-full max-w-full p-2 md:p-4"
          :class="[
            'lg:w-[600px] xl:w-[700px]',
            'min-w-[95vw] sm:min-w-[340px] md:min-w-[400px] lg:min-w-[500px]',
            'max-w-full'
          ]"
          style="width: 100%;"
        >


            <div class="flex flex-wrap gap-1 md:gap-2">
              <Button variant="outline" size="sm" @click="openDiscountDialog"
                :disabled="selectedForDiscount.length === 0" class="bg-indigo-100 flex items-center gap-1">
                <PercentIcon class="h-4 w-4" /> Disc ({{ selectedForDiscount.length }})
              </Button>
              <Button variant="outline" size="sm" @click="showQrScanner = true" class="flex items-center gap-1">
                <ScanQrCode class="h-4 w-4" /> QR
              </Button>
              <Button variant="outline" size="sm" @click="applyCustomer" class="flex items-center gap-1">
                <UserPlusIcon class="h-4 w-4" /> Customer
              </Button>
              <Button variant="outline" size="sm" @click="clearCart" class="flex items-center gap-1">
                <Trash2 class="h-4 w-4" /> Clear
              </Button>
            </div>
   

          <div v-if="selectedProducts.length === 0" class="text-center text-gray-400 py-8 select-none text-xs">
            Tidak ada item di keranjang
          </div>

          <div class="overflow-auto">
  <Table class="w-full">
    <TableHeader>
      <TableRow>
        <TableHead class="text-xs w-12">#</TableHead>
        <TableHead class="text-xs">Nama</TableHead>
        <TableHead class="text-xs w-16 text-center">Qty</TableHead>
        <TableHead class="text-xs w-24 text-center">Total</TableHead>
        <TableHead class="text-xs w-16 text-center">Aksi</TableHead>
      </TableRow>
    </TableHeader>
    <TableBody>
      <TableRow v-for="item in selectedProducts" :key="item.id">
        <TableCell>
          <div class="h-14 w-14 flex items-center justify-center relative">
            <input
              type="checkbox"
              :checked="selectedForDiscount.includes(item.id)"
              @change="toggleProductSelection(item.id)"
              class="absolute top-0 left-0 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
              :title="'Pilih untuk diskon'"
            />
            <img
              v-if="item.image_path"
              :src="getImageUrl(item.image_path)"
              alt="product image"
              class="h-12 w-12 object-cover rounded"
            />
          </div>
        </TableCell>
        <TableCell class="text-xs">
          <div class="font-semibold text-gray-900 truncate max-w-[140px]">{{ item.product_name }}</div>
          <span v-if="item.discount && item.discount > 0" class="line-through text-gray-400 text-[10px]">
            {{ formatRupiah(item.price) }}
          </span>
          <span class="block font-semibold text-gray-900">
            {{ formatRupiah(item.price_sell || (item.price - (item.discount || 0))) }}
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
            @change="updateQuantity(item)"
            class="w-12 border rounded px-1 py-0.5 text-xs text-center"
          />
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


          <div v-if="selectedCustomerName" class="flex justify-between mt-2">
            <span class="font-bold text-xs">Pelanggan:</span>
            <span class="font-bold text-xs">{{ selectedCustomerName }} (#{{ selectedCustomerId }})</span>
          </div>

          <div class="mt-4">
            <label class="block text-xs font-medium text-gray-700 mb-1">Metode Pembayaran</label>
            <select
              v-model="selectedPaymentMethod"
              class="w-full border border-gray-300 rounded-lg p-1 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
              <option value="">Pilih metode pembayaran</option>
              <option v-for="method in paymentMethods" :key="method.id" :value="method.id">{{ method.name }}</option>
            </select>
          </div>

          <div class="mt-4 pt-2 border-t border-gray-200">
            <div class="flex justify-between font-semibold text-gray-700 text-xs mb-2">
              <span>Total</span>
              <span class="font-bold">{{ formattedTotalAmount }}</span>
            </div>
            <Button class="w-full text-xs font-semibold" @click="openPaymentDialog" :disabled="isLoading.placingOrder">
              <span v-if="isLoading.placingOrder">Memproses...</span>
              <span v-else>Bayar</span>
            </Button>
          </div>
        </aside>

      </div>

      <!-- Modals... (Your modal markup remains unchanged, just ensure responsive widths, max widths, and padding) -->
      <div v-if="showModal" class="fixed inset-0 z-50 bg-black bg-opacity-40 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl w-full max-w-xs p-6 shadow-lg">
          <h3 class="text-lg font-semibold mb-4">Set Discount</h3>
          <label class="block mb-2 text-sm font-medium">Discount Price</label>
          <input type="number" v-model.number="discountInput"
            class="w-full border border-gray-300 rounded-md px-3 py-2 mb-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
          <div class="flex justify-end gap-3">
            <button @click="saveDiscount" class="bg-green-600 hover:bg-green-700 text-white text-sm rounded px-4 py-2">
              Save
            </button>
            <button @click="showModal = false"
              class="bg-gray-300 hover:bg-gray-400 text-gray-700 text-sm rounded px-4 py-2">
              Cancel
            </button>
          </div>
        </div>
      </div>

      <!-- Payment Dialog -->
      <div v-if="showPaymentDialog"
        class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
          <h3 class="text-lg font-semibold mb-4">Masukkan Jumlah Bayar</h3>
          <input type="number" min="0" v-model.number="paidAmount" class="w-full border rounded px-3 py-2 mb-4 text-lg"
            placeholder="Masukkan jumlah bayar" />
          <div class="flex justify-between mb-4">
            <div>Total:</div>
            <div class="font-semibold">{{ formattedTotalAmount }}</div>
          </div>
          <div class="flex justify-between mb-4">
            <div>Kembalian:</div>
            <div class="font-semibold text-green-600">{{ formatRupiah(changeAmount) }}</div>
          </div>
          <div class="flex justify-end space-x-3">
            <Button variant="outline" @click="showPaymentDialog = false">Batal</Button>
            <Button @click="confirmPayment" class="bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">Konfirmasi</Button>
          </div>
        </div>
      </div>


      <!-- QR Code Scanner Modal -->
      <Modal :show="showQrScanner" @close="showQrScanner = false" title="Scan QR Code">
        <div class="w-full h-64">
          <qrcode-stream @detect="onDetect" />
        </div>
      </Modal>

      <!-- Print Preview Modal -->
      <div v-if="showPrintPreview"
  class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 p-4">
  <div
    class="bg-white rounded shadow w-[240px] max-w-full p-2 print-area text-[10px] leading-tight font-mono"
    ref="printArea">

    <div class="text-center">
      <p class="font-bold text-[12px]">TOKO ANINKA</p>
      <p>RUKO KALIBATA No.112</p>
      <p>TLP. 12345677</p>
    </div>

    <hr class="border-t border-dashed my-1" />

    <div class="mb-1">
      <p>Tanggal&nbsp;: {{ lastOrderDate }}</p>
      <p>Kasir&nbsp;&nbsp;: {{ cashierName }}</p>
      <p>Nomor&nbsp;&nbsp;&nbsp;: {{ transactionNumber }}</p>
    </div>

    <hr class="border-t border-dashed my-1" />

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
          <span>{{ formatRupiah((item.price * item.quantity) - item.discount) }}</span>
        </div>
      </div>
    </div>

    <hr class="border-t border-dashed my-1" />

    <!-- Totals -->
    <div class="flex justify-between">
      <span>Sub Total&nbsp;:</span>
      <span>{{ formatRupiah(lastOrderSubTotal) }}</span>
    </div>
    <div class="flex justify-between">
      <span>Jumlah Bayar&nbsp;:</span>
      <span>{{ formatRupiah(paidAmount || 0) }}</span>
    </div>
    <div class="flex justify-between">
      <span>Kembalian&nbsp;:</span>
      <span>{{ formatRupiah(changeAmount) }}</span>
    </div>
    <div class="flex justify-between">
      <span>Total Disc&nbsp;:</span>
      <span>{{ formatRupiah(totalDiscount) }}</span>
    </div>
    <div class="flex justify-between">
      <span>Metode&nbsp;:</span>
      <span>{{ lastOrderPaymentMethodName }}</span>
    </div>
    

    <hr class="border-t border-dashed my-1" />
    <p class="text-center">SELAMAT BERBELANJA</p>
    <hr class="border-t border-dashed my-1" />

    <!-- Buttons (hidden when printing) -->
    <div class="flex justify-end gap-2 mt-2 no-print">
      <Button variant="outline" @click="closePrintPreview">TUTUP</Button>
      <Button @click="doPrintKasir80mm" class="bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">CETAK</Button>
    </div>
  </div>
</div>

    </div>
    <CustomerDialog :show="showCustomerDialog" @update:show="showCustomerDialog = $event" @customer-selected="handleCustomerSelected" />
  </AppLayout>
</template>


<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, nextTick } from 'vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

import { Trash2, PercentIcon, ScanQrCode, UserPlusIcon} from 'lucide-vue-next';
import { useToast } from '@/composables/useToast';
import type { User } from '@/types';

import axios from 'axios';
// import jsQR from 'jsqr';
import { QrcodeStream } from 'vue-qrcode-reader';
import Modal from '@/components/Modal.vue';
import CustomerDialog from '@/components/CustomerDialog.vue';


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

interface PaymentMethod {
  id: number;
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
  qty_stock: number;
  image_path: string;
}


interface OrderPayload {
  items: OrderItem[];
  payment_method_id: number;
  total_amount: number;
  paid_amount: number;
  customer_id?: number | null;
}


// const showScanner = ref(false);
// const videoElement = ref<HTMLVideoElement>();
// const stream = ref<MediaStream | null>(null);
const showModal = ref(false)

const showQrScanner = ref(false);

const toast = useToast();
const products = ref<Product[]>([]);
const selectedProducts = ref<Product[]>([]);
const paymentMethods = ref<PaymentMethod[]>([]);
const selectedPaymentMethod = ref<number | null>(null);
const isLoading = ref({ placingOrder: false });

const searchText = ref('');
const currentPage = ref(1);
const lastPage = ref(1);
const discountInput = ref<number>(0);
const selectedForDiscount = ref<number[]>([]);  // Stores selected product IDs


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

const paidAmount = ref<number | null>(null);
const changeAmount = computed(() => {
  if (paidAmount.value === null) return 0;
  return paidAmount.value - totalAmount.value > 0 ? paidAmount.value - totalAmount.value : 0;
});

// Hitung total diskon dari item
const totalDiscount = computed(() =>
  lastOrderItems.value.reduce(
    (sum, item) => sum + (item.discount ?? 0) * item.quantity,
    0
  )
);

// Hitung subtotal dari item
const lastOrderSubTotal = computed(() =>
  lastOrderItems.value.reduce(
    (sum, item) => sum + item.price * item.quantity,
    0
  )
);


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
  const existing = selectedProducts.value.find((item) => item.id === product.product_id);
  if (existing) {
    existing.quantity++;
  } else {
    selectedProducts.value.push({
      id: product.product_id || product.id,
      product_name: product.product_name,
      uom_id: product.uom_id,
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
  selectedProducts.value = selectedProducts.value.filter(p => p.id !== productId);
}

// function selectProduct(product: Product) {
//   const exists = selectedProducts.value.some(p => p.id === product.id);
//   if (!exists) {
//     selectedProducts.value.push({ ...product });
//   }
// }


function updateQuantity(item: Product) {
  if (item.quantity < 1) item.quantity = 1;
  const productStock = products.value.find(p => p.id === item.id)?.qty_stock ?? 0;
  if (item.quantity > productStock) {
    toast.error('Stock limit reached');
    item.quantity = productStock;
  }
}

function openDiscountDialog() {
  if (selectedProducts.value.length === 0) return;

  showModal.value = true;
}

function saveDiscount() {
  selectedProducts.value = selectedProducts.value.map(product => {
    // Only update discount if product is selected
    if (selectedForDiscount.value.includes(product.id)) {
      return {
        ...product,
        discount: discountInput.value,
        price_sell: product.price - discountInput.value
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
  if (paidAmount.value === null || paidAmount.value < totalAmount.value) {
    toast.error('Paid amount must be at least total amount');
    return;
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

const handleCustomerSelected = (customer: { id: number, name: string }) => {
  selectedCustomerId.value = customer.id;
  selectedCustomerName.value = customer.name;
  showCustomerDialog.value = false;
};

const totalAmount = computed(() =>
  selectedProducts.value.reduce((sum, item) => sum + item.price * item.quantity, 0)
);
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
    const items: OrderItem[] = selectedProducts.value.map(p => {
      const discount = p.discount || 0;
      return {
        product_id: p.product_id ?? p.id,
        product_name: p.product_name,
        quantity: p.quantity,
        price: p.price,
        uom_id: p.uom_id,
        qty_stock: 0,
        image_path: '',
        discount,
        price_sell: p.price_sell ?? (p.price - discount),
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
    lastOrderItems.value = items.map(item => ({
      ...item,
      uom_id: selectedProducts.value.find(p => (p.product_id ?? p.id) === item.product_id)?.uom_id || '',
    }));

    lastOrderTotal.value = response.data.total_amount;
    lastOrderPaymentMethodName.value = 
      paymentMethods.value.find(pm => pm.id === selectedPaymentMethod.value)?.name || '';
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
  clearCart();                  // reset cart
  selectedPaymentMethod.value = null;  // reset payment method
}

const onDetect = async (detectedCodes: { rawValue: string }[]) => {
  if (detectedCodes.length > 0) {
    const qrCodeData = detectedCodes[0].rawValue;
    console.log("QR Code detected:", qrCodeData);
    showQrScanner.value = false; // Sembunyikan scanner setelah deteksi

    try {
      const response = await axios.get(`/api/orders/scan/${qrCodeData}`);
      const order = response.data;

      if (order && order.order_items) {
        const products: Product[] = [];

        order.order_items.forEach((item: OrderItem) => {
            const product: Product = {
              id: item.product_id, // mapped
              product_id: item.product_id, // optional
              product_name: item.product_name,
              uom_id: item.uom_id,
              qty_stock: item.qty_stock,
              image_path: item.image_path,
              price: item.price,
              discount: item.discount,
              price_sell: item.price_sell,
              quantity: 1, // default to 1 or from `item.quantity` if available
            };
            products.push(product);
          });

        console.log("Produk dalam pesanan:", products);
        // Lanjutkan dengan memproses produk jika perlu (misalnya: tampilkan di UI)
      }
    } catch (error) {
      console.error("Error scanning QR code:", error);
      toast.error("Failed to scan QR code");
    }
  }
};

function doPrintKasir80mm() {
  // Calculate total quantity
  const totalQty = lastOrderItems.value.reduce((sum, item) => sum + item.quantity, 0);
  // Calculate sisa (remaining)
  const sisa = (lastOrderTotal.value || totalAmount.value) - (paidAmount.value || 0);

  const printContent = `
    <div style="font-family: 'Courier New', Courier, monospace; font-size: 12px; width: 80mm; padding: 0; margin: 0;">
      <div style="text-align:center; font-weight:bold;">TOKO ANINKA</div>
      <div style="text-align:center;">Blok A lantai SLG Los F No 91-92</div>
      <div style="text-align:center;">----------------------------------</div>
      <div style="display:flex; justify-content:space-between;">
        <span>Kasir   : ${cashierName.value}</span>
      </div>
      <div style="display:flex; justify-content:space-between;">
        <span>Tanggal : ${lastOrderDate.value}</span>
      </div>
      <div style="display:flex; justify-content:space-between;">
        <span>Nomor   : ${transactionNumber.value}</span>
      </div>
      <div style="text-align:center;">----------------------------------</div>
      ${
        lastOrderItems.value.map(item => `
          <div>${item.product_name}</div>
          <div style="display:flex; justify-content:space-between;">
            <span>${item.quantity} x ${formatRupiah(item.price)}</span>
            <span>${formatRupiah(item.quantity * item.price)}</span>
          </div>
        `).join('')
      }
      <div style="text-align:center;">----------------------------------</div>
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
      <div style="text-align:center;">----------------------------------</div>
      <div style="display:flex; justify-content:space-between;">
        <span>Customer</span>
        <span>${selectedCustomerName.value || '-'}</span>
      </div>
      <div style="text-align:center;">----------------------------------</div>
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
  .no-print, .no-print * {
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

