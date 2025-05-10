<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useModelStore } from '@/stores/useModelStore'; // adjust path as needed
import { storeToRefs } from 'pinia';

const modelStore = useModelStore();
const { models } = storeToRefs(modelStore);

const selectedModelId = ref<number | null>(null);
const modelSizes = ref<{ size_id: number; size_name: string; qty: number }[]>([]);

// Initialize form
const form = useForm({
  model_id: null as number | null,
  sizes: [] as { size_id: number; qty: number }[]
});

// Load model list on mount
onMounted(async () => {
  await modelStore.fetchModels();
});

// When model is selected, fetch its details including sizes
watch(selectedModelId, async (id) => {
  if (!id) return;
  try {
    const res = await modelStore.fetchModelById(id);
    const data = res.data;

    form.model_id = data.id;
    modelSizes.value = data.sizes;

    form.sizes = data.sizes.map((s: any) => ({
      size_id: s.size_id,
      qty: s.qty,
    }));
  } catch (err) {
    console.error('Failed to fetch model sizes', err);
  }
});

// Handle form submit
const submit = () => {
  form.post('/production');
};
</script>

<template>
  <div class="p-6 space-y-6">
    <h1 class="text-2xl font-bold">Create Production</h1>

    <!-- Model select -->
    <div>
      <label class="block font-medium mb-1">Select Model</label>
      <select v-model="selectedModelId" class="border p-2 rounded w-full">
        <option disabled value="">-- Choose a model --</option>
        <option v-for="model in models" :key="model.id" :value="model.id">
          {{ model.description }}
        </option>
      </select>
    </div>

    <!-- Sizes table -->
    <div v-if="modelSizes.length > 0">
      <h2 class="font-semibold mb-2">Model Sizes</h2>
      <table class="w-full table-auto border">
        <thead>
          <tr class="bg-gray-100">
            <th class="border px-3 py-2 text-left">Size</th>
            <th class="border px-3 py-2 text-left">Qty</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(size, index) in form.sizes" :key="size.size_id">
            <td class="border px-3 py-2">
              {{ modelSizes[index]?.size_name || 'Unknown' }}
            </td>
            <td class="border px-3 py-2">
              <input
                type="number"
                min="0"
                class="border rounded px-2 py-1 w-full"
                v-model.number="form.sizes[index].qty"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <button
      @click="submit"
      class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
      :disabled="form.processing"
    >
      Save Production
    </button>
  </div>
</template>
