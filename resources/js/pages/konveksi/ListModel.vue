<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Edit, Trash2, Plus } from 'lucide-vue-next';
import { useToast } from "@/composables/useToast";
import { useModelStore } from '@/stores/useModelStore';
import { storeToRefs } from 'pinia';
import { DateInput } from '@/components/ui/date-input';
import { ListView } from '@/components/ui/list-view';

const toast = useToast();
const modelStore = useModelStore();
const { models } = storeToRefs(modelStore);

const breadcrumbs = [
  { title: 'Konveksi', href: '/konveksi' },
  { title: 'List Model', href: '/konveksi/model' }
];

const searchQuery = ref('');
const startDate = ref<string | null>(null);
const endDate = ref<string | null>(null);

onMounted(() => {
  fetchModels();
});

const fetchModels = async () => {
  try {
    await modelStore.fetchModels({
      search: searchQuery.value,
      start_date: startDate.value,
      end_date: endDate.value
    });
  } catch (error: any) {
    if (error.response?.data?.errors) {
      console.error(error.response.data.errors);
      toast.error("Gagal ambil data");
    } else {
      toast.error("Terjadi kesalahan saat membuat model");
    }
  }
};

const handleDelete = async (id: number) => {
  if (!confirm('Are you sure you want to delete this model?')) return;

  try {
    await modelStore.deleteModel(id);
    toast.success("Model deleted successfully");
    await fetchModels();
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to delete model");
  }
};

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price);
};

const formatDate = (date: string | null | undefined) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  });
};

const handleSelect = (model: any) => {
  router.visit(`/konveksi/model/${model.id}/edit`);
};
</script>

<template>
  <Head title="List Model" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6 sm:px-6 lg:px-8">
      <!-- Filter and Create Button -->
      <div class="flex flex-col gap-4 mb-6 lg:flex-row lg:items-center lg:justify-between bg-background">
        <!-- Filters -->
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4 w-full">
          <Input 
            v-model="searchQuery"
            placeholder="Search models..."
            class="w-full sm:w-64 bg-background border-border"
            @input="fetchModels"
          />
          <DateInput
            v-model="startDate"
            placeholder="Start Date"
            class="bg-background border-border"
            @update:modelValue="fetchModels"
          />
          <DateInput
            v-model="endDate"
            placeholder="End Date"
            class="bg-background border-border"
            @update:modelValue="fetchModels"
          />
        </div>

        <!-- Create Button -->
        <Button 
          class="w-full sm:w-auto bg-primary text-primary-foreground hover:bg-primary/90" 
          @click="router.visit('/konveksi/model/create')"
        >
          <Plus class="h-4 w-4 mr-2" />
          Buat Model
        </Button>
      </div>

      <!-- List View -->
      <ListView
        :items="models"
        :is-loading="modelStore.loading"
        empty-message="Tidak ada model yang ditemukan"
        @select="handleSelect"
      >
        <!-- Item Slot -->
        <template #item="{ item }">
          <div class="flex flex-col space-y-2 sm:space-y-1">
            <h3 class="font-medium text-base text-muted-foreground/100 truncate text-foreground">{{ item.description }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-sm text-muted-foreground">
              <span><span class="font-medium sm:hidden">Tanggal Mulai:</span> {{ formatDate(item.start_date) }}</span>
              <span><span class="font-medium sm:hidden">Harga/Pcs:</span> {{ formatPrice(item.estimation_price_pcs) }}</span>
              <span><span class="font-medium sm:hidden">Qty:</span> {{ item.estimation_qty }}</span>
            </div>
            <p class="text-sm text-muted-foreground/80 line-clamp-2">{{ item.remark || '-' }}</p>
          </div>
        </template>

        <!-- Actions Slot -->
        <template #actions="{ item }">
          <div class="flex gap-2 justify-end sm:justify-start">
            <Button 
              variant="ghost" 
              size="icon"
              @click.stop="router.visit(`/konveksi/model/${item.id}/edit`)"
            >
              <Edit class="h-4 w-4" />
            </Button>
            <Button 
              variant="ghost" 
              size="icon"
              @click.stop="handleDelete(item.id)"
            >
              <Trash2 class="h-4 w-4" />
            </Button>
          </div>
        </template>
      </ListView>
    </div>
  </AppLayout>
</template>
