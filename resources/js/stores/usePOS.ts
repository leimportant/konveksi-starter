// src/composables/usePOS.ts
import { ref, computed, watch, nextTick } from 'vue';
import axios from 'axios';
import { useToast } from '@/composables/useToast';

interface Product {
  id: number;
  product_id?: number; // Added
  product_name: string;
  uom_id: string;
  size_id?: string; // Added
  qty_stock: number;
  image_path: string;
  price: number;
  price_sell?: number; // Added
  quantity: number;
  discount?: number; // Added
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

export function usePOS() {
  const toast = useToast();

  const products = ref<Product[]>([]);
  const paymentMethods = ref<PaymentMethod[]>([]);
  const orders = ref<Order[]>([]);
  const selectedProducts = ref<Product[]>([]);
  const selectedPaymentMethod = ref<number | null>(null);
  const isLoading = ref({
    products: false,
    paymentMethods: false,
    orders: false,
    placingOrder: false
  });
  const orderList = ref<HTMLElement | null>(null);

  // Load cart & payment from localStorage
  const loadStorage = () => {
    const savedCart = localStorage.getItem('pos_cart');
    if (savedCart) selectedProducts.value = JSON.parse(savedCart);
    const savedPayment = localStorage.getItem('pos_payment_method');
    if (savedPayment) selectedPaymentMethod.value = parseInt(savedPayment);
  };

  // Save cart & payment to localStorage
  watch(selectedProducts, (val) => {
    localStorage.setItem('pos_cart', JSON.stringify(val));
  }, { deep: true });

  watch(selectedPaymentMethod, (val) => {
    if (val !== null) localStorage.setItem('pos_payment_method', val.toString());
  });

  const fetchInventoryProducts = async () => {
    isLoading.value.products = true;
    try {
      const { data } = await axios.get('/api/stock', { params: { category_id: 1 } });
      products.value = data.map((p: any) => ({
        id: p.product_id,
        product_name: p.product_name,
        uom_id: p.uom_id,
        qty_stock: p.qty_stock,
        image_path: p.image_path || '',
        price: p.price ?? 0,
        quantity: 1
      }));
    } catch (error) {
      toast.error('Failed to fetch products');
      console.error('Product fetch error:', error);
    } finally {
      isLoading.value.products = false;
    }
  };

  const fetchPaymentMethods = async () => {
    isLoading.value.paymentMethods = true;
    try {
      const { data } = await axios.get('/api/payment-methods');
      paymentMethods.value = data.data;
    } catch (error) {
      toast.error('Failed to fetch payment methods');
      console.error('Payment method fetch error:', error);
    } finally {
      isLoading.value.paymentMethods = false;
    }
  };

    const fetchOrdersCustomer = async () => {
    isLoading.value.orders = true;
    try {
      const { data } = await axios.get('/api/orders/customer');
      orders.value = data.data.data;
    } catch (error) {
      toast.error('Failed to fetch orders');
      console.error('Order fetch error:', error);
    } finally {
      isLoading.value.orders = false;
    }
  };


  const addToCart = (product: Product) => {
    const existing = selectedProducts.value.find(item => item.id === product.id);
    if (existing) {
      existing.quantity++;
    } else {
      selectedProducts.value.push({
        id: product.id,
        product_name: product.product_name,
        uom_id: product.uom_id,
        qty_stock: product.qty_stock,
        image_path: product.image_path,
        price: product.price,
        quantity: 1,
      });
    }
    nextTick(() => {
      if (orderList.value) orderList.value.scrollTop = orderList.value.scrollHeight;
    });
  };

  const removeFromCart = (productId: number) => {
    selectedProducts.value = selectedProducts.value.filter(p => p.id !== productId);
  };

  const clearCart = () => {
    selectedProducts.value = [];
    selectedPaymentMethod.value = null;
    localStorage.removeItem('pos_cart');
    localStorage.removeItem('pos_payment_method');
  };

  const updateQuantity = (product: Product) => {
    if (product.quantity < 1) {
      product.quantity = 1;
      toast.warning('Minimum quantity is 1');
    }
  };

  const totalAmount = computed(() =>
    selectedProducts.value.reduce((sum, item) => sum + item.price * item.quantity, 0).toFixed(2)
  );

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
      await axios.post('/api/pos/order', {
        products: selectedProducts.value.map(p => ({
          product_id: p.id,
          quantity: p.quantity,
          price: p.price
        })),
        payment_method_id: selectedPaymentMethod.value
      });
      toast.success('Order placed successfully');
      await fetchOrdersCustomer();
      clearCart();
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Failed to place order');
    } finally {
      isLoading.value.placingOrder = false;
    }
  };

  // Initialize on composable load
  loadStorage();
  fetchInventoryProducts();
  fetchPaymentMethods();


  return {
    products,
    paymentMethods,
    orders,
    selectedProducts,
    fetchOrdersCustomer,
    selectedPaymentMethod,
    isLoading,
    orderList,

    totalAmount,
    addToCart,
    removeFromCart,
    clearCart,
    updateQuantity,
    placeOrder,
    fetchInventoryProducts,
    fetchPaymentMethods,

  };
}
