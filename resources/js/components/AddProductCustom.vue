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
        <div v-for="(item, index) in form.items" :key="index" class="mb-4 rounded-md bg-gray-50 p-3 shadow-sm">
          <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
            <div>
              <label :for="`size_id-${index}`" class="block text-sm font-medium text-gray-700">Size</label>
              <select
                :id="`size_id-${index}`"
                v-model="item.size_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                required
              >
                <option value="" disabled>Pilih Ukuran</option>
                <option v-for="size in sizes" :key="size.id" :value="size.id">{{ size.name }}</option>
              </select>
              <p v-if="form.errors[`items.${index}.size_id` as keyof typeof form.errors]" class="mt-1 text-sm text-red-600">{{ form.errors[`items.${index}.size_id` as keyof typeof form.errors] }}</p>
            </div>

            <div>
              <label :for="`variant-${index}`" class="block text-sm font-medium text-gray-700">Variant</label>
              <input
                type="text"
                :id="`variant-${index}`"
                v-model="item.variant"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                required
              />
              <p v-if="form.errors[`items.${index}.variant` as keyof typeof form.errors]" class="mt-1 text-sm text-red-600">{{ form.errors[`items.${index}.variant` as keyof typeof form.errors] }}</p>
            </div>

            <div>
              <label :for="`qty-${index}`" class="block text-sm font-medium text-gray-700">Quantity</label>
              <input
                type="number"
                :id="`qty-${index}`"
                v-model.number="item.qty"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                required
              />
              <p v-if="form.errors[`items.${index}.qty` as keyof typeof form.errors]" class="mt-1 text-sm text-red-600">{{ form.errors[`items.${index}.qty` as keyof typeof form.errors] }}</p>
            </div>

            <div>
              <label :for="`price_store-${index}`" class="block text-sm font-medium text-gray-700">Harga Toko</label>
              <input
                type="number"
                :id="`price_store-${index}`"
                v-model.number="item.price_store"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                required
              />
              <p v-if="form.errors[`items.${index}.price_store` as keyof typeof form.errors]" class="mt-1 text-sm text-red-600">{{ form.errors[`items.${index}.price_store` as keyof typeof form.errors] }}</p>
            </div>

            <div>
              <label :for="`price_grosir-${index}`" class="block text-sm font-medium text-gray-700">Harga Grosir</label>
              <input
                type="number"
                :id="`price_grosir-${index}`"
                v-model.number="item.price_grosir"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                required
              />
              <p v-if="form.errors[`items.${index}.price_grosir` as keyof typeof form.errors]" class="mt-1 text-sm text-red-600">{{ form.errors[`items.${index}.price_grosir` as keyof typeof form.errors] }}</p>
            </div>
          </div>
          <button type="button" @click="removeItem(index)" class="mt-3 text-red-600 text-sm hover:text-red-800">Hapus Item</button>
        </div>
        <button type="button" @click="addItem" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
          Tambah Item
        </button>
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

const categoryStore = useCategoryStore();
const sizeStore = useSizeStore();

const { items: categories } = storeToRefs(categoryStore);
const { sizes } = storeToRefs(sizeStore);

interface ProductItem {
  size_id: string;
  variant: string;
  qty: number;
  price_store: number;
  price_grosir: number;
}

const form = useForm({
  name: '',
  category_id: '',
  items: [] as ProductItem[],
});

const addItem = () => {
  form.items.push({
    size_id: '',
    variant: '',
    qty: 1,
    price_store: 0,
    price_grosir: 0,
  });
};

const removeItem = (index: number) => {
  form.items.splice(index, 1);
};

const handleSubmit = async () => {
  try {
    await productStore.saveProductCustom(form.data());
    toast.success('Produk custom berhasil disimpan!');
    form.reset();
    form.items = []; // Reset items array as well
    addItem(); // Add one empty item for convenience after reset
  } catch (error: any) {
    toast.error(error.message || 'Gagal menyimpan produk custom.');
  }
};

onMounted(async () => {
  await categoryStore.fetchCategories();
  await sizeStore.fetchSizes();
  if (form.items.length === 0) {
    addItem(); // Add initial item row
  }
});
</script>
