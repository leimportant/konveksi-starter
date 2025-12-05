<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
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

const props = defineProps<{
  id: string;
  activity_role: string;
}>();

const selectedModelId = ref<number | null>(null);
const modelSizes = ref<{ size_id: string; size_name: string; qty: number }[]>([]);
const activityRole = ref<{ id: number; name: string } | null>(null);

// FORM SETUP
const form = useForm({
  id: null as number | null,
  model_id: null as number | null,
  activity_role_id: Number(props.activity_role),
  items: [] as { size_id: string; qty: number; variant: string }[]
});

interface ProductionItemApiResponse {
  id: string;
  production_id: string;
  size_id: string;
  variant: string;
  qty: number;
  size: { id: string; name: string };
}

// state to prevent watcher spam
const isInitializing = ref(true);

/**
 * FETCH PRODUCTION
 */
const fetchProduction = async () => {
  try {
    const data = await productionStore.fetchProductionsById(props.id);

    form.id = data.id;
    form.model_id = data.model_id;
    form.activity_role_id = data.activity_role_id;

    selectedModelId.value = data.model_id; // watcher will skip during initializing

    if (Array.isArray(data.items)) {
      modelSizes.value = data.items.map((item: ProductionItemApiResponse) => ({
        size_id: String(item.size_id),
        size_name: item.size.name,
        qty: item.qty
      }));

      form.items = data.items.map((item: ProductionItemApiResponse) => ({
        size_id: String(item.size_id),
        qty: item.qty,
        variant: item.variant || ''
      }));
    }
  } catch (err: any) {
    console.error("Failed to fetch model sizes:", err);
    toast.error("Failed to fetch production data: " + (err?.message || err));
}
};

/**
 * WATCH MODEL CHANGES
 */
watch(selectedModelId, async (id) => {
  if (!id || isInitializing.value) return;

  try {
    const modelData = await modelStore.fetchModelById(Number(id));
    if (!modelData) return;

    form.model_id = modelData.id;

    const oldItems = [...form.items];

    modelSizes.value = modelData.sizes.map((size: any) => ({
      size_id: String(size.id),
      size_name: size.name,
      qty: oldItems.find(i => i.size_id === String(size.id))?.qty || 0
    }));

    form.items = modelData.sizes.map((size: any) => ({
      size_id: String(size.id),
      qty: oldItems.find(i => i.size_id === String(size.id))?.qty || 0,
      variant: oldItems.find(i => i.size_id === String(size.id))?.variant || ''
    }));
  } catch (err) {
    console.error("Failed to fetch model sizes:", err);
    toast.error("Failed to fetch production data");
    form.items = [];
    modelSizes.value = [];
  }
});

/**
 * INITIAL PAGE LOAD
 */
onMounted(async () => {
  await modelStore.fetchModels({ page: 1, is_close: 'N' });

  try {
    activityRole.value = await activityRoleStore.getActivityRoleById(
      Number(props.activity_role)
    );
  } catch {}

  await fetchProduction();

  isInitializing.value = false;
});

/**
 * UPDATE SUBMIT
 */
const submit = async () => {
  try {
    // keep only valid items (qty > 0)
    const items = form.items.map(i => ({
      size_id: i.size_id,
      qty: Number(i.qty) || 0,
      variant: i.variant || ''
    })).filter(i => i.qty > 0);

    await productionStore.updateProduction(String(form.id), {
      model_id: form.model_id!,
      activity_role_id: form.activity_role_id,
      items
    });

    toast.success("Production updated successfully");

    router.visit(`/production/${props.activity_role}`);

  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to update production");
  }
};
</script>

<template>
  <Head title="Update Production" />

  <AppLayout :breadcrumbs="[{ title: 'Production', href: `/production/${props.activity_role}` }]">
    <div class="p-4 md:p-6 space-y-6">
      <h1 class="text-xl md:text-2xl font-bold">
        Update Production <span v-if="activityRole">- {{ activityRole.name }}</span>
      </h1>

      <!-- MODEL SELECT -->
      <div>
        <label class="block font-medium mb-1">Select Model</label>
        <select v-model="selectedModelId" class="border p-2 rounded w-full text-sm">
          <option disabled value="">-- Choose a model --</option>
          <option v-for="m in models" :key="m.id" :value="m.id">
            {{ m.description }}
          </option>
        </select>
      </div>

      <!-- SIZE TABLE -->
      <div v-if="modelSizes.length > 0" class="overflow-x-auto">
        <h2 class="font-semibold mb-2">Model Sizes</h2>
        <table class="w-full table-auto border text-sm">
          <thead>
            <tr class="bg-gray-100">
              <th class="border px-3 py-2 text-left">Size</th>
              <th class="border px-3 py-2 text-left">Variant</th>
              <th class="border px-3 py-2 text-left">Qty</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, idx) in form.items" :key="idx">
              <td class="border px-3 py-2">
                {{ modelSizes.find(s => s.size_id === item.size_id)?.size_name }}
              </td>
              <td class="border px-3 py-2">
                {{ item.variant }}
              </td>
              <td class="border px-3 py-2">
                <input v-model.number="item.qty" min="0" type="number" class="border rounded px-2 w-full" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- BUTTONS -->
      <div class="flex gap-2">
        <button @click="submit" class="bg-blue-600 text-white px-4 py-2 rounded" :disabled="form.processing">
          Save
        </button>

        <button @click="router.visit(`/production/${props.activity_role}`)" type="button"
          class="bg-gray-500 text-white px-4 py-2 rounded">
          Cancel
        </button>
      </div>
    </div>
  </AppLayout>
</template>
