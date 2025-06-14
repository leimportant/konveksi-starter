<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useToast } from "@/composables/useToast";
import { useGoodReceiveStore } from '@/stores/useGoodReceiveStore';
import { useModelStore } from '@/stores/useModelStore';
import { useUomStore } from '@/stores/useUomStore';
import { storeToRefs } from 'pinia';

// Remove vue-router import and usage
const toast = useToast();
// Get the ID from the current route using Inertia's props
const props = defineProps<{
  id: number;
}>();

const goodReceiveStore = useGoodReceiveStore();
const modelStore = useModelStore();
const uomStore = useUomStore();

const { models } = storeToRefs(modelStore);
const { items: uoms } = storeToRefs(uomStore);

const form = useForm({
  date: '',
  model_id: 0,
  description: '',
  qty_base: 0,
  qty_convert: 0,
  uom_base: 0,
  uom_convert: 0,
  recipent: ''
});

const breadcrumbs = [
  { title: 'Good Receive', href: '/good-receive' },
  { title: 'Edit', href: `/good-receive/${props.id}/edit` }
];

onMounted(async () => {
  try {
    await Promise.all([
      modelStore.fetchModels(),
      uomStore.fetchUoms()
    ]);

    const gr = await goodReceiveStore.fetchGoodReceivesById(props.id);
    const goodReceive = gr.data;
  
    form.date = goodReceive.date ? goodReceive.date.split('T')[0] : '';;
    form.model_id = goodReceive.model_id;
    form.description = goodReceive.description;
    form.qty_base = goodReceive.qty_base;
    form.qty_convert = goodReceive.qty_convert;
    form.uom_base = goodReceive.uom_base;
    form.uom_convert = goodReceive.uom_convert;
    form.recipent = goodReceive.recipent;
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to fetch Good Receive data");
  }
});

const handleSubmit = async () => {
  if (!form.model_id) return toast.error("Model is required");
  if (!form.description) return toast.error("Description is required");
  if (!form.qty_base || form.qty_base <= 0) return toast.error("Base quantity must be greater than 0");
  if (!form.qty_convert || form.qty_convert <= 0) return toast.error("Convert quantity must be greater than 0");
  if (!form.uom_base) return toast.error("Base UOM is required");
  if (!form.uom_convert) return toast.error("Convert UOM is required");
  if (!form.recipent) return toast.error("Recipient is required");

  try {
    await goodReceiveStore.updateGoodReceive(props.id, {
      date: form.date,
      model_id: Number(form.model_id),
      description: form.description,
      recipent: form.recipent
    });
    toast.success("Good Receive updated successfully");
    window.location.href = '/good-receive';
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to update Good Receive");
  }
};
</script>

<template>
  <Head title="Edit Good Receive" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-4">
      <div class="mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold mb-6">Edit Good Receive</h2>
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
              <label class="block text-sm font-medium mb-1">Description</label>
              <textarea
                v-model="form.description"
                rows="3"
                class="w-full rounded-md border border-input px-3 py-2"
                required
              ></textarea>
            </div>

            <div class="grid grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium mb-1">Base Quantity</label>
                <Input type="number" v-model="form.qty_base" step="0.01" min="0" required />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Base UOM</label>
                <select v-model="form.uom_base" class="w-full rounded-md border border-input px-3 py-2" required>
                  <option value="0">Select UOM</option>
                  <option v-for="uom in uoms" :key="uom.id" :value="uom.id">
                    {{ uom.name }}
                  </option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium mb-1">Convert Quantity</label>
                <Input type="number" v-model="form.qty_convert" step="0.01" min="0" required />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Convert UOM</label>
                <select v-model="form.uom_convert" class="w-full rounded-md border border-input px-3 py-2" required>
                  <option value="0">Select UOM</option>
                  <option v-for="uom in uoms" :key="uom.id" :value="uom.id">
                    {{ uom.name }}
                  </option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Recipient</label>
              <Input v-model="form.recipent" required />
            </div>

            <div class="flex justify-end gap-4">
              <Button type="button" variant="outline" @click="$inertia.visit('/good-receive')">
                Cancel
              </Button>
              <Button type="submit">Update</Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>