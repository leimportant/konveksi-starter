<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { DateInput } from '@/components/ui/date-input';
import { Input } from '@/components/ui/input';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { useModelStore } from '@/stores/useModelStore';
import { useToast } from "@/composables/useToast";

const props = defineProps<{
  modelId: number;
}>();

const toast = useToast();
const modelStore = useModelStore();

const form = useForm({
  description: '',
  remark: '',
  start_date: null,
  estimation_price_pcs: 0,
  estimation_qty: 1,
});

const errors = ref<Record<string, string[]>>({});

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Konveksi', href: '/konveksi' },
  { title: 'List Model', href: '/konveksi/model/list' },
  { title: 'Edit Model', href: `/konveksi/model/${props.modelId}/edit` }
];

onMounted(async () => {
  try {
    const response = await modelStore.fetchModelById(props.modelId);
    if (!response || !response.data) {
      toast.error("Data model tidak ditemukan");
    //   router.visit('/konveksi/model/list');
      return;
    }

    const model = response.data;
    form.description = model.description || '';
    form.remark = model.remark || '';
    form.start_date = model.start_date || null;
    form.estimation_price_pcs = model.estimation_price_pcs || 0;
    form.estimation_qty = model.estimation_qty || 1;
  } catch (error: any) {
    console.error('Error fetching model:', error);
    toast.error(error.response?.data?.message || "Gagal mengambil data model");
    router.visit('/konveksi/model/list');
  }
});

const handleSubmit = async () => {
  try {
    if (!form.start_date) {
      errors.value = {
        ...errors.value,
        start_date: ['Tanggal mulai harus diisi']
      };
      toast.error("Tanggal mulai harus diisi");
      return;
    }

    await modelStore.updateModel(props.modelId, {
      description: form.description,
      remark: form.remark,
      estimation_price_pcs: form.estimation_price_pcs,
      estimation_qty: form.estimation_qty,
      start_date: form.start_date
    });
    
    toast.success("Model berhasil diperbarui");
    router.visit('/konveksi/model/list');
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
      toast.error("Validasi gagal, silakan periksa kembali form anda");
    } else {
      toast.error("Terjadi kesalahan saat memperbarui model");
    }
  }
};
</script>

<template>
  <Head title="Edit Model" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-6 rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
      <div class="border-b pb-4 dark:border-gray-700">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Edit Model</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Edit model yang sudah ada dengan mengubah detail di bawah ini.
        </p>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Description -->
        <div class="field">
          <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Deskripsi</label>
          <Input 
            v-model="form.description" 
            id="description" 
            placeholder="Masukkan deskripsi model..." 
            :class="{ 'border-destructive': errors.description }"
          />
          <small class="text-destructive" v-if="errors.description">{{ errors.description[0] }}</small>
        </div>

        <!-- Two columns layout -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <!-- Start Date -->
          <div class="field">
            <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Tanggal Mulai</label>
            <DateInput
              v-model="form.start_date"
              id="start_date"
              :class="{ 'border-destructive': errors.start_date }"
            />
            <small class="text-destructive" v-if="errors.start_date">{{ errors.start_date[0] }}</small>
          </div>

          <!-- Estimation Price -->
          <div class="field">
            <label for="estimation_price_pcs" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Estimasi Harga</label>
            <Input
              type="number"
              v-model="form.estimation_price_pcs" 
              id="estimation_price_pcs" 
              :class="{ 'border-destructive': errors.estimation_price_pcs }"
              class="w-full" 
            />
            <small class="text-destructive" v-if="errors.estimation_price_pcs">{{ errors.estimation_price_pcs[0] }}</small>
          </div>
        </div>

        <!-- Remark -->
        <div class="field">
          <label for="remark" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Catatan</label>
          <Input 
            v-model="form.remark" 
            id="remark" 
            placeholder="Tambahkan catatan tambahan..." 
            :class="{ 'border-destructive': errors.remark }"
          />
          <small class="text-destructive" v-if="errors.remark">{{ errors.remark[0] }}</small>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end gap-2">
          <Button 
            type="button" 
            variant="outline" 
            @click="router.visit('/konveksi/model/list')"
          >
            Batal
          </Button>
          <Button type="submit">Simpan Perubahan</Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>