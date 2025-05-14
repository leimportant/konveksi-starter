<template>
  <Head title="Point of Sale" />
  <AppLayout>
    <div class="p-2 md:p-4 space-y-8 bg-gray-50 min-h-screen">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Product List -->
        <div class="lg:col-span-2">
          <div class="mb-4 flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Available Products</h2>
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-4">
            <div v-for="product in products" :key="product.id"
              class="bg-white rounded-2xl p-4 shadow hover:shadow-md transition cursor-pointer border border-gray-200"
              @click="addToCart(product)">
              <h3 class="font-semibold text-sm text-gray-900 truncate">{{ product.name }}</h3>
              <p class="text-sm text-gray-500 mt-1">Rp. {{ product.price.toFixed(2) }}</p>
            </div>
          </div>
        </div>

        <!-- Order Summary -->
        <div class="bg-white rounded-2xl shadow-md p-5 sticky top-6 h-fit border border-gray-100">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-800">Order Summary</h2>
            <Button variant="outline" size="sm" @click="clearCart">
              <Trash2 class="h-4 w-4 mr-1" />
              Clear
            </Button>
          </div>

          <div v-if="selectedProducts.length === 0" class="text-center text-gray-400 py-6">
            <p>No items in cart</p>
          </div>

          <div v-else class="space-y-4 max-h-[280px] overflow-y-auto pr-1" ref="orderList">
            <div v-for="item in selectedProducts" :key="item.id"
              class="flex items-center justify-between gap-4 border-b pb-2">
              <!-- Product Info -->
              <div class="flex-1">
                <p class="font-medium text-sm">{{ item.name }}</p>
                <div class="flex items-center gap-2 text-sm text-gray-600 mt-1">
                  <span>Rp. {{ item.price }}</span>
                  <span class="text-gray-400">x</span>
                  <input type="number" min="1" class="w-14 border rounded px-2 py-1 text-sm"
                    v-model.number="item.quantity" @change="updateQuantity(item)" />
                  <span class="ml-auto text-right text-gray-400 font-small">Rp. {{ (item.quantity * item.price).toFixed(2) }}</span>
                </div>
              </div>

              <!-- Delete Button -->
              <Button variant="ghost" size="icon" class="hover:bg-gray-100" @click="removeFromCart(item.id)">
                <Trash2 class="h-4 w-4" />
              </Button>
            </div>
          </div>

          <!-- Payment -->
          <div class="mt-6">
            <label class="text-sm font-medium text-gray-700">Payment Method</label>
            <select v-model="selectedPaymentMethod"
              class="w-full mt-2 border rounded-lg p-2 text-sm focus:outline-none focus:ring focus:border-blue-300">
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
              <span class="ml-auto text-right text-gray-700 font-medium">{{ totalAmount }}</span>
            </div>
            <!-- Hapus button clear cart yang ada di sini -->
            <Button class="w-full mt-4 text-sm font-medium" @click="placeOrder" :disabled="isLoading.placingOrder">
              <span v-if="isLoading.placingOrder">Processing...</span>
              <span v-else>Place Order</span>
            </Button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed, nextTick, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Trash2 } from 'lucide-vue-next';
import { useToast } from "@/composables/useToast";
import axios from 'axios';

interface Product {
  id: number;
  name: string;
  price: number;
  category_id: number;
  quantity: number; // Required for cart
}

interface PaymentMethod {
  id: number;
  name: string;
}

interface OrderItem {
  product_id: number;
  quantity: number;
  price: number;
}

interface Order {
  id: number;
  total: number;
  status: string;
  created_at: string;
  items: OrderItem[];
}

const toast = useToast();
const products = ref<Product[]>([]);
const paymentMethods = ref<PaymentMethod[]>([]);
const orders = ref<Order[]>([]);
const selectedProducts = ref<Product[]>([]);
const selectedPaymentMethod = ref<number | null>(null);

// Load cart from localStorage
onMounted(() => {
  const savedCart = localStorage.getItem('pos_cart');
  if (savedCart) {
    selectedProducts.value = JSON.parse(savedCart);
  }
  const savedPayment = localStorage.getItem('pos_payment_method');
  if (savedPayment) {
    selectedPaymentMethod.value = parseInt(savedPayment);
  }
  
  fetchProducts();
  fetchPaymentMethods();
  fetchOrders();
});

// Save cart to localStorage whenever it changes
watch(selectedPaymentMethod, (newMethod: number | null) => {
  if (newMethod) {
    localStorage.setItem('pos_payment_method', newMethod.toString());
  }
});
watch(selectedProducts, (newCart) => {
  localStorage.setItem('pos_cart', JSON.stringify(newCart));
}, { deep: true });
const isLoading = ref({
  products: false,
  paymentMethods: false,
  orders: false,
  placingOrder: false
});
const orderList = ref<HTMLElement | null>(null); // Add ref for order list

// Fetch products with category_id = 1
const fetchProducts = async () => {
  try {
    const { data } = await axios.get('/api/products', {
      params: { category_id: 1 }
    });
    products.value = data.data.map((p: any) => ({
      ...p,
      price: p.price ?? 1000,
      quantity: 10
    }));
  } catch (error) {
    toast.error('Failed to fetch products');
    console.error('Product fetch error:', error);
  }
};

const totalAmount = computed(() =>
  selectedProducts.value.reduce((sum, item) => sum + item.price * item.quantity, 0).toFixed(2)
);

const updateQuantity = (item: Product) => {
  if (item.quantity < 1) {
    item.quantity = 1;
    toast.warning('Minimum quantity is 1');
  }
};

const fetchPaymentMethods = async () => {
  try {
    const { data } = await axios.get('/api/payment-methods');
    paymentMethods.value = data.data;
  } catch (error) {
    toast.error('Failed to fetch payment methods');
    console.error('Payment method fetch error:', error);
  }
};

const fetchOrders = async () => {
  try {
    const { data } = await axios.get('/api/orders');
    orders.value = data.data;
  } catch (error) {
    toast.error('Failed to fetch orders');
    console.error('Order fetch error:', error);
  }
};

const addToCart = (product: Product) => {
  const existingItem = selectedProducts.value.find(item => item.id === product.id);
  if (existingItem) {
    existingItem.quantity += 1;
  } else {
    selectedProducts.value.push({
      ...product,
      quantity: 1
    });
  }

  // Scroll to bottom after adding product
  nextTick(() => {
    if (orderList.value) {
      orderList.value.scrollTop = orderList.value.scrollHeight;
    }
  });
};

const removeFromCart = (productId: number) => {
  selectedProducts.value = selectedProducts.value.filter(item => item.id !== productId);
};

const clearCart = (): void => {
      selectedProducts.value = [];
      selectedPaymentMethod.value = null;
      localStorage.removeItem('pos_cart');
      localStorage.removeItem('pos_payment_method');
    };
    
const placeOrder = async () => {
  if (!selectedPaymentMethod.value) {
    toast.error('Please select a payment method');
    return;
  }
  if (selectedProducts.value.length === 0) {
    toast.error('Please add products to cart');
    return;
  }

  isLoading.value.placingOrder = true;
  try {
    await axios.post('/api/orders', {
      products: selectedProducts.value.map(p => ({
        product_id: p.id,
        quantity: p.quantity,
        price: p.price
      })),
      payment_method_id: selectedPaymentMethod.value
    });
    toast.success('Order placed successfully');
    
    await fetchOrders();
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Failed to place order');
  } finally {
    isLoading.value.placingOrder = false;
  }
};

onMounted(() => {
  fetchProducts();
  fetchPaymentMethods();
  fetchOrders();
});
</script>

