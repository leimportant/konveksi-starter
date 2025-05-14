<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useForm, router } from '@inertiajs/vue3';
import { useModelStore } from '@/stores/useModelStore';
import { useMasterActivityRoleStore } from '@/stores/useMasterActivityRoleStore';
import { storeToRefs } from 'pinia';
import { useToast } from "@/composables/useToast";
import { useProductionStore } from '@/stores/useProductionStore';

const toast = useToast();
const modelStore = useModelStore();
const activityRoleStore = useMasterActivityRoleStore();
const { models } = storeToRefs(modelStore);

const props = defineProps<{
  activity_role: string | number
}>();

const selectedModelId = ref<number | null>(null);
const modelSizes = ref<{ size_id: string; size_name: string; qty: number }[]>([]);
const activityRole = ref<{ id: number; name: string } | null>(null);

const form = useForm({
  model_id: null as number | null,
  activity_role_id: Number(props.activity_role),
  items: [] as { size_id: string; qty: number }[] // Changed to string
});

onMounted(async () => {
  await modelStore.fetchModels();
  try {
    const res = await activityRoleStore.getActivityRoleById(Number(props.activity_role));
    activityRole.value = res;
  } catch (error) {
    console.error('Failed to fetch activity role', error);
  }
});

watch(selectedModelId, async (id) => {
  if (!id) return;
  try {
    const res = await modelStore.fetchModelById(id);
    const data = res.data;

    form.model_id = data.id;
    modelSizes.value = data.sizes;

    form.items = data.sizes.map((s: any) => ({
      size_id: String(s.size_id), // Ensure string conversion
      qty: 0
    }));
  } catch (err) {
    console.error('Failed to fetch model sizes', err);
  }
});

const breadcrumbs = [
  { title: 'Production', href: `/production/${props.activity_role}` }
];

const productionStore = useProductionStore();

const submit = async () => {
  try {
    await productionStore.createProduction({
      model_id: form.model_id!,
      activity_role_id: form.activity_role_id,
      remark: "",
      items: form.items.filter(item => item.qty > 0)
    });
    toast.success("Production created successfully");
    router.visit(`/production/${props.activity_role}`);
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to create production");
  }
};
</script>

<template>
  <Head title="Production Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-6">
      <h1 class="text-2xl font-bold">
        Create Production<span v-if="activityRole"> - {{ activityRole?.name }}</span>
      </h1>

      <div>
        <label class="block font-medium mb-1">Select Model</label>
        <select v-model="selectedModelId" class="border p-2 rounded w-full">
          <option disabled value="">-- Choose a model --</option>
          <option v-for="model in models" :key="model.id" :value="model.id">
            {{ model.description }}
          </option>
        </select>
      </div>

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
            <tr v-for="(size, index) in form.items" :key="`size-${index}-${size.size_id}`">
              <td class="border px-3 py-2">
                {{ modelSizes.find(ms => ms.size_id === size.size_id)?.size_name || size.size_id }}
              </td>
              <td class="border px-3 py-2">
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
