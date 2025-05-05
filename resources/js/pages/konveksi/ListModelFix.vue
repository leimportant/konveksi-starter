<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Edit, Trash2, Plus } from 'lucide-vue-next';
import { useToast } from "@/composables/useToast";
import { useModelStore } from '@/stores/useModelStore';
import { storeToRefs } from 'pinia';
import { DateInput } from '@/components/ui/date-input';
import { router } from '@inertiajs/vue3';

const toast = useToast();
const modelStore = useModelStore();
const { models } = storeToRefs(modelStore);

const breadcrumbs = [
  { title: 'Konveksi', href: '/konveksi' },
  { title: 'List Model', href: '/konveksi/model' }
];

// Search filters
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

// Add format functions
const formatPrice = (price: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price);
};

const formatDate = (date: string | null | undefined) => {
  if (!date) return '-'; // fallback for null or empty
  return new Date(date).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  });
};

</script>

<template>
  <Head title="List Model" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="flex justify-between items-center mb-6">
        <div class="flex gap-4 items-center">
          <Input 
            v-model="searchQuery"
            placeholder="Search models..."
            class="w-64"
            @input="fetchModels"
          />
          <DateInput
            v-model="startDate"
            placeholder="Start Date"
            @update:modelValue="fetchModels"
          />
          <DateInput
            v-model="endDate"
            placeholder="End Date"
            @update:modelValue="fetchModels"
          />
        </div>
        <Button @click="router.visit('/konveksi/model/create')">
          <Plus class="h-4 w-4 mr-2" />
          Buat Model
        </Button>
      </div>

      <div class="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Description</TableHead>
              <TableHead>Start Date</TableHead>
              <TableHead>Price/Pcs</TableHead>
              <TableHead>Qty</TableHead>
              <TableHead>Remark</TableHead>
              <TableHead class="w-24">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="model in models" :key="model.id">
              <TableCell>{{ model.description }}</TableCell>
              <TableCell>{{ formatDate(model.start_date) }}</TableCell>
              <TableCell>{{ formatPrice(model.estimation_price_pcs) }}</TableCell>
              <TableCell>{{ model.estimation_qty }}</TableCell>
              <TableCell>{{ model.remark }}</TableCell>
              <TableCell class="flex gap-2">
                <Button 
                  variant="ghost" 
                  size="icon" 
                  @click="router.visit(`/konveksi/model/${model.id}/edit`)"
                >
                  <Edit class="h-4 w-4" />
                </Button>
                <Button 
                  variant="ghost" 
                  size="icon" 
                  @click="handleDelete(model.id)"
                >
                  <Trash2 class="h-4 w-4" />
                </Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>
  </AppLayout>
</template>