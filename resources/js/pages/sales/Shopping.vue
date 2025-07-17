<script setup lang="ts">
import { onMounted, onBeforeUnmount } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { useAuthStore } from '@/stores/useAuthStore';
import { useCartStore } from '@/stores/useCartStore';
import { useToast } from '@/composables/useToast';

const toast = useToast();
const auth = useAuthStore();
const cart = useCartStore();

const loadMoreProducts = async () => {
  if (
    !cart.loading &&
    cart.currentPage < cart.lastPage &&
    window.innerHeight + window.scrollY >= document.body.offsetHeight - 200
  ) {
    await cart.fetchProducts(cart.currentPage + 1);
  }
};

onMounted(() => {
  cart.fetchProducts(1);
  window.addEventListener('scroll', loadMoreProducts);
});

onBeforeUnmount(() => {
  window.removeEventListener('scroll', loadMoreProducts);
});

const handleAddToCart = async (productId: number) => {
  try {
    await cart.addToCart(productId);
    toast.success("Produk ditambahkan ke keranjang.");

    if (!auth.isLoggedIn) {
      setTimeout(() => {
        router.visit(route('login'));
      }, 1200);
    }
  } catch (e) {
    console.error(e);
    toast.error("Gagal menambahkan produk.");
  }
};
</script>

<template>
  <Head title="Belanja Produk" />
  <AppLayout>
    <div class="px-4 py-4">
      <h1 class="text-3xl font-semibold mb-6">Produk Tersedia</h1>

      <div v-if="cart.loading && cart.products.length === 0" class="text-center text-gray-500">
        Memuat produk...
      </div>

      <div v-else-if="cart.products.length === 0" class="text-center text-gray-500">
        Tidak ada produk tersedia.
      </div>

      <div
        v-else
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"
      >
        <div
          v-for="product in cart.products"
          :key="product.id"
          class="border rounded-xl p-4 shadow hover:shadow-md transition"
        >
          <div class="aspect-video overflow-hidden rounded-lg bg-gray-100 mb-4">
            <img
              v-if="product.image"
              :src="product.image"
              alt="Gambar produk"
              class="w-full h-full object-cover"
            />
            <video
              v-else-if="product.video"
              :src="product.video"
              controls
              class="w-full h-full object-cover"
            />
          </div>

          <h2 class="text-lg font-semibold mb-1 truncate">{{ product.name }}</h2>
          <p class="text-gray-700 mb-2 text-sm">{{ product.short_description }}</p>
         <p class="text-orange-600 font-semibold mb-4">
            Rp {{ product.price ? product.price.toLocaleString() : '0' }}
          </p>


          <div class="flex justify-between gap-2">
            <!-- <Link
              :href="route('product.show', product.id)"
              class="text-sm text-blue-600 hover:underline"
            > -->
            <Link
              class="text-sm text-blue-600 hover:underline"
            >
              Detail
            </Link>

            <Button @click="handleAddToCart(product.id)" size="sm" class="bg-indigo-600 text-white py-2 h-10 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">
              Add to Cart
            </Button>
          </div>
        </div>
      </div>

      <div v-if="cart.loading && cart.products.length > 0" class="text-center py-4 text-gray-500">
        Memuat lebih banyak produk...
      </div>
    </div>
  </AppLayout>
</template>
