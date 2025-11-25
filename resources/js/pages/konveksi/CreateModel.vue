<template>
  <Head :title="isEditMode ? 'Edit Model' : 'Create Model'" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6">
      <!-- Header -->
      <div class="border-b pb-3 mb-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-white">
          {{ isEditMode ? 'Edit Model' : 'Buat Model Baru' }}
        </h1>
        <p class="text-xs text-gray-500 dark:text-gray-400">
          {{ isEditMode ? 'Ubah data model, ukuran, dan aktivitas' : 'Isi data model, ukuran, dan aktivitas' }}
        </p>
      </div>

      <!-- Tabs -->
      <div class="flex flex-wrap gap-2 mb-6 text-sm">
        <button
          v-for="tab in tabs"
          :key="tab"
          @click="activeTab = tab"
          class="px-3 py-1.5 rounded-full transition-all"
          :class="{
            'bg-indigo-600 text-white': activeTab === tab,
            'bg-gray-100 text-gray-600 hover:bg-gray-200': activeTab !== tab
          }"
        >
          {{ tab.charAt(0).toUpperCase() + tab.slice(1) }}
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="space-y-5 text-sm">
        <!-- Tab: Model -->
        <div v-if="activeTab === 'model'" class="space-y-4">
          <div>
            <label for="description" class="block mb-1 font-medium">Nama Model</label>
            <Input id="description" v-model="form.description" />
            <small class="text-red-500" v-if="errors.description">{{ errors.description[0] }}</small>
          </div>

          <div>
          <label for="category_id" class="block mb-1 font-medium">Kategori</label>
          <select v-model="form.category_id" class="w-full text-xs rounded-md border border-input px-3 py-2" required>
                  <option value="0">Select Category</option>
                  <option v-for="category in categories" :key="category.id" :value="category.id">
                    {{ category.name }}
                  </option>
                </select>
          </div>  

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block mb-1 font-medium" for="start_date">Tanggal Mulai</label>
              <DateInput id="start_date" v-model="form.start_date" />
              <small class="text-red-500" v-if="errors.start_date">{{ errors.start_date[0] }}</small>
            </div>
            <div class="hidden">
              <label class="block mb-1 font-medium" for="end_date">Estimasi Selesai</label>
              <DateInput id="end_date" v-model="form.end_date" />
              <small class="text-red-500" v-if="errors.end_date">{{ errors.end_date[0] }}</small>
            </div>
            <div class="sm:col-span-2 hidden">
              <label class="block mb-1 font-medium" for="estimation_price_pcs">Estimasi Harga</label>
              <Input id="estimation_price_pcs" type="number" v-model="form.estimation_price_pcs" />
              <small class="text-red-500" v-if="errors.estimation_price_pcs">{{ errors.estimation_price_pcs[0] }}</small>
            </div>
          </div>

          <div>
            <label class="block mb-1 font-medium" for="remark">Catatan</label>
            <Input id="remark" v-model="form.remark" />
            <small class="text-red-500" v-if="errors.remark">{{ errors.remark[0] }}</small>
          </div>

          <!-- Uploaded Documents -->
          <div v-if="uploadedDocuments.length" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div v-for="(doc, idx) in uploadedDocuments" :key="doc.id" class="relative group rounded-lg overflow-hidden border">
              <img :src="doc.url" class="aspect-square w-full object-cover transition-transform group-hover:scale-105" />
              <div class="absolute bottom-0 inset-x-0 bg-black/60 p-1 text-[11px] text-white truncate">
                {{ doc.filename }}
              </div>
              <Button
                variant="destructive"
                size="icon"
                class="absolute top-1 right-1 p-1 h-6 w-6"
                @click.prevent="removeDocument(idx)"
              >
                <i class="pi pi-times text-xs" />
              </Button>
            </div>
          </div>
        </div>

        <!-- Tab: Size -->
        <div v-if="activeTab === 'size'">
          <SizeTab v-model="sizeItems" @update:totalQuantity="totalProduction = $event" />
        </div>

        <!-- Tab: Activity -->
        <div v-if="activeTab === 'activity'">
          <ActivityTab v-model="activityItems" />
        </div>

        <!-- Tab: Document -->
        <div v-if="activeTab === 'gambar'">

          <DocumentList :reference-id="generatedDocId" :reference-type="referenceType" />
        </div>

        <!-- Tab: Material -->
        <div v-if="activeTab === 'bahan_dan_biaya'">
          <ModelMaterialTab v-model="modelMaterials" />
        </div>

        <!-- Tab: HPP -->
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
            :size-items="sizeItems"
          />
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-end border-t pt-4 gap-2 text-sm">
          <Button type="button" variant="secondary" @click="router.visit('/konveksi')">
            Batal
          </Button>
          <Button
            type="submit"
            :loading="form.processing"
            class="bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500"
          >
            <i class="pi pi-check mr-1" /> {{ isEditMode ? 'Perbarui' : 'Simpan' }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>


<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { DateInput } from '@/components/ui/date-input';
import { Button } from '@/components/ui/button';
import DocumentList from '@/components/DocumentList.vue';
import SizeTab from '@/components/SizeTab.vue';
import ActivityTab from '@/components/ActivityTab.vue';
import HPPTab from '@/components/HPPTab.vue';
import { useModelStore } from '@/stores/useModelStore';
import { useToast } from '@/composables/useToast';
import { type BreadcrumbItem } from '@/types';
import ModelMaterialTab from '@/components/ModelMaterialTab.vue';
import { useCategoryStore } from '@/stores/useCategoryStore';
import { Inertia } from '@inertiajs/inertia';
import { storeToRefs } from 'pinia';

// Props for edit mode
const props = defineProps<{
  modelData?: any;
}>();

const categoryStore = useCategoryStore();
// Tabs setup
const tabs = ['model', 'size', 'activity', 'gambar', 'bahan_dan_biaya', 'hpp'] as const;
type Tab = typeof tabs[number];
const activeTab = ref<Tab>('model');

const { items: categories } = storeToRefs(categoryStore);

// Determine mode
const isEditMode = computed(() => !!props.modelData);
const uniqId = ref<string>(Math.random().toString(36).substring(2, 15));
// Document handling
const uploadedDocuments = ref<{ id: string; url: string; filename: string }[]>([]);
const generatedDocId = computed(() => (isEditMode.value ? props.modelData.id : uniqId.value));

const referenceType = 'Model';

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
  category_id: null as number | null,
  remark: '',
  start_date: null,
  end_date: null,
  estimation_price_pcs: 0,
  estimation_qty: 1,
  is_close: 'N',
});
const errors = ref<Record<string, string[]>>({});

// Size items
const sizeItems = ref<{ size_id: string; qty: number, variant: string, price_store: number, price_grosir: number }[]>([]);

// Total production quantity from size items
const totalProduction = ref<number>(0);

// Activity items
const activityItems = ref<{ activity_role_id: number; price: number }[]>([]);

const modelMaterials = ref<{
  product_id: number;
  qty: number;
  uom_id: number;
  remark: string;
  price: number | null; // Add price property
}[]>([]);

// Initialize form for edit
onMounted(() => {

  Promise.all([categoryStore.fetchCategories()]);
  if (isEditMode.value) {
    form.description = props.modelData.description;
    form.remark = props.modelData.remark;
    form.start_date = props.modelData.start_date;
    form.end_date = props.modelData.end_date;
    form.estimation_price_pcs = props.modelData.estimation_price_pcs;
    form.estimation_qty = props.modelData.estimation_qty;
    form.is_close = props.modelData.is_close;
    sizeItems.value = props.modelData.sizes || [];
    activityItems.value = props.modelData.activity || [];
    modelMaterials.value = props.modelData.modelMaterials || [];
    uploadedDocuments.value = props.modelData.documents || [];
  }

   // Menangani back button / tab switch di PWA
  window.addEventListener('popstate', () => {
    Inertia.visit(window.location.href, { preserveState: true });
  });
});

watch(activeTab, (tab) => {
  if (tab === 'size') {
    // Trigger ulang update jika tab Size dibuka
    const totalQty = sizeItems.value.reduce((sum, item) => sum + (Number(item.qty) || 0), 0);
    totalProduction.value = totalQty;
  }
});


const cleanModelMaterials = (materials: any[]) => {
  return materials.map(m => ({
    ...m,
    product_id: typeof m.product_id === 'object' && m.product_id?.id
      ? m.product_id.id
      : m.product_id
  }));
};

const validateBeforeSubmit = () => {
  let valid = true;

  errors.value = {}; // reset errors

  if (!form.description) {
    errors.value.description = ['Deskripsi wajib diisi'];
    toast.error('Deskripsi wajib diisi');
    valid = false;
  }

  if (!form.category_id) {
    errors.value.category_id = ['Kategori wajib diisi'];
    toast.error('Kategori wajib diisi');
    valid = false;
  }

  if (sizeItems.value.length === 0) {
    errors.value.sizes = ['Data ukuran minimal 1'];
    toast.error('Data Size minimal 1');
    valid = false;
  }

  if (activityItems.value.length === 0) {
    errors.value.activity = ['Data aktivitas minimal 1'];
    toast.error('Data Activity minimal 1');
    valid = false;
  }

  if (modelMaterials.value.length === 0) {
    errors.value.modelMaterials = ['Data material minimal 1'];
    toast.error('Data Material minimal 1');
    valid = false;
  }

  return valid;
};


// Submit handler
const handleSubmit = async () => {
  if (!validateBeforeSubmit()) {
    return;
  }

  if (!form.start_date) {
    errors.value.start_date = ['Tanggal mulai harus diisi'];
    toast.error('Tanggal mulai harus diisi');
    return;
  }

  form.is_close = form.is_close ? 'Y' : 'N';


  try {
    if (isEditMode.value) {
      await modelStore.updateModel(props.modelData.id, {
        ...form,
        sizes: sizeItems.value || [],
        activity: activityItems.value || [],
        documents: uploadedDocuments.value || [],
        modelMaterials: cleanModelMaterials(modelMaterials.value) || [],
      });
      toast.success('Model berhasil diperbarui');
    } else {
      await modelStore.createModel({
        ...form,
        sizes: sizeItems.value || [],
        activity: activityItems.value || [],
        uniqId: uniqId.value, // Ensure unique ID is sent
        documents: uploadedDocuments.value || [],
        modelMaterials: cleanModelMaterials(modelMaterials.value) || [], // Changed from modelMaterial to modelMaterials
      });
      toast.success('Model berhasil dibuat');
    }
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

// Add this function before using it in the template
const getActivityName = (activityRoleId: number) => {
  const activity = activityItems.value.find(item => item.activity_role_id === activityRoleId);
  return activity ? `Activity ${activityRoleId}` : `Unknown Activity`;
};
</script>
