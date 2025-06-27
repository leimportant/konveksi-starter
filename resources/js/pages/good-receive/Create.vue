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
import { useProductStore } from '@/stores/useProductStore'; // Add this import
import { storeToRefs } from 'pinia';

const toast = useToast();
const goodReceiveStore = useGoodReceiveStore();
const modelStore = useModelStore();
const uomStore = useUomStore();
const productStore = useProductStore(); // Initialize product store

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
    model_material_desc: string;
    model_material_item: number;
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
      const materials = response.data?.model_material || [];
      modelMaterials.value = materials;
      
      form.items = await Promise.all(materials.map(async (material: ModelMaterial) => {
        try {
          const product = await productStore.fetchProductById(material.product_id);
          return {
            model_material_id: material.product_id,
            model_material_desc: product?.name || material.item.toString(),
            model_material_item: material.item,
            qty: material.qty,
            qty_convert: material.qty,
            uom_base: material.uom_id,
            uom_convert: 'YARD'
          };
        } catch (error) {
          console.error('Error fetching product:', error);
          return {
            model_material_id: material.product_id,
            model_material_desc: material.item.toString(),
            model_material_item: material.item,
            qty: material.qty,
            qty_convert: material.qty,
            uom_base: material.uom_id,
            uom_convert: 'YARD'
          };
        }
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
  if (!form.recipent) return toast.error("Penerima is required");
  if (form.items.length === 0) return toast.error("Minimal 1 material item");

  try {
    await goodReceiveStore.createGoodReceive({
      date: form.date,
      model_id: Number(form.model_id),
      description: form.description,
      recipent: form.recipent,
      items: form.items.map(item => ({
        model_material_id: item.model_material_id,
        model_material_desc: item.model_material_desc,
        model_material_item: item.model_material_item,
        qty: item.qty,
        qty_convert: item.qty_convert,
        qty_base: item.qty,
        uom_base: item.uom_base,
        uom_convert: item.uom_convert
      }))
    });

    toast.success("Good Receive created successfully");
    window.location.href = '/good-receive';
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to create Good Receive");
  }
};
</script>

<template>
  <Head title="Buat Penerimaan Barang / Kain" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-2 py-4 sm:px-4 sm:py-6">
      <div class="mx-auto">
        <div class="bg-white rounded-lg shadow p-4 sm:p-6">
          <h2 class="text-base sm:text-lg font-semibold mb-4 sm:mb-6">Penerimaan Kain</h2>
          <form @submit.prevent="handleSubmit" class="space-y-4 sm:space-y-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6">
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
            <div v-if="form.items.length > 0" class="mt-4 sm:mt-6">
              <h3 class="text-sm sm:text-md font-medium mb-2">Material Items</h3>
              <div class="overflow-x-auto">
                <div class="min-w-full inline-block align-middle">
                  <div class="border rounded-lg overflow-hidden sm:border-0 sm:rounded-none">
                    <table class="min-w-full divide-y divide-gray-200">
                      <thead class="bg-gray-50 hidden sm:table-header-group">
                        <tr>
                          <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Material</th>
                          <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                          <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                          <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">UOM</th>
                          <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty Conv</th>
                          <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">UOM Conv</th>
                        </tr>
                      </thead>
                      <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="(item, index) in form.items" :key="index" class="block sm:table-row mb-2 sm:mb-0 border-b sm:border-b-0">
                          <td class="px-2 py-1 sm:px-3 sm:py-2 whitespace-nowrap block sm:table-cell">
                            <div class="sm:hidden">
                              <div class="font-semibold">{{ item.model_material_id }} - {{ item.model_material_desc }}</div>
                              <div class="text-sm mt-1">{{ item.qty }} {{ item.uom_base }}</div>
                            </div>
                            <div class="hidden sm:block">
                              {{ item.model_material_id }} - {{ item.model_material_desc }}
                            </div>
                          </td>
                          <td class="px-3 py-2 whitespace-nowrap hidden sm:table-cell">{{ item.model_material_item }}</td>
                          <td class="px-3 py-2 whitespace-nowrap hidden sm:table-cell">{{ item.qty }}</td>
                          <td class="px-3 py-2 whitespace-nowrap hidden sm:table-cell">{{ item.uom_base }}</td>
                          <td class="px-3 py-2 whitespace-nowrap sm:table-cell">
                            <Input type="number" v-model="item.qty_convert" step="0.01" min="0" class="w-full" />
                          </td>
                          <td class="px-3 py-2 whitespace-nowrap sm:table-cell">
                            <select v-model="item.uom_convert" class="w-full rounded-md border border-input px-2 py-1 text-sm">
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
              </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-4">
              <Button type="button" variant="outline" @click="$inertia.visit('/good-receive')">
                Cancel
              </Button>
              <Button type="submit" class="bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">Simpan</Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
@media (max-width: 640px) {
  tr.block {
    display: block;
    padding: 0.5rem;
    border-bottom: 1px solid #e5e7eb;
  }
  
  tr.block > td {
    display: block;
    padding: 0.25rem 0;
  }
  
  tr.block > td:not([class*="hidden"]) {
    display: block;
    padding: 0.25rem 0;
  }

  tr.block > td:before {
    content: attr(data-label);
    font-weight: 600;
    display: inline-block;
    width: 30%;
  }
  
  .hidden-mobile {
    display: none;
  }
}
</style>