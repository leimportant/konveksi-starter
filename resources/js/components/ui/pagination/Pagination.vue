<script setup lang="ts">
import { computed, ref } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
  class?: string;
  items?: any[];
  itemsPerPage?: number;
  pageSizes?: number[];
}>();

// Beri default secara manual jika props tidak diberikan
const items = ref(props.items ?? []);
const itemsPerPage = ref(props.itemsPerPage ?? 10);
const pageSizes = ref(props.pageSizes ?? [10, 20, 50]);
const currentPage = ref(1);

const totalPages = computed(() =>
  Math.ceil(items.value.length / itemsPerPage.value)
);

const paginatedItems = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  return items.value.slice(start, start + itemsPerPage.value);
});

const handlePageChange = (page: number) => {
  if (page >= 1 && page <= totalPages.value) currentPage.value = page;
};
</script>

<template>
  <div class="relative w-full overflow-auto">
    <table :class="cn('w-full caption-bottom text-sm', props.class)">
      <slot :paginatedItems="paginatedItems" />
    </table>

    <div class="flex justify-between items-center mt-4">
      <div class="flex items-center gap-2">
        <label class="text-sm">Rows per page:</label>
        <select v-model="itemsPerPage" class="border rounded px-2 py-1 text-sm">
          <option v-for="size in pageSizes" :key="size" :value="size">
            {{ size }}
          </option>
        </select>
      </div>
      <div class="flex items-center gap-2">
        <button
          @click="handlePageChange(currentPage - 1)"
          :disabled="currentPage === 1"
          class="px-2 py-1 border rounded text-sm"
        >
          Prev
        </button>
        <span class="text-sm">Page {{ currentPage }} of {{ totalPages }}</span>
        <button
          @click="handlePageChange(currentPage + 1)"
          :disabled="currentPage === totalPages"
          class="px-2 py-1 border rounded text-sm"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>
