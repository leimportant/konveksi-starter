<script setup lang="ts">
import { defineProps } from 'vue';

const { item } = defineProps({
  item: {
    type: Object,
    required: true,
  },
});

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value);
};
</script>

<template>
  <div class="flex justify-between items-center text-sm text-gray-700">
    <span>{{ item.qty }} x {{ item.product.name }}</span>
    <span>{{ formatCurrency(item.price_final * item.qty) }}</span>
  </div>
</template>