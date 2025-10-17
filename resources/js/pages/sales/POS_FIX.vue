<template>
  <Head title="Point of Sale" />
  <AppLayout>
    <div class="p-2 md:p-4 space-y-8 bg-gray-50 min-h-screen">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Product List -->
        <div class="lg:col-span-2">
          <div class="mb-4 flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Available Products</h2>
            <input type="text" v-model="searchText" @input="onSearchInput" placeholder="Search products..."
              class="border rounded px-3 py-1 text-sm w-48" />
          </div>
          <div class="grid grid-cols-4 sm:grid-cols-6 xl:grid-cols-4 gap-2">
            <div v-for="product in products" :key="product.id"
              class="bg-white rounded-2xl p-4 shadow hover:shadow-md transition cursor-pointer border border-gray-200"
              @click="addToCart(product)">
              <img v-if="product.image_path" :src="getImageUrl(product.image_path)" alt="product image"
                class="mb-2 w-full h-24 object-cover rounded-lg" />
              <div v-else
                class="mb-2 w-full h-24 bg-gray-200 rounded-lg flex items-center justify-center text-gray-400 text-xs">
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
        </div>

        <!-- Order Summary -->
        <div class="bg-white rounded-1xl shadow-md p-4 sticky top-6 h-fit border border-gray-100 max-h-[90vh] overflow-auto">
          <div class="flex justify-between items-center mb-5">
            <h2 class="text-lg font-semibold text-gray-800">Order Summary</h2>
            <Button variant="outline" size="sm" @click="clearCart">
              <Trash2 class="h-4 w-4 mr-1" />
              Clear
            </Button>
          </div>

          <div v-if="selectedProducts.length === 0" class="text-center text-gray-400 py-8">
            <p>No items in cart</p>
          </div>

          <div v-else class="space-y-8 max-h-[500px] overflow-y-auto pr-1" ref="orderList">
            <div v-for="item in selectedProducts" :key="item.id" class="flex items-center justify-between gap-5 border-b pb-2">
              <Button variant="ghost" size="icon" class="hover:bg-gray-100" @click="removeFromCart(item.id)">
                <Trash2 class="h-4 w-4" />
              </Button>
              <div class="flex items-center gap-2 text-sm text-gray-600 mt-1">
                <div class="min-w-0">
                  <span class="text-xs mr-1 truncate block max-w-[120px]">
                    {{ item.product_id ? item.product_name : item.product_name }}
                  </span>
                  <span v-if="item.discount && item.discount > 0">
                    <span class="text-xs text-gray-400 line-through mr-1 truncate block max-w-[120px]">
                      {{ formatRupiah(item.price) }}
                    </span>
                    <span class="text-gray-700 font-semibold truncate block max-w-[120px]">
                      {{ formatRupiah(item.price_sell || (item.price - item.discount)) }}
                    </span>
                  </span>
                  <span v-else class="text-gray-700 font-semibold truncate block max-w-[100px] text-xs">
                    {{ formatRupiah(item.price) }}
                  </span>
                </div>
                <span class="text-gray-400">x</span>
                <input type="number" min="1" class="w-10 border rounded px-2 py-1 text-xs"
                  v-model.number="item.quantity" @change="updateQuantity(item)" />
                <span class="ml-auto text-right text-gray-400 text-xs">
                  {{ formatRupiah(item.quantity * (item.price_sell || item.price)) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Payment -->
          <div class="mt-6">
            <label class="text-sm font-medium text-gray-700">Payment Method</label>
            <select v-model="selectedPaymentMethod" class="w-full mt-2 border rounded-lg p-2 text-sm">
              <option value="">Select payment method</option>
              <option v-for="method in paymentMethods" :key="method.id" :value="method.id">
                {{ method.name }}
              </option>
            </select>
          </div>

          <!-- Total & Place Order -->
          <div class="mt-6 pt-4 border-t">
            <div class="flex justify-between font-semibold text-sm text-gray-700">
              <span>Total</span>
              <span class="ml-auto text-right text-gray-700 font-medium">{{ formattedTotalAmount }}</span>
            </div>
            <Button
                class="w-full mt-4 text-sm font-medium"
                @click="openPaymentDialog"
                :disabled="isLoading.placingOrder"
              >
                <span v-if="isLoading.placingOrder">Processing...</span>
                <span v-else>Bayar</span>
              </Button>

          </div>
        </div>
      </div>

      <!-- Payment Dialog -->
<div v-if="showPaymentDialog" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
    <h3 class="text-lg font-semibold mb-4">Masukkan Jumlah Bayar</h3>
    <input
      type="number"
      min="0"
      v-model.number="paidAmount"
      class="w-full border rounded px-3 py-2 mb-4 text-lg"
      placeholder="Masukkan jumlah bayar"
    />
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


      <!-- Print Preview Modal -->
      <div v-if="showPrintPreview" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6 overflow-auto max-h-[80vh] print-area" ref="printArea">
          <h3 class="text-lg font-bold mb-4 text-center">Print Preview</h3>
          <div>
            <table class="w-full text-sm border-collapse border border-gray-300">
              <thead>
                <tr>
                  <th class="border border-gray-300 p-2 text-left">Product</th>
                  <th class="border border-gray-300 p-2 text-right">Qty</th>
                  <th class="border border-gray-300 p-2 text-right">Price</th>
                  <th class="border border-gray-300 p-2 text-right">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in lastOrderItems" :key="item.product_id">
                  <td class="border border-gray-300 p-2">{{ item.product_name }}</td>
                  <td class="border border-gray-300 p-2 text-right">{{ item.quantity }}</td>
                  <td class="border border-gray-300 p-2 text-right">{{ formatRupiah(item.price) }}</td>
                  <td class="border border-gray-300 p-2 text-right">
                    {{ formatRupiah(item.price * item.quantity) }}
                  </td>
                </tr>
                <tr>
                  <td colspan="3" class="border border-gray-300 p-2 font-semibold text-right">Total</td>
                  <td class="border border-gray-300 p-2 font-semibold text-right">
                    {{ formatRupiah(lastOrderTotal) }}
                  </td>
                </tr>
              </tbody>
            </table>
            <div class="mt-4">
              <p><strong>Payment Method:</strong> {{ lastOrderPaymentMethodName }}</p>
              <p><strong>Date:</strong> {{ lastOrderDate }}</p>
              <p><strong>Transaction Number:</strong> {{ transactionNumber }}</p>
            </div>
          </div>
          <div class="mt-6 flex justify-end space-x-3">
            <Button variant="outline" @click="closePrintPreview">CLOSE</Button>
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
import { Trash2 } from 'lucide-vue-next';
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

interface PaymentMethod {
  id: number;
  name: string;
}

interface OrderItem {
  product_id: number;
  product_name: string;
  quantity: number;
  price: number;
}

const toast = useToast();
const products = ref<Product[]>([]);
const selectedProducts = ref<Product[]>([]);
const paymentMethods = ref<PaymentMethod[]>([]);
const selectedPaymentMethod = ref<number | null>(null);
const isLoading = ref({ placingOrder: false });

const searchText = ref('');
const currentPage = ref(1);
const lastPage = ref(1);

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

const showPaymentDialog = ref(false);


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
    minimumFractionDigits: 0,
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

function updateQuantity(item: Product) {
  if (item.quantity < 1) item.quantity = 1;
  const productStock = products.value.find(p => p.id === item.id)?.qty_stock ?? 0;
  if (item.quantity > productStock) {
    toast.error('Stock limit reached');
    item.quantity = productStock;
  }
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
    const orderPayload = {
      items: selectedProducts.value.map(p => ({
        product_id: p.product_id || p.id,
        product_name: p.product_name,
        quantity: p.quantity,
        price: p.price,
        discount: p.discount || 0,
        price_sell: p.price_sell || p.price_sell,
      })),
      payment_method_id: selectedPaymentMethod.value,
      total_amount: totalAmount.value,
      paid_amount: paidAmount.value,  // <-- kirim nilai paidAmount ke backend
    };

    const response = await axios.post('/api/pos/orders', orderPayload);
    lastOrderItems.value = response.data.items;
    lastOrderTotal.value = response.data.total_amount;
    lastOrderPaymentMethodName.value = paymentMethods.value.find(pm => pm.id === selectedPaymentMethod.value)?.name || '';
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
</style>
