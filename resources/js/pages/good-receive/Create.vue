<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useToast } from "@/composables/useToast";
import { useGoodReceiveStore } from '@/stores/useGoodReceiveStore';
import { useModelStore } from '@/stores/useModelStore';
import { useUomStore } from '@/stores/useUomStore';
import { storeToRefs } from 'pinia';

const toast = useToast();
const goodReceiveStore = useGoodReceiveStore();
const modelStore = useModelStore();
const uomStore = useUomStore();

const { models } = storeToRefs(modelStore);
const { items: uoms } = storeToRefs(uomStore);
interface ModelMaterial {
    product_id: number;
    item: string;
    qty: number;
    uom_id: string;
}

const modelMaterials = ref<ModelMaterial[]>([]);
const page = usePage();
const auth = page.props.auth as { user?: { name: string } };

const form = useForm({
  date: new Date().toISOString().split('T')[0],
  model_id: 0,
  description: '',
  recipent: auth.user?.name || '',
  items: [] as Array<{
    model_material_id: number;
    model_material_item: string;
    qty: number;
    qty_convert: number;
    uom_base: string;
    uom_convert: string;
  }>
});

// Watch for model selection changes
watch(() => form.model_id, async (newModelId) => {
  if (newModelId) {
    try {
      const response = await modelStore.fetchModelById(newModelId);
      modelMaterials.value = response.data;
      
      // Initialize form items
      form.items = modelMaterials.value.map(material => ({
        model_material_id: material.product_id,
        model_material_item: material.item,
        qty: material.qty,
        qty_convert: material.qty,
        uom_base: material.uom_id,
        uom_convert: 'YARD' // Default to YARD
      }));
    } catch (error) {
      console.error('Error fetching model materials:', error);
    }
  }
});

const breadcrumbs = [
  { title: 'Good Receive', href: '/good-receive' },
  { title: 'Create', href: '/good-receive/create' }
];

onMounted(async () => {
  await Promise.all([
    modelStore.fetchModels(),
    uomStore.fetchUoms()
  ]);
});

const handleSubmit = async () => {
  if (!form.model_id) return toast.error("Model is required");
  if (!form.recipent) return toast.error("Recipient is required");
  if (form.items.length === 0) return toast.error("At least one material item is required");

  try {
    await goodReceiveStore.createGoodReceive({
      date: form.date,
      model_id: Number(form.model_id),
      recipent: form.recipent,
      good_receive_items: form.items
    });
    toast.success("Good Receive created successfully");
    window.location.href = '/good-receive';
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to create Good Receive");
  }
};
</script>

<template>
  <Head title="Create Good Receive" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold mb-6">Create Good Receive</h2>
          <form @submit.prevent="handleSubmit" class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium mb-1">Date</label>
                <Input type="date" v-model="form.date" required />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Model</label>
                <select v-model="form.model_id" class="w-full rounded-md border border-input px-3 py-2" required>
                  <option value="0">Select Model</option>
                  <option v-for="model in models" :key="model.id" :value="model.id">
                    {{ model.description }}
                  </option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Penerima</label>
              <Input v-model="form.recipent" required />
            </div>

            <!-- Materials Table -->
            <div v-if="form.items.length > 0" class="mt-6">
              <h3 class="text-md font-medium mb-2">Material Items</h3>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Material</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty Base</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">UOM Base</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty Convert</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">UOM Convert</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="(item, index) in form.items" :key="index">
                      <td class="px-6 py-4 whitespace-nowrap">{{ item.model_material_id }}</td>
                      <td class="px-6 py-4 whitespace-nowrap">{{ item.model_material_item }}</td>
                      <td class="px-6 py-4 whitespace-nowrap">{{ item.qty }}</td>
                      <td class="px-6 py-4 whitespace-nowrap">{{ item.uom_base }}</td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <Input type="number" v-model="item.qty_convert" step="0.01" min="0" />
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <select v-model="item.uom_convert" class="w-full rounded-md border border-input px-3 py-2">
                          <option value="YARD">YARD</option>
                          <option v-for="uom in uoms" :key="uom.id" :value="uom.name">
                            {{ uom.name }}
                          </option>
                        </select>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="flex justify-end gap-4">
              <Button type="button" variant="outline" @click="$inertia.visit('/good-receive')">
                Cancel
              </Button>
              <Button type="submit">Create</Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>