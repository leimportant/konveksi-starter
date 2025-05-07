<!-- components/SizeTab.vue -->
<template>
  <div class="space-y-6">
    <table class="min-w-full divide-y divide-gray-200">
      <thead>
        <tr>
          <th class="px-6 py-5 text-left text-xs text-gray-500 uppercase">Size</th>
          <th class="px-6 py-5 text-left text-xs text-gray-500 uppercase">Quantity</th>
          <th class="px-6 py-5 text-left text-xs text-gray-500 uppercase w-16">Action</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <tr v-for="(item, index) in modelItems" :key="index">
          <td class="px-6 py-4 whitespace-nowrap">
            <select
              v-model="item.size_id"
              class="block w-full rounded-md border border-gray-300 bg-white py-2 px-3 shadow-sm sm:text-sm"
              :disabled="sizeStore.isLoading"
            >
              <option disabled value="0">Pilih Ukuran</option>
              <option
                v-for="size in sizeStore.sizes"
                :key="size.id"
                :value="size.id"
              >
                {{ size.name }}
              </option>
            </select>
            <small v-if="sizeStore.error" class="text-destructive">
              {{ sizeStore.error }}
            </small>
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <Input type="number" v-model="item.qty" class="w-full" min="0" />
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <Button variant="destructive" size="icon" @click="removeItem(index)">
              <Trash/>
            </Button>
          </td>
        </tr>
      </tbody>
    </table>

    <Button type="button" @click="addItem">
      <i class="pi pi-plus" /> Tambah Ukuran
    </Button>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useSizeStore } from '@/stores/useSizeStore';
import { Trash } from 'lucide-vue-next';

const props = defineProps<{
  modelValue: { size_id: number; qty: number }[]
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', val: { size_id: number; qty: number }[]): void
}>();

// Initialize store
const sizeStore = useSizeStore();

// local copy of the v-model array
const modelItems = ref(props.modelValue.slice());

// keep parent in sync
watch(modelItems, val => emit('update:modelValue', val), { deep: true });

// Fetch sizes on mount
onMounted(() => {
  sizeStore.fetchSizes();
});

const addItem = () => {
  modelItems.value.push({ size_id: 0, qty: 0 });
};

const removeItem = (i: number) => {
  modelItems.value.splice(i, 1);
};
</script>
