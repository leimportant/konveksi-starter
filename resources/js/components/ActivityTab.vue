<template>
  <div>
    <table class="min-w-full divide-y divide-gray-200">
      <thead>
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aktivitas</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Upah</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(item, index) in items"
          :key="item.activity_role_id"
          class="bg-white even:bg-gray-50"
        >
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
            {{ rolesMap[item.activity_role_id] || 'Unknown' }}
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
            <Input
              type="number"
              min="0"
              v-model="item.price"
              @input="updatePrice(index, $event.target.value)"
            />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { Input } from '@/components/ui/input';
import { useActivityStore } from '@/stores/useActivityStore';

interface ActivityItem {
  activity_role_id: number;
  price: number;
}

const props = defineProps<{
  modelValue: ActivityItem[];
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', val: ActivityItem[]): void;
}>();

const activityStore = useActivityStore();
const { rolesMap } = activityStore;
const items = ref<ActivityItem[]>(props.modelValue); // Initialize with props value

onMounted(async () => {
  await activityStore.fetchRoles();
  // Only initialize if items are empty
  if (items.value.length === 0) {
    items.value = Object.keys(rolesMap).map(roleId => ({
      activity_role_id: parseInt(roleId),
      price: 0
    }));
    emit('update:modelValue', items.value);
  }
});

watch(items, (newVal) => {
  emit('update:modelValue', newVal);
}, { deep: true });

const updatePrice = (index: number, value: string) => {
  const price = parseFloat(value);
  items.value[index].price = isNaN(price) ? 0 : price;
};
</script>
