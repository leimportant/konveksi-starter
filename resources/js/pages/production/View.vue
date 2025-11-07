<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useToast } from "@/composables/useToast";
import { useProductionStore } from '@/stores/useProductionStore';
import { useModelStore } from '@/stores/useModelStore';
import { useMasterActivityRoleStore } from '@/stores/useMasterActivityRoleStore';
// import { storeToRefs } from 'pinia';
import AppLayout from '@/layouts/AppLayout.vue';

const toast = useToast();
const productionStore = useProductionStore();
const modelStore = useModelStore();
const activityRoleStore = useMasterActivityRoleStore();

// const { models } = storeToRefs(modelStore);

// Define props
const props = defineProps<{
  id: string;
  activity_role: string;
}>();

const production = ref<any>(null);
const activityRole = ref<any>(null);
const model = ref<any>(null);


function formatRupiah(value: number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(value);
}

const fetchData = async () => {
  try {
    const prod = await productionStore.fetchProductionsById(props.id);
    production.value = prod;

    const modelData = await modelStore.fetchModelById(prod.model_id);
    model.value = modelData;

    const role = await activityRoleStore.getActivityRoleById(Number(props.activity_role));
    activityRole.value = role;
  } catch (error) {
    console.error('Failed to fetch activity role', error);
    toast.error('Failed to fetch data');
  }
};

const printPage = () => {
  const originalContent = document.body.innerHTML;
  const printContent = document.getElementById('print-area')?.innerHTML || '';

  document.body.innerHTML = printContent;
  window.print();
  document.body.innerHTML = originalContent;

  // Optional: Reload to re-bind Vue events
  location.reload();
};


onMounted(fetchData);
</script>

<template>

  <Head title="View Production" />
  <AppLayout :breadcrumbs="[{ title: 'Production', href: `/production/${props.activity_role}` }]">
    <div id="print-area" class="p-4 md:p-6 space-y-4 md:space-y-6 print:bg-white">
      <div class="flex justify-between items-center">
        <h1 class="text-xl md:text-2xl font-bold">
          Hasil Produksi
          <!-- <span v-if="activityRole"> - {{ activityRole?.name }}</span> -->
        </h1>
        <div class="hidden print:block">Printed on: {{ new Date().toLocaleDateString('id-ID') }}</div>
      </div>

      <!-- Production Info -->
      <div
        class="w-full max-w-lg bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-xl shadow-sm p-4 text-sm md:text-base">
        <div class="grid grid-cols-[150px_1fr] gap-y-2">
          <div class="text-gray-500 dark:text-gray-400 font-medium">Model</div>
          <div class="text-gray-900 dark:text-white font-semibold">{{ production?.model?.description || '-' }}</div>

          <div class="text-gray-500 dark:text-gray-400 font-medium">Activity</div>
          <div class="text-gray-900 dark:text-white font-semibold">{{ activityRole?.name || '-' }}</div>

          <div class="text-gray-500 dark:text-gray-400 font-medium">Tanggal</div>
          <div class="text-gray-900 dark:text-white font-semibold">
            {{ production?.created_at ? new Date(production.created_at).toLocaleDateString('id-ID') : '-' }}
          </div>

          <div class="text-gray-500 dark:text-gray-400 font-medium">Harga</div>
          <div class="text-emerald-600 dark:text-emerald-400 font-semibold">
            {{ formatRupiah(production?.price_per_pcs || 0) }}
          </div>

          <div class="text-gray-500 dark:text-gray-400 font-medium">Total</div>
          <div class="text-indigo-600 dark:text-indigo-400 font-bold">
            {{ production?.total_price || 0 }}
          </div>
        </div>
      </div>


      <!-- Size Table -->
      <div v-if="production?.items?.length" class="overflow-x-auto">
        <h2 class="font-semibold mb-2">Production Sizes</h2>
        <table class="w-full table-auto border text-sm md:">
          <thead>
            <tr class="bg-gray-100">
              <th class="border px-2 md:px-3 py-1 md:py-2 text-left">Size</th>
              <th class="border px-2 md:px-3 py-1 md:py-2 text-left">Variant</th> 
              <th class="border px-2 md:px-3 py-1 md:py-2 text-left">Qty</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in production.items" :key="item.size_id">
              <td class="border px-2 md:px-3 py-1 md:py-2">{{ item.size?.name || item.size_id }}</td>
              <td class="border px-2 md:px-3 py-1 md:py-2">{{ item.variant || '-' }}</td>
              <td class="border px-2 md:px-3 py-1 md:py-2">{{ item.qty }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Action Buttons -->
      <div class="flex gap-2 no-print">
        <button @click="printPage" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
          Print
        </button>
        <button @click="router.visit(`/production/${props.activity_role}`)" type="button"
          class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
          Back
        </button>
      </div>
    </div>
  </AppLayout>
</template>

<style>
@media print {
  .no-print {
    display: none !important;
  }

  .print\:bg-white {
    background-color: white !important;
  }

  body {
    margin: 0;
  }
}
</style>
