<script setup lang="ts">
import { ref, computed, onMounted, watch, defineProps } from 'vue';

interface Product {
  id: number;
  price: number;
  discount?: number;
}

interface CartItem {
  quantity: number;
  price: number;
  product?: Product;
}

interface User {
  customer_id?: number;
}

const props = defineProps({
  user: Object as () => User,
});
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { useCartStore } from '@/stores/useCartStore';
import { useToast } from "@/composables/useToast";
import { Button } from '@/components/ui/button';
import axios from 'axios';
import QrcodeVue from 'qrcode.vue';


const cartStore = useCartStore();
const toast = useToast();

const showPostCheckoutModal = ref(false);
const orderIdForQr = ref<string | null>(null);
const orderIdForUpload = ref<string | null>(null);

const selectedPaymentMethod = ref<string | null>(null);
const settingMessage = ref<string | null>(null);
const paymentProofFile = ref<File | null>(null);

watch(selectedPaymentMethod, async (newValue: string | null) => {
  if (newValue === 'cod_store') {
    try {
      const response = await axios.get('/api/setting/cod_store');
      settingMessage.value = response.data.message; // Assuming the API returns { message: "..." }
    } catch (error) {
      console.error('Error fetching COD message:', error);
      settingMessage.value = 'Failed to load COD message.';
    }
  }else if (newValue === 'bank_transfer') {
    try {
      const response = await axios.get('/api/setting/bank_transfer');
      settingMessage.value = response.data.message; // Assuming the API returns { message: "..." }
    } catch (error) {
      console.error('Error fetching COD message:', error);
      settingMessage.value = 'Failed to load bank transfer message.';
    }
  } else {
    settingMessage.value = null;
  }
});
const subtotalAmount = computed(() => {
  return cartStore.cartItems.reduce((sum: number, item: CartItem) => {
    return sum + (item.quantity || 0) * (item.price || 0);
  }, 0);
});

const discountAmount = computed(() => {
  return cartStore.cartItems.reduce((sum: number, item: CartItem) => {
    const originalPrice = item.product?.price || 0;
    const finalPricePerItem = item.price || 0;
    const discountPerItem = originalPrice - finalPricePerItem;
    return sum + (discountPerItem * (item.quantity || 0));
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

onMounted(() => {
  if (cartStore.cartItems.length === 0) {
    toast.warning('Your cart is empty. Please add items before checking out.');
    router.visit('/home');
  }
});

const confirmCheckout = async () => {
  if (!selectedPaymentMethod.value) {
    toast.error('Please select a payment method.');
    return;
  }

  if (selectedPaymentMethod.value === 'bank_transfer' && !paymentProofFile.value) {
    toast.error('Please upload proof of transfer.');
    return;
  }

  if (confirm('Are you sure you want to process this order?')) {
    try {
      interface OrderData {
        customer_id: number | undefined;
        items: { product_id: number; quantity: number; price: number; discount: number; }[];
        payment_method: string;
        total_amount: number;
        payment_proof: File | null;
        [key: string]: any; // Add string index signature
      }

      const orderData: OrderData = {
        customer_id: props.user?.customer_id,
        items: cartStore.cartItems
          .filter((item: CartItem) => item.product?.id)
          .map((item: CartItem) => ({
            product_id: item.product!.id,
            quantity: item.quantity,
            price: item.product?.price || 0,
            discount: item.product?.discount || 0,
          })),
        payment_method: selectedPaymentMethod.value,
        total_amount: totalAmount.value,
        payment_proof: selectedPaymentMethod.value === 'bank_transfer' ? paymentProofFile.value : null,
      };

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
          'Content-Type': 'multipart/form-data'
        }
      });
      toast.success('Order placed successfully!');
      cartStore.clearCart();

      // Handle post-checkout actions based on payment method
      if (selectedPaymentMethod.value === 'cod_store') {
        orderIdForQr.value = response.data.order.id; // Assuming API returns order_id
        showPostCheckoutModal.value = true;
      } else if (selectedPaymentMethod.value === 'bank_transfer') {
        orderIdForUpload.value = response.data.id; // Assuming API returns order_id
        showPostCheckoutModal.value = true;
      } else {
        router.visit('/');
      }
    } catch (error) {
      console.error('Error placing order:', error);
      toast.error('Failed to place order. Please try again.');
    }
  }
};

const goBackToCart = () => {
  router.visit('/home');
};

const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    paymentProofFile.value = target.files[0];
  } else {
    paymentProofFile.value = null;
  }
};
</script>

<template>
  <Head title="Checkout Produk" />
  <AppLayout>
    <div class="container mx-auto px-4 py-8">
     
      <div v-if="showPostCheckoutModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-lg shadow-xl max-w-md w-full text-center">
          <h2 class="text-xl font-bold mb-4">Order Confirmed!</h2>
          <div v-if="orderIdForQr">
            <p class="text-lg mb-4">Your order ID is: <span class="font-semibold">{{ orderIdForQr }}</span></p>
            <p class="text-md text-gray-600 mb-6">Please present this QR code at the store for payment.</p>
            <div class="flex justify-center mb-6">
              <qrcode-vue :value="orderIdForQr" :size="200" level="H" />
            </div>
            <Button @click="router.visit('/')">Go to Home</Button>
          </div>
          <div v-else-if="orderIdForUpload">
            <p class="text-lg mb-4">Your order ID is: <span class="font-semibold">{{ orderIdForUpload }}</span></p>
            <p class="text-md text-gray-600 mb-6">Please upload your proof of transfer below. Your order status will be 'waiting' until confirmed.</p>
            <!-- Placeholder for File Upload -->
            <input type="file" class="block w-full text-sm text-gray-500
              file:mr-4 file:py-2 file:px-4
              file:rounded-full file:border-0
              file:text-sm file:font-semibold
              file:bg-blue-50 file:text-blue-700
              hover:file:bg-blue-100
            "/>
            <Button @click="router.visit('/')" class="mt-6">Done</Button>
          </div>
        </div>
      </div>

      <div v-if="cartItemValue.length > 0 && !showPostCheckoutModal" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Order Summary -->
        <div class="lg:col-span-2 bg-white shadow-xl rounded-2xl p-8 mb-8">
          <h2 class="text-xl font-bold text-gray-800 mb-6">Order Summary</h2>

          <div v-for="item in cartItemValue" :key="item.product?.id" class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200 last:border-b-0 last:mb-0">
            <div class="flex items-center space-x-4">
              <img v-if="item.product?.image" :src="item.product.image" alt="Product Image" class="w-16 h-16 object-cover rounded-lg shadow-md" />
              <div class="flex-1">
                <p class="text-sm text-gray-900">{{ item.product?.name ?? 'Unknown Product' }}</p>
                <p class="text-sm text-gray-600">Quantity: {{ item.quantity }}</p>
              </div>
            </div>
            <p class="text-sm font-bold text-gray-900">{{ formatRupiah(item.price) }}</p>
          </div>

          <div class="mt-8 pt-6 border-t border-gray-300 space-y-4">
            <div class="flex justify-between items-center text-sm font-semibold text-gray-800">
              <span>Subtotal:</span>
              <span>{{ formatRupiah(subtotalAmount) }}</span>
            </div>
            <div class="flex justify-between items-center text-sm font-semibold text-gray-800">
              <span>Discount:</span>
              <span>
                - {{ formatRupiah(discountAmount) }}
              </span>
            </div>
            <div class="flex justify-between items-center text-sm font-bold text-gray-900 pt-4 border-t border-gray-200">
              <span>Total:</span>
              <span>{{ formatRupiah(totalAmount) }}</span>
            </div>
          </div>
        </div>


        <!-- Payment Method -->
        <div class="lg:col-span-1 bg-white shadow-xl rounded-2xl p-8">
          <h2 class="text-sm font-bold text-gray-800 mb-6">Payment Method</h2>
          <div class="space-y-4">
            <label class="flex items-center p-4 rounded-sm border border-gray-200 shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-500 has-[:checked]:ring-2 has-[:checked]:ring-blue-500 transition-all duration-200">
              <input type="radio" name="paymentMethod" value="cod_store" v-model="selectedPaymentMethod" class="form-radio h-5 w-5 text-blue-600" />
              <span class="ml-4 text-sm font-medium text-gray-800">Bayar Di Toko</span>
            </label>
            <p v-if="selectedPaymentMethod === 'cod_store' && settingMessage" class="text-sm text-green-600 mt-2 ml-4">{{ settingMessage }}</p>
            <label class="flex items-center p-4 rounded-sm border border-gray-200 shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-500 has-[:checked]:ring-2 has-[:checked]:ring-blue-500 transition-all duration-200">
              <input type="radio" name="paymentMethod" value="bank_transfer" v-model="selectedPaymentMethod" class="form-radio h-5 w-5 text-blue-600" />
              <span class="ml-4 text-sm font-medium text-gray-800">Bank Transfer</span>
            </label>
            <p v-if="selectedPaymentMethod === 'bank_transfer' && settingMessage" class="text-sm text-green-600 mt-2 ml-4">{{ settingMessage }}</p>
            <div v-if="selectedPaymentMethod === 'bank_transfer'" class="mt-4">
              <label for="payment_proof" class="block text-sm font-medium text-gray-700 mb-2">Upload Bukti Transfer</label>
              <input type="file" id="payment_proof" @change="handleFileUpload" class="block w-full text-sm text-gray-500
                file:mr-4 file:py-2 file:px-4
                file:rounded-full file:border-0
                file:text-sm file:font-semibold
                file:bg-blue-50 file:text-blue-700
                hover:file:bg-blue-100
              "/>
            </div>
          </div>
        </div>

        <div class="flex justify-end space-x-4 mt-8">
            <Button variant="secondary" @click="goBackToCart" class="px-6 py-3 text-base">Back to Cart</Button>
            <Button variant="default" @click="confirmCheckout" class="px-6 py-3 text-base">Place Order</Button>
          </div>
      </div>

      

    <!-- Empty Cart Message -->
    <div v-else class="text-center text-gray-600">
      <p>Your cart is empty. Please add some products to proceed to checkout.</p>
      <Button class="mt-4" @click="goBackToCart">Go to Cart</Button>
    </div>
  </div>
  </AppLayout>
</template>
