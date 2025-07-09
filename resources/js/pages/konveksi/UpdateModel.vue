<template>

  <Head title="Edit Model" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
      <div class="border-b pb-4">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Edit Model</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Edit data model, ukuran, dan aktivitas</p>
      </div>

      <!-- Tabs -->
      <div class="my-6 flex space-x-4 border-b dark:border-gray-700">
        <button v-for="tab in tabs" :key="tab" class="pb-2 text-sm font-medium" :class="{
          'border-b-2 border-primary text-primary': activeTab === tab,
          'text-gray-500 hover:text-gray-700': activeTab !== tab
        }" @click="activeTab = tab">
          {{ tab.charAt(0).toUpperCase() + tab.slice(1) }}
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Model Tab -->
        <div v-if="activeTab === 'model'" class="space-y-6">
          <div>
            <label for="description" class="text-sm font-medium">Nama Model</label>
            <Input id="description" v-model="form.description" placeholder="" />
            <small class="text-destructive" v-if="errors.description">{{ errors.description[0] }}</small>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="start_date" class="text-sm font-medium">Tanggal Mulai</label>
              <DateInput v-model="form.start_date" id="start_date"
                :class="{ 'border-destructive': errors.start_date }" />
              <small class="text-destructive" v-if="errors.start_date">{{ errors.start_date[0] }}</small>
            </div>
            <div>
              <label for="end_date" class="text-sm font-medium">Estimasi Selesai</label>
              <DateInput v-model="form.end_date" id="end_date"
                :class="{ 'border-destructive': errors.end_date }" />
              <small class="text-destructive" v-if="errors.end_date">{{ errors.end_date[0] }}</small>
            </div>
            <div>
              <label for="estimation_price_pcs" class="text-sm font-medium">Estimasi Harga</label>
              <Input type="number" v-model="form.estimation_price_pcs" id="estimation_price_pcs"
                :class="{ 'border-destructive': errors.estimation_price_pcs }" />
              <small class="text-destructive" v-if="errors.estimation_price_pcs">
                {{ errors.estimation_price_pcs[0] }}
              </small>
            </div>
          </div>

          <div>
            <label for="remark" class="text-sm font-medium">Catatan</label>
            <Input v-model="form.remark" id="remark" placeholder="" :class="{ 'border-destructive': errors.remark }" />
            <small class="text-destructive" v-if="errors.remark">{{ errors.remark[0] }}</small>
          </div>
        </div>

        <!-- Size Tab -->
        <div v-if="activeTab === 'size'">
           <SizeTab v-model="sizeItems" @update:totalQuantity="totalProduction = $event" />
        </div>

        <!-- Activity Tab -->
        <div v-if="activeTab === 'activity'">
          <ActivityTab v-model="activityItems" />
        </div>

        <!-- Document Tab -->

        <div v-if="activeTab === 'gambar'" class="space-y-6">
          <DocumentList :reference-id="currentProductIdForUpload" :reference-type="referenceType" />
        </div>

        <div v-if="activeTab === 'bahan dan biaya'">
          <ModelMaterialTab v-model="modelMaterials" />
        </div>

        <!-- Add HPP Tab -->
        <div v-if="activeTab === 'hpp'">
          <HPPTab 
            :model-materials="modelMaterials"
            :activity-items="activityItems.map(item => ({
              ...item,
              activity_name: getActivityName(item.activity_role_id)
            }))"
            :start-date="form.start_date"
            :end-date="form.end_date"
            :total-production="totalProduction"
            :size-items="sizeItems.map(item => ({
              ...item,
            }))"
          />
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-end gap-2">
          <Button type="button" variant="outline" @click="router.visit('/konveksi/model/list')">
            Batal
          </Button>
          <Button type="submit" class="bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">Simpan Perubahan</Button>
        </div>
      </form>
    </div>


  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { DateInput } from '@/components/ui/date-input';
import { Button } from '@/components/ui/button';
import DocumentList from '@/components/DocumentList.vue';
import SizeTab from '@/components/SizeTab.vue';
import ActivityTab from '@/components/ActivityTab.vue';
import ModelMaterialTab from '@/components/ModelMaterialTab.vue';
import HPPTab from '@/components/HPPTab.vue';
import { useModelStore } from '@/stores/useModelStore';
import { useToast } from '@/composables/useToast';
import { type BreadcrumbItem } from '@/types';

// Props
// Jika Anda menginginkan hanya tipe number:
const props = defineProps<{
  modelId: number;
}>();

const modelId = ref(Number(props.modelId));  // Pastikan tipe data diubah ke Number
const currentProductIdForUpload =  (modelId.value ?? '');
// Tabs setup
const tabs = ['model', 'size', 'activity', 'gambar', 'bahan dan biaya', 'hpp'] as const;
type Tab = typeof tabs[number];
const activeTab = ref<Tab>(tabs[0]);
const errors = ref<Record<string, string[]>>({});

const referenceType = 'Model';
// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Konveksi', href: '/konveksi' },
  { title: 'Edit Model', href: `/konveksi/model/${props.modelId}/edit` }
];

// Stores & Toast
const modelStore = useModelStore();
const toast = useToast();

const cleanMaterialData = (materials: any[]) => {
  return materials.map(m => ({
    ...m,
    product_id: typeof m.product_id === 'object' ? m.product_id.id : m.product_id,
  }));
};

// Form setup
const form = useForm({
  description: '',
  remark: '',
  start_date: null,
  end_date: null,  // Added end_date
  estimation_price_pcs: 0,
  estimation_qty: 1,
});

// Document handling
const uploadedDocuments = ref<{ id: string; url: string; filename: string }[]>([]);

// Size items
const sizeItems = ref<{ size_id: string; qty: number, variant: string }[]>([]);

// Total production quantity from size items
const totalProduction = ref<number>(0);

// Activity items
const activityItems = ref<{
  activity_role_id: number;
  price: number;
  activity_name?: string;  // Added activity_name as optional property
}[]>([]);


const modelMaterials = ref<{
  product_id: number;
  qty: number;
  uom_id: number;
  remark: string;
  price: number | null;  // Added price property
}[]>([]);
// Submit handler
const handleSubmit = async () => {
  try {
    await modelStore.updateModel(props.modelId, {
      ...form,
      sizes: sizeItems.value,
      activity: activityItems.value,  // Changed from 'activities' to 'activity'
      documents: uploadedDocuments.value,
      modelMaterials: cleanMaterialData(modelMaterials.value),
    });

    toast.success('Model berhasil diperbarui');
    router.visit('/konveksi/model/list');
  } catch (error: any) {
    if (error.response?.status === 422 && error.response?.data?.errors) {
      errors.value = error.response.data.errors;

      // Iterate and display all error messages
      for (const key in errors.value) {
        errors.value[key].forEach((errorMsg: string) => {
          toast.error(errorMsg);
        });
      }
    } 
     // Jika ada message dari backend (misal error 400, 500, dll)
      else if (error.response?.data?.message) {
        toast.error(error.response.data.message);
      } else {
          toast.error('Terjadi kesalahan saat menyimpan model');
        }
    }
};

// Initialize data
onMounted(async () => {
  try {
    const model = await modelStore.fetchModelById(props.modelId);
    console.log('Model Data:', model);
    if (model) {
      form.description = model.data.description;
      form.remark = model.data.remark;
      form.start_date = model.data.start_date
        ? model.data.start_date.split('T')[0]
        : '';
      form.end_date = model.data.end_date  // Added end_date handling
        ? model.data.end_date.split('T')[0]
        : '';
      form.estimation_price_pcs = model.data.estimation_price_pcs;
      form.estimation_qty = model.data.estimation_qty;

      sizeItems.value = model.data.sizes || [];
      activityItems.value = model.data.activities || [];
      uploadedDocuments.value = model.data.documents || [];
      modelMaterials.value = model.data.model_material || [];
    }
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
      toast.error('Validasi gagal, silakan periksa form');
    } else {
      toast.error('Terjadi kesalahan saat membuat model');
    }
  }
});

// Add getActivityName function
const getActivityName = (activityRoleId: number) => {
  const activity = activityItems.value.find(item => item.activity_role_id === activityRoleId);
  return activity?.activity_name || `Activity ${activityRoleId}`;
};
</script>