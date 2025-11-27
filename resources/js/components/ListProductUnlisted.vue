<template>
  <div class="p-6 bg-white rounded-xl shadow-md border border-gray-200">
    <h3 class="text-xl font-semibold mb-4 text-gray-800">Daftar Produk Tanpa Master</h3>

    <!-- Search + Add Button -->
    <div class="mb-4 flex flex-col sm:flex-row justify-between items-center gap-3">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Cari produk..."
        class="w-full sm:w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
        @keyup.enter="fetchUnlistedProducts()"
      />

      <Button
        @click="showAddProductDialog = true"
        class="w-full sm:w-auto flex items-center gap-2 bg-purple-600 text-white hover:bg-purple-700 transition rounded-md"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
        Tambah Produk Baru
      </Button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="py-8 text-center text-purple-600 text-lg font-medium">
      Loading products...
    </div>

    <!-- Empty State -->
    <div v-else-if="!unlistedProducts.length" class="py-8 text-center text-gray-500">
      Tidak ada produk tanpa master ditemukan.
    </div>

    <!-- TABLE VIEW -->
    <div v-else class="overflow-x-auto rounded-lg border border-gray-200">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-2">Produk</th>
            <th class="px-3 py-2">Detail</th>
            <th class="px-3 py-2"></th>
          </tr>
        </thead>

        <tbody class="bg-white divide-y divide-gray-200">
          <tr
            v-for="product in unlistedProducts"
            :key="product.id"
            class="hover:bg-gray-50 transition"
          >
            <!-- PRODUCT NAME -->
            <td class="px-4 py-4 align-top">
              {{ product.name }}
            </td>

            <!-- DETAILS -->
            <td class="border-b border-gray-100 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">
  <div class="flex flex-col gap-3 w-full text-sm
              bg-gradient-to-br from-gray-50 to-purple-50
              rounded-xl p-4 border border-gray-200 shadow-sm backdrop-blur-sm">

    <!-- Kategori -->
    <div class="flex justify-between items-center">
      <span class="font-semibold text-gray-900">
        {{ product.category?.name || '-' }}
      </span>
    </div>

    <!-- Size -->
    <div class="flex justify-between items-center">
      <span class="font-semibold">
        {{ product.variant || '-' }} - {{ product.size_id }}
      </span>
    </div>

    <!-- Harga Toko -->
    <div class="flex justify-between items-center font-sm">
      <span class="font-sm">
        Harga Toko {{ Number(product.price_store).toLocaleString() }}
      </span>
    </div>

    <!-- Harga Grosir -->
    <div class="flex justify-between items-center font-xs">
      Harga Grosir
      <span class="font-bold text-purple-700">
        {{ Number(product.price_grosir).toLocaleString() }}
      </span>
    </div>

  </div>
</td>



            <!-- ACTION -->
            <td class="px-4 py-4 text-right align-top">
              <Button
                @click="addToCart(product)"
                size="sm"
                class="bg-green-600 hover:bg-green-700 text-white rounded-md shadow-sm px-3 py-1 text-xs"
              >
                <ShoppingBag  class="h-4 w-4"  />
              </Button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6 flex items-center justify-between">
      <span class="text-sm text-gray-600">
        Page {{ pagination.current_page }} dari {{ totalPages }}
      </span>

      <div class="flex items-center gap-2">
        <Button
          @click="goToPage(pagination.current_page - 1)"
          :disabled="pagination.current_page === 1 || loading"
          variant="outline"
          class="px-4 py-2 border border-gray-300 text-sm text-gray-700 disabled:opacity-50"
        >
          Previous
        </Button>

        <Button
          @click="goToPage(pagination.current_page + 1)"
          :disabled="pagination.current_page >= totalPages || loading"
          variant="outline"
          class="px-4 py-2 border border-gray-300 text-sm text-gray-700 disabled:opacity-50"
        >
          Next
        </Button>
      </div>
    </div>

    <!-- Modal -->
    <Modal :show="showAddProductDialog" @close="showAddProductDialog = false">
      <template #header>
        <h3 class="text-lg font-medium text-gray-900">Tambah Produk Tanpa Master</h3>
      </template>
      <AddProductUnlisted @product-saved="handleProductSaved" />
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useProductStore } from '@/stores/useProductStore';
import { Button } from '@/components/ui/button';
import Modal from '@/components/Modal.vue';
import AddProductUnlisted from './AddProductUnlisted.vue';
import { useToast } from '@/composables/useToast';
import { ShoppingBag } from 'lucide-vue-next';


const toast = useToast();
const productStore = useProductStore();

const emit = defineEmits(['add-unlisted-to-cart']);

const unlistedProducts = ref<any[]>([]);
const loading = ref(false);
const searchQuery = ref('');
const showAddProductDialog = ref(false);

const pagination = ref({
  current_page: 1,
  per_page: 50,
  total: 0,
});

const totalPages = computed(() => Math.ceil(pagination.value.total / pagination.value.per_page));

const fetchUnlistedProducts = async (page = 1) => {
  loading.value = true;

  try {
    const response = await productStore.fetchProductUnlisted(page, pagination.value.per_page, searchQuery.value);

    unlistedProducts.value = response.data.map((item: any) => ({
      ...item,
      category: { name: item.category_name },
      size: { name: item.size_id },
    }));

    pagination.value.current_page = response.current_page;
    pagination.value.per_page = response.per_page;
    pagination.value.total = response.total;
  } catch (e) {
    console.log(e)
    toast.error('Failed to fetch unlisted products.');
  } finally {
    loading.value = false;
  }
};

const goToPage = (page: number) => {
  if (page >= 1 && page <= totalPages.value) fetchUnlistedProducts(page);
};

const addToCart = (product: any) => {
  emit('add-unlisted-to-cart', { ...product, is_unlisted: true });
};

const handleProductSaved = () => {
  showAddProductDialog.value = false;
  fetchUnlistedProducts(pagination.value.current_page);
  toast.success('Produk tanpa master berhasil ditambahkan.');
};

onMounted(() => fetchUnlistedProducts());
</script>
