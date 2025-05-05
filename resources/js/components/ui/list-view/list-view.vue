<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  items: any[];
  keyField?: string;
  displayField?: string;
  isLoading?: boolean;
  emptyMessage?: string;
  showDivider?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  keyField: 'id',
  displayField: 'name',
  isLoading: false,
  emptyMessage: 'Tidak ada data',
  showDivider: true
});

const emit = defineEmits<{
  (e: 'select', item: any): void;
}>();

const hasItems = computed(() => props.items && props.items.length > 0);
</script>

<template>
  <div class="rounded-md border bg-white">
    <div v-if="isLoading" class="p-4 text-center text-gray-500">
      <div class="animate-spin h-6 w-6 border-2 border-primary mx-auto mb-2"></div>
      <p>Memuat data...</p>
    </div>

    <div v-else-if="!hasItems" class="p-4 text-center text-gray-500">
      {{ emptyMessage }}
    </div>

    <ul v-else class="divide-y divide-gray-200">
      <li 
        v-for="item in items" 
        :key="item[keyField]"
        class="p-4 hover:bg-gray-50 cursor-pointer transition-colors"
        @click="emit('select', item)"
      >
        <div class="w-full">
          <div class="flex flex-col lg:flex-row lg:items-center gap-4">
            <div class="flex-1 min-w-0 space-y-2">
              <slot name="item" :item="item">
                {{ item[displayField] }}
              </slot>
            </div>
            <div class="flex-shrink-0 flex justify-end lg:justify-start">
              <slot name="actions" :item="item"></slot>
            </div>
          </div>
        </div>
      </li>
    </ul>
  </div>
</template>