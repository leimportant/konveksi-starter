<script setup lang="ts">

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
  <div class="flex flex-wrap items-center justify-between text-sm text-gray-700 gap-2">
    <div class="flex-1 min-w-0">
      <span class="block font-sm truncate">
        <span class="block font-sm truncate">{{ item.qty }} × {{ item.product.name }} Size: {{ item.size_id }} -  {{ item.variant }} </span>
      </span>
      
    </div>
    <div class="text-right font-semibold whitespace-nowrap">
      {{ formatCurrency(item.price_final * item.qty) }}
    </div>
  </div>
</template>
