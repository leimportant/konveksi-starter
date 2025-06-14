import { defineStore } from 'pinia';
import { ref, watch } from 'vue';
import axios from 'axios';

export const useCartStore = defineStore('cart', () => {
  const items = ref<number[]>(JSON.parse(localStorage.getItem('cart') || '[]'));

  const products = ref<any[]>([]);
  const currentPage = ref(1);
  const lastPage = ref(1);
  const loading = ref(false);
  const searchText = ref('');

  const fetchProducts = async (page = 1) => {
    try {
      loading.value = true;
      const response = await axios.get(`/api/stock?page=${page}&search=${searchText.value}`);

      if (page === 1) {
        products.value = response.data.data;
      } else {
        products.value = [...products.value, ...response.data.data];
      }

      currentPage.value = page;
      lastPage.value = response.data.last_page;
    } catch (error) {
      console.error('Gagal memuat produk:', error);
    } finally {
      loading.value = false;
    }
  };

  const addToCart = (productId: number) => {
    if (!items.value.includes(productId)) {
      items.value.push(productId);
    }
  };

  const removeFromCart = (productId: number) => {
    items.value = items.value.filter(id => id !== productId);
  };

  const clearCart = () => {
    items.value = [];
  };

  watch(items, (val) => {
    localStorage.setItem('cart', JSON.stringify(val));
  }, { deep: true });

  return {
    items,
    products,
    currentPage,
    lastPage,
    loading,
    searchText,
    fetchProducts,
    addToCart,
    removeFromCart,
    clearCart,
  };
});
