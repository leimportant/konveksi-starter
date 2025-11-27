<template>
  <div class="p-4 bg-white rounded-lg shadow-md">
    <h3 class="text-lg font-semibold mb-4">Tambah Produk Custom</h3>
    <form @submit.prevent="handleSubmit">
      <div class="mb-3">
        <label for="name" class="block text-sm font-medium text-gray-700">Nama Produk</label>
        <input
          type="text"
          id="name"
          v-model="form.name"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
          required
        />
        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
      </div>

      <div class="mb-3">
        <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
        <select
          id="category_id"
          v-model="form.category_id"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
          required
        >
          <option value="" disabled>Pilih Kategori</option>
          <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
        </select>
        <p v-if="form.errors.category_id" class="mt-1 text-sm text-red-600">{{ form.errors.category_id }}</p>
      </div>

      <div class="mb-4 mt-6 border-t pt-4">
        <h4 class="text-md font-semibold mb-3">Detail Produk (Ukuran & Harga)</h4>
        <div class="mb-4 rounded-md bg-gray-50 p-3 shadow-sm">
          <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
            <div>
              <label for="size_id" class="block text-sm font-medium text-gray-700">Size</label>
              <select
                id="size_id"
                v-model="form.size_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                required
              >
                <option value="" disabled>Pilih Ukuran</option>
                <option v-for="size in sizes" :key="size.id" :value="size.id">{{ size.name }}</option>
              </select>
              <p v-if="form.errors.size_id" class="mt-1 text-sm text-red-600">{{ form.errors.size_id }}</p>
            </div>

            <div>
              <label for="variant" class="block text-sm font-medium text-gray-700">Variant</label>
              <input
                type="text"
                id="variant"
                v-model="form.variant"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                required
              />
              <p v-if="form.errors.variant" class="mt-1 text-sm text-red-600">{{ form.errors.variant }}</p>
            </div>

            <div>
              <label for="qty" class="block text-sm font-medium text-gray-700">Quantity</label>
              <input
                type="number"
                id="qty"
                v-model.number="form.qty"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                required
              />
              <p v-if="form.errors.qty" class="mt-1 text-sm text-red-600">{{ form.errors.qty }}</p>
            </div>

            <div>
              <label for="price_store" class="block text-sm font-medium text-gray-700">Harga Toko</label>
              <input
                type="number"
                id="price_store"
                v-model.number="form.price_store"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                required
              />
              <p v-if="form.errors.price_store" class="mt-1 text-sm text-red-600">{{ form.errors.price_store }}</p>
            </div>

            <div>
              <label for="price_grosir" class="block text-sm font-medium text-gray-700">Harga Grosir</label>
              <input
                type="number"
                id="price_grosir"
                v-model.number="form.price_grosir"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                required
              />
              <p v-if="form.errors.price_grosir" class="mt-1 text-sm text-red-600">{{ form.errors.price_grosir }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="flex justify-end mt-6">
        <button
          type="submit"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          :disabled="form.processing"
        >
          {{ form.processing ? 'Saving...' : 'Simpan Produk' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useProductStore } from '@/stores/useProductStore';
import { useToast } from '@/composables/useToast';
import { useCategoryStore } from '@/stores/useCategoryStore';
import { useSizeStore } from '@/stores/useSizeStore';
import { storeToRefs } from 'pinia';

const productStore = useProductStore();
const toast = useToast();
const emit = defineEmits(['product-saved']);

const categoryStore = useCategoryStore();
const sizeStore = useSizeStore();

const { items: categories } = storeToRefs(categoryStore);
const { sizes } = storeToRefs(sizeStore);

const form = useForm({
  name: '',
  category_id: '',
  size_id: '', // Changed from null to ''
    variant: '',
    qty: 1,
    price_store: 0,
    price_grosir: 0,
});



const handleSubmit = async () => {
  try {
    await productStore.saveProductUnlisted(form.data());
    toast.success('Produk custom berhasil disimpan!');
    form.reset();
    emit('product-saved');
  } catch (error: any) {
    toast.error(error.message || 'Gagal menyimpan produk custom.');
  }
};

onMounted(async () => {
  await categoryStore.fetchCategories();
  await sizeStore.fetchSizes();
});
</script>
