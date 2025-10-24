<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { useModelStore } from '@/stores/useModelStore';
import { useProductionStore } from '@/stores/useProductionStore';
import { Head, router, useForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { onMounted, ref, Ref, watch } from 'vue';
import Vue3Select from 'vue3-select'
import 'vue3-select/dist/vue3-select.css'
import axios from 'axios';

const toast = useToast();
const modelStore = useModelStore();
const { models, employees } = storeToRefs(modelStore);


const props = defineProps<{
  activity_role: string | number;
}>();

const selectedModelId = ref<number | null>(null);
const selectedEmployeeId: Ref<number | { id: number; name: string }> = ref(0);

const modelSizes = ref<{ size_id: string; size_name: string; qty: number, variant: string}[]>([]);
const activityTasks = ref<any[]>([]);
const selectedTasks = ref<number[]>([]);

const form = useForm({
  model_id: null as number | null,
  activity_role_id: Number(props.activity_role),
  employee_id: null as number | null,
  items: [] as { size_id: string; qty: number, variant: string}[],
  remark: '', // <-- tambahkan properti remark di sini
});

const fetchActivityTasks = async (status = 'SEWING') => {
  try {
    const res = await axios.get('/api/activity-roles', { params: { group_menu: status } });
    activityTasks.value = res.data.data;
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Gagal mengambil data tugas finishing');
  }
};


onMounted(async () => {
  await modelStore.fetchModels({ page: 1, is_close: 'N' });
  
  if (props.activity_role === 'FINISHING') {
    await fetchActivityTasks('FINISHING');
  }
  if (props.activity_role === 'SEWING') {
    await fetchActivityTasks('SEWING');
  }
  await modelStore.fetchActivityEmployee(String(props.activity_role));
});

watch(selectedModelId, async (id) => {
  if (!id) return;
  try {
    const res = await modelStore.fetchModelById(id);
    const data = res.data;

    form.model_id = data.id;
    modelSizes.value = data.sizes;

    form.items = data.sizes.map((s: any) => ({
      size_id: String(s.size_id),
      size_name: s.size_name, // Add size_name field
      qty: 0,
      variant: s.variant,
    }));
  } catch (err) {
    console.error('Failed to fetch model sizes', err);
  }
});

const breadcrumbs = [{ title: 'Produksi', href: `/production/${props.activity_role}` }];

const productionStore = useProductionStore();

const submit = async () => {
  try {

    let taskIds: number | number[] = 1; // <-- allow both number and number[]
    if (props.activity_role == 'CUTTING') {
      taskIds = 1; // Assuming 'CUTTING' maps to activity role ID 1
    }
    if (props.activity_role == 'FINISHING' || props.activity_role == 'SEWING') {
      taskIds = selectedTasks.value
        .map((taskId) => activityTasks.value.find((t) => t.id === taskId)?.id)
        .filter((id): id is number => id !== null);
    }

    if (props.activity_role == 'LUBANG_KANCING') {
      taskIds = 4; // Assuming 'LUBANG_KANCING' maps to activity role ID 1
    }

   const employeeId =
  typeof selectedEmployeeId.value === 'number'
    ? selectedEmployeeId.value
    : selectedEmployeeId.value?.id ?? 0;

    await productionStore.createProduction({
      model_id: form.model_id!,
      activity_role_id: taskIds, // now can be number or number[]
      activity_role: String(props.activity_role),
      employee_id: employeeId, // send just the ID
      remark: form.remark || '',
      items: form.items.filter((item) => item.qty > 0),
    });


    toast.success('Produksi berhasil dibuat');
    router.visit(`/production/${props.activity_role}`);
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? 'Gagal membuat produksi');
  }
};
</script>

<template>

  <Head title="Production Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <h1 class="text-2xl font-bold">
         {{  props.activity_role.toString().replace(/_/g, ' ') ?? 'Buat Produksi' }}
       </h1>

      <div>
        <label class="mb-1 block font-medium">Pilih Desain Kerjaan</label>
         <!-- <Vue3Select id="model.id"
         v-model="selectedModelId"
         :reduce="(model: any) => model.id"
         :options="models" 
         label="description"
                placeholder="Select Model" class="w-full" /> -->

        <select v-model="selectedModelId" class="w-full rounded border p-2">
          <option disabled value="">-- Choose a model --</option>
          <option v-for="model in models" :key="model.id" :value="model.id">
            {{ model.description }}
          </option>
        </select>
      </div>

      <div>
        <label class="mb-1 block font-medium">Karyawan</label>
         <Vue3Select id="model.id"
         v-model="selectedEmployeeId"
         :options="employees" 
         label="name"
        placeholder="Select Employee" class="w-full" />

      </div>

      <div v-if="props.activity_role === 'QUALITY_CHECK'"
        class="mt-4 p-4 bg-yellow-50 border border-yellow-300 rounded text-sm text-yellow-700">
        <p>Periksa kualitas produk dengan teliti sebelum melanjutkan ke proses selanjutnya. Pastikan tidak ada cacat
          atau kekurangan.</p>
        <Input type="text" v-model="form.remark" placeholder="Tambahkan catatan atau remark jika diperlukan"
          class="mt-2 w-full rounded border p-2" />
      </div>

      <div v-if="activityTasks.length && props.activity_role === 'FINISHING' || props.activity_role === 'SEWING'"
        class="mt-4 p-4 bg-blue-50 border border-blue-300 rounded text-sm text-blue-700">
        <p class="font-semibold mb-2">Pilih pekerjaan yang dilakukan:</p>
        <div class="flex flex-col gap-2">
          <label v-for="task in activityTasks" :key="task.id" class="flex items-center gap-2">
            <input type="checkbox" v-model="selectedTasks" :value="task.id" />
            {{ task.name }}
          </label>
        </div>
      </div>

      <div v-if="modelSizes.length > 0">
        <h2 class="mb-2 font-semibold">Model Ukuran</h2>
        <table class="w-full table-auto border">
          <thead>
            <tr class="bg-gray-100">
              <th class="border px-3 text-sm py-2 text-left">Size</th>
              <th class="border px-3 text-sm py-2 text-left">Variant</th>
              <th class="border px-3 text-sm py-2 text-left">Qty</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(size, index) in form.items" :key="`size-${index}-${size.size_id}`">
              <td class="border px-3 py-2 text-sm">
                {{modelSizes.find((ms) => ms.size_id === size.size_id)?.size_name || size.size_id}}
              </td>
              <td class="border px-3 py-2 text-sm">
                {{ size.variant }}
              </td>
              <td class="border px-3 py-2 text-sm">
                <input type="number" min="0" class="w-full rounded border px-2 py-1" v-model.number="size.qty" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex gap-2">
        <button @click="submit" class="rounded text-sm bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
          :disabled="form.processing">Simpan</button>
        <button @click="router.visit(`/production/${props.activity_role}`)" type="button"
          class="rounded text-sm bg-gray-500 px-4 py-2 text-white hover:bg-gray-600">
          Cancel
        </button>
      </div>
    </div>
  </AppLayout>
</template>
