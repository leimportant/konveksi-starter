<template>
  <Head :title="isEditMode ? 'Edit Model' : 'Create Model'" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
      <div class="border-b pb-4">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
          {{ isEditMode ? 'Edit Model' : 'Buat Model Baru' }}
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          {{ isEditMode ? 'Ubah data model, ukuran, dan aktivitas' : 'Isi data model, ukuran, dan aktivitas' }}
        </p>
      </div>

      <!-- Tabs -->
      <div class="my-6 flex space-x-4 border-b dark:border-gray-700">
        <button
          v-for="tab in tabs"
          :key="tab"
          class="pb-2 text-sm font-medium"
          :class="{
            'border-b-2 border-primary text-primary': activeTab === tab,
            'text-gray-500 hover:text-gray-700': activeTab !== tab
          }"
          @click="activeTab = tab"
        >
          {{ tab.charAt(0).toUpperCase() + tab.slice(1) }}
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Model Tab -->
        <div v-if="activeTab === 'model'" class="space-y-6">
          <div>
            <label for="description" class="text-sm font-medium">Nama Model</label>
            <Input id="description" v-model="form.description" />
            <small class="text-destructive" v-if="errors.description">{{ errors.description[0] }}</small>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="start_date" class="text-sm font-medium">Tanggal Mulai</label>
              <DateInput id="start_date" v-model="form.start_date" />
              <small class="text-destructive" v-if="errors.start_date">{{ errors.start_date[0] }}</small>
            </div>
            <div>
              <label for="estimation_price_pcs" class="text-sm font-medium">Estimasi Harga</label>
              <Input id="estimation_price_pcs" type="number" v-model="form.estimation_price_pcs" />
              <small class="text-destructive" v-if="errors.estimation_price_pcs">
                {{ errors.estimation_price_pcs[0] }}
              </small>
            </div>
          </div>

          <div>
            <label for="remark" class="text-sm font-medium">Catatan</label>
            <Input id="remark" v-model="form.remark" />
            <small class="text-destructive" v-if="errors.remark">{{ errors.remark[0] }}</small>
          </div>

          <div v-if="uploadedDocuments.length" class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div
              v-for="(doc, idx) in uploadedDocuments"
              :key="doc.id"
              class="relative group border rounded-xl overflow-hidden"
            >
              <img :src="doc.url" class="object-cover w-full aspect-square transition-transform group-hover:scale-105" />
              <div class="absolute inset-x-0 bottom-0 bg-black/50 p-2 text-white text-xs truncate">{{ doc.filename }}</div>
              <Button variant="destructive" size="icon" class="absolute top-2 right-2" @click.prevent="removeDocument(idx)">
                <i class="pi pi-times" />
              </Button>
            </div>
          </div>
        </div>

        <!-- Size Tab -->
        <div v-if="activeTab === 'size'">
          <SizeTab v-model="sizeItems" />
        </div>

        <!-- Activity Tab -->
        <div v-if="activeTab === 'activity'">
          <ActivityTab v-model="activityItems" />
        </div>

        <!-- Document Tab -->
        <div v-if="activeTab === 'document'" class="space-y-6">
          <DocumentUpload
            v-model:visible="showUploadDialog"
            :reference-id="'MODEL'"
            :doc-id="generatedDocId"
            module="model"
            accept="image/*"
            @uploaded="handleDocumentUploaded"
            @removed="handleDocumentRemoved"
          />
        </div>

        <!-- Material Tab -->
        <div v-if="activeTab === 'bahan_dan_biaya'">
          <ModelMaterialTab v-model="modelMaterials" />
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-end border-t pt-6 dark:border-gray-300 space-x-2">
          <Button type="button" variant="secondary" @click="router.visit('/konveksi')">Batal</Button>
          <Button type="submit" :loading="form.processing">
            <i class="pi pi-check" /> {{ isEditMode ? 'Perbarui' : 'Simpan' }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { DateInput } from '@/components/ui/date-input';
import { Button } from '@/components/ui/button';
import DocumentUpload from '@/components/DocumentUpload.vue';
import SizeTab from '@/components/SizeTab.vue';
import ActivityTab from '@/components/ActivityTab.vue';
import { useModelStore } from '@/stores/useModelStore';
import { useToast } from '@/composables/useToast';
import { type BreadcrumbItem } from '@/types';
import ModelMaterialTab from '@/components/ModelMaterialTab.vue';

// Props for edit mode
const props = defineProps<{
  modelData?: any;
}>();

// Tabs setup
const tabs = ['model', 'size', 'activity', 'document', 'bahan_dan_biaya'] as const;
type Tab = typeof tabs[number];
const activeTab = ref<Tab>('model');



// Determine mode
const isEditMode = computed(() => !!props.modelData);

// Document handling
const uploadedDocuments = ref<{ id: string; url: string; filename: string }[]>([]);
const showUploadDialog = ref(false);
const generatedDocId = computed(() => (isEditMode.value ? props.modelData.id : 'MDL-' + Date.now()));

const handleDocumentUploaded = (doc: any) => uploadedDocuments.value.push(doc);
const handleDocumentRemoved = (docId: string) => {
  uploadedDocuments.value = uploadedDocuments.value.filter(doc => doc.id !== docId);
};
const removeDocument = (idx: number) => uploadedDocuments.value.splice(idx, 1);

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Konveksi', href: '/konveksi' },
  {
    title: isEditMode.value ? 'Edit Model' : 'Create Model',
    href: isEditMode.value ? `/konveksi/model/${props.modelData.id}/edit` : '/konveksi/model/create',
  },
];

// Stores & Toast
const modelStore = useModelStore();
const toast = useToast();

// Form setup
const form = useForm({
  description: '',
  remark: '',
  start_date: null,
  estimation_price_pcs: 0,
  estimation_qty: 1,
});
const errors = ref<Record<string, string[]>>({});

// Size items
const sizeItems = ref<{ size_id: number; qty: number }[]>([{ size_id: 0, qty: 0 }]);

// Activity items
const activityItems = ref<{ activity_role_id: number; price: number }[]>([]);

const modelMaterials = ref<{
  product_id: number;
  qty: number;
  uom_id: number;
  remark: string;
}[]>([]);

// Initialize form for edit
onMounted(() => {
  if (isEditMode.value) {
    form.description = props.modelData.description;
    form.remark = props.modelData.remark;
    form.start_date = props.modelData.start_date;
    form.estimation_price_pcs = props.modelData.estimation_price_pcs;
    form.estimation_qty = props.modelData.estimation_qty;
    sizeItems.value = props.modelData.sizes || [];
    activityItems.value = props.modelData.activity || [];
    modelMaterials.value = props.modelData.modelMaterials || [];
    uploadedDocuments.value = props.modelData.documents || [];
  }
});

// Submit handler
const handleSubmit = async () => {
  if (!form.start_date) {
    errors.value.start_date = ['Tanggal mulai harus diisi'];
    toast.error('Tanggal mulai harus diisi');
    return;
  }

  try {
    if (isEditMode.value) {
      await modelStore.updateModel(props.modelData.id, {
        ...form,
        sizes: sizeItems.value,
        activity: activityItems.value,
        documents: uploadedDocuments.value,
        modelMaterials: modelMaterials.value,  // Changed from modelMaterial to modelMaterials
      });
      toast.success('Model berhasil diperbarui');
    } else {
      await modelStore.createModel({
        ...form,
        sizes: sizeItems.value,
        activity: activityItems.value,
        documents: uploadedDocuments.value,
        modelMaterials: modelMaterials.value,  // Changed from modelMaterial to modelMaterials
      });
      toast.success('Model berhasil dibuat');
    }
    router.visit('/konveksi/model/list');
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
      toast.error('Validasi gagal, silakan periksa form');
    } else {
      toast.error('Terjadi kesalahan saat menyimpan model');
    }
  }
};
</script>
