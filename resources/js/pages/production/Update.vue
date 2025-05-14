<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { Head, useForm , router} from '@inertiajs/vue3';
import { useToast } from "@/composables/useToast";
import { useProductionStore } from '@/stores/useProductionStore';
import { useModelStore } from '@/stores/useModelStore';
import { useMasterActivityRoleStore } from '@/stores/useMasterActivityRoleStore';
import { storeToRefs } from 'pinia';
import AppLayout from '@/layouts/AppLayout.vue';

const toast = useToast();
const productionStore = useProductionStore();
const modelStore = useModelStore();
const activityRoleStore = useMasterActivityRoleStore();
const { models } = storeToRefs(modelStore);

// Define props
const props = defineProps<{
  id: string;
  activity_role: string;
}>();

// Define refs
const selectedModelId = ref<number | null>(null);
const modelSizes = ref<{ size_id: string; size_name: string; qty: number }[]>([]);
const activityRole = ref<{ id: number; name: string } | null>(null);
const form = useForm({
  id: null as number | null,
  model_id: null as number | null,
  activity_role_id: Number(props.activity_role),
  items: [] as { size_id: string; qty: number }[]
});

// Define the interface for the API response item
interface ProductionItem {
  id: string;
  production_id: string;
  size_id: string;
  qty: number;
  size: {
    id: string;
    name: string;
  };
}

// Fetch production by ID
const fetchProduction = async () => {
  try {
    const data = await productionStore.fetchProductionsById(props.id);
    
    if (!data) {
      throw new Error('No production data received');
    }

    form.id = data.id;
    form.model_id = data.model_id;
    form.activity_role_id = data.activity_role_id;
    selectedModelId.value = data.model_id;

    // Set model sizes and form items together to maintain consistency
    if (Array.isArray(data.items)) {
      // First set the model sizes
      modelSizes.value = data.items.map((item: ProductionItem) => ({
        size_id: String(item.size_id),
        size_name: item.size.name,
        qty: item.qty
      }));

      // Then set form items using the same data
      form.items = data.items.map((item: ProductionItem) => ({
        size_id: String(item.size_id),
        qty: item.qty || 0
      }));
    } else {
      modelSizes.value = [];
      form.items = [];
    }
  } catch (err) {
    console.error('Failed to fetch production data', err);
    toast.error("Failed to fetch production data");
    modelSizes.value = [];
    form.items = [];
  }
};

const isInitializing = ref(true); // ✅ Tambahkan ini


// Handle model selection change
watch(selectedModelId, async (id) => {
  if (!id || isInitializing.value) return; // ⛔ Skip saat masih initializing

  try {
    const modelData = await modelStore.fetchModelById(Number(id));
    if (!modelData) throw new Error('No model data received');

    form.model_id = modelData.id;

    const existingItems = [...form.items];

    if (Array.isArray(modelData.sizes)) {
      modelSizes.value = modelData.sizes.map((size: any) => ({
        size_id: String(size.id),
        size_name: size.name,
        qty: existingItems.find(item => item.size_id === String(size.id))?.qty || 0
      }));

      form.items = modelData.sizes.map((size: any) => ({
        size_id: String(size.id),
        qty: existingItems.find(item => item.size_id === String(size.id))?.qty || 0
      }));
    } else {
      modelSizes.value = [];
      form.items = [];
    }
  } catch (err) {
    console.error('Failed to fetch model sizes', err);
    modelSizes.value = [];
    form.items = [];
  }
});


// Fetch activity role
onMounted(async () => {
  await modelStore.fetchModels();
  try {
    const res = await activityRoleStore.getActivityRoleById(Number(props.activity_role));
    activityRole.value = res;
  } catch (error) {
    console.error('Failed to fetch activity role', error);
  }
  await fetchProduction();
  isInitializing.value = false;
});

// Handle submit (Update production)
const submit = async () => {
  try {
    await productionStore.updateProduction(String(form.id), {
      model_id: form.model_id!,
      activity_role_id: form.activity_role_id,
      items: form.items.filter(item => item.qty > 0)
    });
    toast.success("Production updated successfully");
    window.location.href = `/production/${form.activity_role_id}`;
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to update production");
  }
};
</script>

<template>
  <Head title="Update Production" />
  <AppLayout :breadcrumbs="[{ title: 'Production', href: `/production/${props.activity_role}` }]">
    <div class="p-4 md:p-6 space-y-4 md:space-y-6">
      <h1 class="text-xl md:text-2xl font-bold">
        Update Production<span v-if="activityRole"> - {{ activityRole?.name }}</span>
      </h1>

      <!-- Model Selection -->
      <div class="w-full">
        <label class="block font-medium mb-1">Select Model</label>
        <select v-model="selectedModelId" class="border p-2 rounded w-full text-sm md:text-base">
          <option disabled value="">-- Choose a model --</option>
          <option v-for="model in models" :key="model.id" :value="model.id">
            {{ model.description }}
          </option>
        </select>
      </div>

      <!-- Model Sizes Table -->
      <div v-if="modelSizes.length > 0" class="overflow-x-auto">
        <h2 class="font-semibold mb-2">Model Sizes</h2>
        <table class="w-full table-auto border text-sm md:text-base">
          <thead>
            <tr class="bg-gray-100">
              <th class="border px-2 md:px-3 py-1 md:py-2 text-left">Size</th>
              <th class="border px-2 md:px-3 py-1 md:py-2 text-left">Qty</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(size, index) in form.items" :key="`size-${index}-${size.size_id}`">
              <td class="border px-2 md:px-3 py-1 md:py-2">
                {{ modelSizes.find(ms => ms.size_id === size.size_id)?.size_name || size.size_id }}
              </td>
              <td class="border px-2 md:px-3 py-1 md:py-2">
                <input
                  type="number"
                  min="0"
                  class="border rounded px-2 py-1 w-full"
                  v-model.number="size.qty"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Save Button -->
      <div class="flex gap-2">
        <button
          @click="submit"
          class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
          :disabled="form.processing"
        >
          Save
        </button>
        <button
          @click="router.visit(`/production/${props.activity_role}`)"
          type="button"
          class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600"
        >
          Cancel
        </button>
      </div>

    </div>
  </AppLayout>
</template>
