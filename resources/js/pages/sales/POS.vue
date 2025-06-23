<template>

  <Head title="Point of Sale" />
  <AppLayout>
    <div class="min-h-screen bg-gray-50 p-4 md:p-8 space-y-10">

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Products Section -->
        <section class="lg:col-span-2">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
            <h2 class="text-2xl font-semibold text-gray-800">Available Products..</h2>
            <input type="text" v-model="searchText" @input="onSearchInput" placeholder="Search products..."
              class="border border-gray-300 rounded-md px-4 py-2 text-sm w-full max-w-xs focus:outline-none focus:ring-2 focus:ring-primary-500" />
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-4">
            <div v-for="product in products" :key="product.id" @click="addToCart(product)"
              class="bg-white rounded-xl p-4 shadow-sm hover:shadow-md cursor-pointer border border-gray-200 transition flex flex-col">
              <img v-if="product.image_path" :src="getImageUrl(product.image_path)" alt="product image"
                class="mb-3 h-28 w-full object-cover rounded-lg" />
              <div v-else
                class="mb-3 h-28 w-full bg-gray-200 rounded-lg flex items-center justify-center text-gray-400 text-xs select-none">
                No Image
              </div>
              <p class="font-semibold text-sm text-gray-900 truncate mb-1">{{ product.product_name }}</p>
              <p class="text-xs text-gray-400 mb-2">Stock: {{ product.qty_stock }} / {{ product.uom_id }}</p>
              <div>
                <template v-if="product.discount && product.discount > 0">
                  <p class="text-xs text-gray-400 line-through">{{ formatRupiah(product.price) }}</p>
                  <p class="text-sm font-semibold text-green-600">
                    {{ formatRupiah(product.price_sell ?? product.price - product.discount) }}
                  </p>
                  <p class="text-xs text-green-500">(Disc {{ formatRupiah(product.discount) }})</p>
                </template>
                <p v-else class="text-sm font-semibold text-gray-700">{{ formatRupiah(product.price) }}</p>
              </div>
            </div>
          </div>
        </section>

        <!-- Order Summary Section -->
          <!-- Cart Sidebar -->
        <aside
          class="bg-gray-100 dark:bg-gray-800 dark:text-blue-400 rounded-l shadow p-2 flex flex-col max-h-[calc(90vh-4rem)]">
          <h2 class="text-lg font-semibold text-gray-800 mb-4 flex justify-between items-center">
            Order Summary
            <div class="flex space-x-2">
              <Button variant="outline" size="sm" @click="openDiscountDialog"
                :disabled="selectedForDiscount.length === 0" class="flex items-center gap-1">
                <PercentIcon class="h-4 w-4" /> Disc ({{ selectedForDiscount.length }})
              </Button>
              <Button variant="outline" size="sm" @click="showQrScanner = true" class="flex items-center gap-1">
                <ScanQrCode class="h-4 w-4" /> QR
              </Button>
              <Button variant="outline" size="sm" @click="clearCart" class="flex items-center gap-1">
                <Trash2 class="h-4 w-4" /> Clear
              </Button>
            </div>
          </h2>

          <div v-if="selectedProducts.length === 0" class="text-center text-gray-400 py-12 select-none">
            No items in cart
          </div>

          <div v-else class="space-y-4 overflow-y-auto flex-1 pr-2">
            <div v-for="item in selectedProducts" :key="item.id"
              class="flex items-center gap-3 border-b border-gray-200 pb-3">
              <input type="checkbox" :checked="selectedForDiscount.includes(item.id)"
                @change="toggleProductSelection(item.id)"
                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
              <Button variant="ghost" size="icon" @click="removeFromCart(item.id)" class="hover:bg-gray-100">
                <Trash2 class="h-4 w-4" />
              </Button>
              <div class="flex items-center gap-2 text-sm text-gray-600 mt-1 min-w-0">
                <div class="min-w-0 flex-1">
                  <span class="text-xs mr-1 truncate block max-w-[120px]">
                    <p>{{ item.product_name }} </p>
                    <template v-if="item.discount && item.discount > 0">
                      <span class="line-through text-gray-400 text-xs">{{ formatRupiah(item.price) }}</span><br />
                      <span class="font-semibold text-gray-900">{{ formatRupiah(item.price_sell || (item.price -
                        item.discount)) }}</span><br/>
                      <span class="text-green-500 text-xs">Disc : -{{ formatRupiah(item.discount) }}</span>
                    </template>
                    <span v-else class="font-semibold text-gray-900 text-xs">{{ formatRupiah(item.price) }}</span>
                  </span>

                </div>

                <input type="number" min="1" v-model.number="item.quantity" @change="updateQuantity(item)"
                  class="w-12 border rounded px-2 py-1 text-xs text-center" />

                <span class="ml-auto text-right text-gray-400 text-xs min-w-[60px]">
                  {{ formatRupiah(item.quantity * (item.price_sell || item.price)) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Payment Method -->
          <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
            <select v-model="selectedPaymentMethod"
              class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
              <option value="">Select payment method</option>
              <option v-for="method in paymentMethods" :key="method.id" :value="method.id">{{ method.name }}</option>
            </select>
          </div>

          <!-- Total & Pay -->
          <div class="mt-6 pt-4 border-t border-gray-200">
            <div class="flex justify-between font-semibold text-gray-700 text-sm mb-4">
              <span>Total</span>
              <span class="font-bold">{{ formattedTotalAmount }}</span>
            </div>
            <Button class="w-full text-sm font-semibold" @click="openPaymentDialog" :disabled="isLoading.placingOrder">
              <span v-if="isLoading.placingOrder">Processing...</span>
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
            <Button @click="confirmPayment">Konfirmasi</Button>
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
      <Button @click="doPrintKasir58mm">CETAK</Button>
    </div>
  </div>
</div>

    </div>
  </AppLayout>
</template>


<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, nextTick } from 'vue';
import { Button } from '@/components/ui/button';
import { Trash2, PercentIcon, ScanQrCode } from 'lucide-vue-next';
import { useToast } from '@/composables/useToast';
import type { User } from '@/types';

import axios from 'axios';
// import jsQR from 'jsqr';
import { QrcodeStream } from 'vue-qrcode-reader';
import Modal from '@/components/Modal.vue';


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

// Add these functions
// const startScanning = async () => {
//   try {
//     stream.value = await navigator.mediaDevices.getUserMedia({
//       video: { facingMode: 'environment' }
//     });

//     if (videoElement.value && stream.value) {
//       videoElement.value.srcObject = stream.value;
//       videoElement.value.play();

//       requestAnimationFrame(scan);
//     }

//     showScanner.value = true;
//   } catch (err) {
//     console.error(err);
//     toast.error('Failed to start camera');
//   }
// };


// const scan = () => {
//   if (!videoElement.value || !showScanner.value) return;

//   const canvas = document.createElement('canvas');
//   const context = canvas.getContext('2d');

//   if (context && videoElement.value) {
//     canvas.width = videoElement.value.videoWidth;
//     canvas.height = videoElement.value.videoHeight;

//     context.drawImage(videoElement.value, 0, 0, canvas.width, canvas.height);
//     const imageData = context.getImageData(0, 0, canvas.width, canvas.height);

//     const code = jsQR(imageData.data, imageData.width, imageData.height);

//     if (code) {
//       try {
//         const productData = JSON.parse(code.data);
//         if (productData.id) {
//           const product = products.value.find(p => p.id === productData.id);
//           if (product) {
//             addToCart(product);
//             stopScanning();
//             toast.success('Product added to cart');
//             return;
//           }
//         }
//       } catch (err) {
//         console.error('Invalid QR code data:', err);
//       }
//     }

//     requestAnimationFrame(scan);
//   }
// };

// const stopScanning = () => {
//   if (stream.value) {
//     stream.value.getTracks().forEach(track => track.stop());
//     stream.value = null;
//   }
//   showScanner.value = false;
// };

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
      console.error("Gagal memuat data pesanan:", error);
    }
  }
};


// function doPrintKasir58mm2() {
//   const printContent = (printArea.value as HTMLElement)?.innerHTML;
//   if (!printContent) {
//     toast.error('Print content not found.');
//     return;
//   }

//   const printWindow = window.open('', '_blank', 'width=300,height=600');
//   if (!printWindow) {
//     toast.error('Failed to open print window.');
//     return;
//   }

//   printWindow.document.write(`
//     <html>
//       <head>
//         <title>Print Kasir</title>
//         <style>
//           @media print {
//             body * {
//               visibility: hidden;
//             }
//             .print-area, .print-area * {
//               visibility: visible;
//             }
//             .print-area {
//               position: absolute;
//               left: 0;
//               top: 0;
//               width: 58mm;
//               font-family: monospace, Arial, sans-serif;
//               font-size: 12px;
//               padding: 5mm;
//               background: white;
//               box-shadow: none;
//             }
//           }
//         </style>
//       </head>
//       <body>
//         <div class="print-area">${printContent}</div>
//         <script>
//           window.onload = function() {
//             window.print();
//             window.onafterprint = function() {
//               window.close();
//             };
//           };
//         <\/script>
//       </body>
//     </html>
//   `);
//   printWindow.document.close();

//   clearCart();                  // reset cart setelah cetak
//   selectedPaymentMethod.value = null;  // reset payment method
//   showPrintPreview.value = false;      // tutup modal
// }

function doPrintKasir58mm() {
  // Assuming 'printArea' is a ref to a DOM element (e.g., a textarea or a div)
  // and 'toast' is a notification library.
  // The 'as HTMLElement' cast suggests printArea might be a Vue ref or similar.
  const printContent = (printArea.value as HTMLElement)?.innerHTML;
  if (!printContent) {
    // Assuming 'toast' is available for notifications
    if (typeof toast !== 'undefined' && toast.error) {
      toast.error('Print content not found.');
    } else {
      console.error('Print content not found.');
    }
    return;
  }

  // 58mm is approximately 220px. Adjusting window width for a better preview.
  const printWindow = window.open('', '_blank', 'width=230,height=600');

  if (!printWindow) {
    if (typeof toast !== 'undefined' && toast.error) {
      toast.error('Failed to open print window.');
    } else {
      console.error('Failed to open print window.');
    }
    return;
  }

  printWindow.document.write(`
    <html>
      <head>
        <title>Print Kasir</title>
        <style>
          /* Basic reset and page setup for printing */
          body {
            margin: 0;
            font-family: 'Courier New', Courier, monospace; /* Classic receipt font */
            font-size: 10px; /* Base font size, adjust as needed for 58mm width */
            line-height: 1.3; /* Spacing between lines */
            background-color: #fff; /* Ensure print background is white */
            color: #000; /* Ensure text is black */
          }

          /* The main container for the receipt content */
          .print-area {
            width: 58mm; /* Strict width for the thermal paper */
            padding: 2mm; /* Small padding around the content */
            box-sizing: border-box; /* Include padding and border in the element's total width and height */
          }

          /* Utility classes for text alignment */
          .text-center { text-align: center; }
          .text-left { text-align: left; }
          .text-right { text-align: right; }
          .font-bold { font-weight: bold; }

          /* Header section styling */
          .receipt-header {
            text-align: center;
            margin-bottom: 2mm;
          }
          .receipt-header .shop-icon { /* If you use an icon, e.g., an emoji or SVG */
            font-size: 22px; /* Adjust if you have a real icon/SVG */
            margin-bottom: 1mm;
          }
          .receipt-header .shop-name {
            font-weight: bold;
            font-size: 12px; /* Slightly larger for shop name */
            margin: 1mm 0;
          }
          .receipt-header .shop-address,
          .receipt-header .shop-phone,
          .receipt-header .transaction-id {
            font-size: 9px;
            margin: 0.5mm 0;
            line-height: 1.2; /* Tighter line height for address lines */
          }

          /* Dotted line separator */
          .dotted-line {
            border-top: 1px dashed #333; /* Dashed line for separation */
            margin: 1.5mm 0;
            height: 0;
            overflow: hidden;
          }

          /* Transaction details (date, time, cashier, etc.) */
          .transaction-info {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            margin: 1.5mm 0;
          }
          .transaction-info .left-column,
          .transaction-info .right-column {
            flex-basis: 48%; /* Distribute space */
          }
          .transaction-info .right-column {
            text-align: right;
          }
          .transaction-info p {
            margin: 0.5mm 0;
            line-height: 1.2;
          }

          /* Styling for the list of items */
          .items-section .item {
            margin-bottom: 1mm; /* Space between items */
          }
          .items-section .item-line-1 { /* Contains item name and total price for that item */
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.2mm; /* Minimal space before quantity/unit price line */
          }
          .items-section .item-name {
            text-align: left;
            word-break: break-word; /* Prevent overflow for long item names */
            padding-right: 5px; /* Space before price */
          }
          .items-section .item-total-price {
            text-align: right;
            white-space: nowrap; /* Keep price on one line */
            min-width: 50px; /* Ensure alignment, adjust as needed */
          }
          .items-section .item-line-2 { /* Contains quantity and price per unit */
            font-size: 9px;
            padding-left: 10px; /* Indent this line slightly, as seen in the image */
            text-align: left;
          }

          /* Summary section (Total QTY, Sub Total, Total, Bayar, Kembali) */
          .summary-section .summary-line {
            display: flex;
            justify-content: space-between;
            margin: 1mm 0;
            font-size: 10px;
          }
          .summary-section .summary-line.bold {
            font-weight: bold;
          }
           .summary-section .summary-line span:first-child { /* Label like "Total QTY :" */
            text-align: left;
            padding-right: 5px;
          }
          .summary-section .summary-line span:last-child { /* Value like "14" or "Rp 70.000" */
            text-align: right;
            white-space: nowrap;
            min-width: 60px; /* Ensure alignment, adjust as needed */
          }


          /* Footer message */
          .footer-message {
            text-align: center;
            margin-top: 2mm;
            font-size: 10px;
          }
          .footer-message p {
            margin: 0.5mm 0;
          }

          /* Print-specific styles: ensure only .print-area is visible and properly styled */
          @media print {
            body {
              font-size: 10px; /* Explicitly set for printing if different from screen */
            }
            body * {
              visibility: hidden;
            }
            .print-area, .print-area * {
              visibility: visible;
            }
            .print-area {
              position: absolute;
              left: 0;
              top: 0;
              background: white !important; /* Ensure white background for printing */
              box-shadow: none !important; /* No shadow when printing */
              color: #000 !important; /* Ensure black text */
              /* width, font-family, padding are inherited or already set on .print-area */
            }
            /* Hide elements not meant for printing, if any, by giving them a specific class */
            .no-print, .no-print * {
              display: none !important;
            }
          }
        </style>
      </head>
      <body>
        <div class="print-area">
          ${printContent}
        </div>
        <script>
          window.onload = function() {
            // A slight delay can sometimes help ensure all content and styles are rendered,
            // especially if there are images or complex CSS.
            setTimeout(function() {
              window.print();
              window.onafterprint = function() {
                window.close();
              };
            }, 100); // 100ms delay, adjust if needed or remove if not necessary
          };
        <\/script>
      </body>
    </html>
  `);
  printWindow.document.close(); // Close the document for writing

  // These lines are for your main application logic after initiating printing
  // clearCart();
  // selectedPaymentMethod.value = null;
  // showPrintPreview.value = false;
}

/*
  IMPORTANT ASSUMPTION & EXAMPLE HTML STRUCTURE FOR 'printContent':

  The 'printContent' variable (which is the innerHTML of your 'printArea' element)
  is expected to have an HTML structure that uses the classes defined in the CSS above.
  Below is an example of how your 'printArea.innerHTML' should be structured:

  <div class="receipt-header">
    <div class="shop-icon">🛍️</div> <p class="shop-name">Karis Jaya Shop</p>
    <p class="shop-address">Jl. Dr. Ir. H. Soekarno No. 19, Medokan Semampir</p>
    <p class="shop-address">Surabaya</p>
    <p class="shop-phone">No. Telp 0812345678</p>
    <p class="transaction-id">16413520230802084636</p>
  </div>

  <div class="dotted-line"></div>

  <div class="transaction-info">
    <div class="left-column">
      <p>2023-08-02</p>
      <p>08:46:36</p>
      <p>No.0-3</p>
    </div>
    <div class="right-column">
      <p>karis</p>
      <p>Sheila</p>
      <p>Jl. Diponegoro 1, Sby</p>
    </div>
  </div>

  <div class="dotted-line"></div>

  <div class="items-section">
    <div class="item">
      <div class="item-line-1">
        <span class="item-name">1. Indomie Goreng</span>
        <span class="item-total-price">Rp 36.000</span>
      </div>
      <div class="item-line-2">1 lusin x 36,000</div>
    </div>
    <div class="item">
      <div class="item-line-1">
        <span class="item-name">2. Fruit Tea Apple</span>
        <span class="item-total-price">Rp 7.000</span>
      </div>
      <div class="item-line-2">1 500 ml x 7,000</div>
    </div>
    <div class="item">
      <div class="item-line-1">
        <span class="item-name">3. Belfood Sosis Bakar</span>
        <span class="item-total-price">Rp 27.000</span>
      </div>
      <div class="item-line-2">1 x 27,000</div>
    </div>
    </div>

  <div class="dotted-line"></div>

  <div class="summary-section">
    <div class="summary-line">
      <span>Total QTY :</span>
      <span>14</span>
    </div>
    <div class="summary-line">
      <span>Sub Total</span>
      <span>Rp 70.000</span>
    </div>
    <div class="dotted-line"></div> <div class="summary-line bold"> <span>Total</span>
      <span>Rp 70.000</span>
    </div>
    <div class="dotted-line"></div> <div class="summary-line">
      <span>Bayar (Cash)</span>
      <span>Rp 70.000</span>
    </div>
    <div class="summary-line">
      <span>Kembali</span>
      <span>Rp 0</span>
    </div>
  </div>

  <div class="dotted-line"></div>

  <div class="footer-message">
    <p>Terimakasih Telah Berbelanja</p>
  </div>
  */

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


<!--  -->

    <!-- const onDetect = async (detectedCodes) => {
      if (detectedCodes.length > 0) {
        const qrCodeData = detectedCodes[0].rawValue;
        console.log('QR Code Scanned:', qrCodeData);
        showQrScanner.value = false;

        try {
          const response = await axios.get(`/api/orders/scan/${qrCodeData}`);
          const order = response.data;
          if (order && order.items) {
            order.items.forEach(item => {
              addToCart(item.product, item.quantity);
            });
          } else {
            alert('Order not found or no items in order.');
          }
        } catch (error) {
          console.error('Error fetching order:', error);
          alert('Failed to fetch order details. Please try again.');
        }
      }
    }; -->
