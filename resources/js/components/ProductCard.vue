<script setup lang="ts">
defineProps<{
  product: {
    id: number | string;
    name: string;
    price: number;
    image: string;
  };
}>();
</script>

<template>
  <div class="border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow cursor-pointer bg-white dark:bg-gray-800">
    <img :src="product.image" alt="product.name" class="w-full h-40 object-cover" />
    <div class="p-4">
      <h3 class="font-semibold text-lg truncate">{{ product.name }}</h3>
      <p class="mt-1 text-orange-600 font-bold">Rp {{ product.price.toLocaleString() }}</p>
    </div>
  </div>
</template>
