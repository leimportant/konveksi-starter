<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DateInput } from '@/components/ui/date-input';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { useModelStore } from '@/stores/useModelStore';
import { Head, router } from '@inertiajs/vue3';
import { Edit, Plus, Trash2 } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { onMounted, ref, watch } from 'vue';

const toast = useToast();
const modelStore = useModelStore();
const { models } = storeToRefs(modelStore);

const breadcrumbs = [
    { title: 'Konveksi', href: '/konveksi' },
    { title: 'List Model', href: '/konveksi/model' },
];

const searchQuery = ref('');
const startDate = ref<string | null>(null);
const endDate = ref<string | null>(null);

const fetchModels = async () => {
    try {
        await modelStore.fetchModels({
            search: searchQuery.value,
            start_date: startDate.value,
            end_date: endDate.value,
        });
    } catch (error: any) {
        if (error.response?.data?.errors) {
            console.error(error.response.data.errors);
            toast.error('Gagal ambil data');
        } else {
            toast.error('Terjadi kesalahan saat membuat model');
        }
    }
};

onMounted(() => {
    fetchModels();
});

watch(searchQuery, () => {
    fetchModels();
});

const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to delete this model?')) return;

    try {
        await modelStore.deleteModel(id);
        toast.success('Model deleted successfully');
        await fetchModels();
    } catch (error: any) {
        toast.error(error?.response?.data?.message ?? 'Failed to delete model');
    }
};

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
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

// const handleSelect = (model: any) => {
//   router.visit(`/konveksi/model/${model.id}/edit`);
// };
</script>

<template>
  <Head title="List Model" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6 sm:px-6 lg:px-8 space-y-6">
      <!-- Filter & Action -->
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
  <!-- Tombol Tambah + Search di 1 baris -->
  <div class="flex flex-col sm:flex-row sm:items-center gap-2 w-full sm:w-auto justify-between">
    <Button
      @click="router.visit('/konveksi/model/create')"
      class="flex items-center gap-2 rounded-md bg-indigo-600 px-3 py-2 text-sm text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500"
    >
      <Plus class="h-4 w-4" /> Tambah
    </Button>

    <Input
      v-model="searchQuery"
      placeholder="Cari data"
      class="w-full sm:w-64"
    />
  </div>

  <!-- Date Range di baris berikutnya -->
  <div class="flex items-center gap-2 w-full sm:w-auto">
    <DateInput
      v-model="startDate"
      placeholder="Start"
      class="w-full sm:w-[8rem]"
    />
    <span class="text-gray-500">–</span>
    <DateInput
      v-model="endDate"
      placeholder="End"
      class="w-full sm:w-[8rem]"
    />
  </div>
</div>
      <!-- Table -->
      <div class="overflow-x-auto rounded-md border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <Table class="min-w-[600px] w-full text-sm">
          <TableHeader>
            <TableRow class="bg-gray-100 dark:bg-gray-800">
              <TableHead class="px-3 py-2 w-[300px]">Nama Model</TableHead>
              <TableHead class="px-3 py-2">Est Harga/Pcs</TableHead>
              <TableHead class="px-3 py-2">Est Qty</TableHead>
              <TableHead class="px-3 py-2 hidden md:table-cell">Catatan</TableHead>
              <TableHead class="px-3 py-2 text-right">Action</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow
              v-for="item in models"
              :key="item.id"
              class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800"
            >
              <TableCell class="px-3 py-2 font-medium text-foreground">
                <div class="font-semibold">{{ item.description }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Mulai: {{ formatDate(item.start_date) }}</div>
              </TableCell>
              <TableCell class="px-3 py-2">{{ formatPrice(item.estimation_price_pcs) }}</TableCell>
              <TableCell class="px-3 py-2">
                <template v-if="item.sizes?.length">
                  <div class="flex flex-wrap gap-1 text-xs">
                    <span v-for="(size, index) in item.sizes" :key="index" class="inline-block">
                      {{ size.size_id }} ({{ size.qty }})<span v-if="index !== item.sizes.length - 1">,</span>
                    </span>
                  </div>
                </template>
                <span v-else>-</span>
              </TableCell>
              <TableCell class="px-3 py-2 hidden md:table-cell text-sm text-gray-500 dark:text-gray-400">
                {{ item.remark || '-' }}
              </TableCell>
              <TableCell class="px-3 py-2 text-right space-x-1">
                <Button
                  size="icon"
                  variant="ghost"
                  class="hover:bg-gray-100 dark:hover:bg-gray-700"
                  @click.stop="router.visit(`/konveksi/model/${item.id}/edit`)"
                >
                  <Edit class="h-4 w-4" />
                </Button>
                <Button
                  size="icon"
                  variant="ghost"
                  class="hover:bg-gray-100 dark:hover:bg-gray-700"
                  @click.stop="handleDelete(item.id)"
                >
                  <Trash2 class="h-4 w-4 text-red-500" />
                </Button>
              </TableCell>
            </TableRow>

            <TableRow v-if="!modelStore.loading && models.length === 0">
              <TableCell colspan="5" class="px-3 py-6 text-center text-sm text-muted-foreground">
                Tidak ada model yang ditemukan.
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
        <div
          v-if="modelStore.loading"
          class="p-4 text-center text-sm text-gray-500 dark:text-gray-400"
        >
          Memuat data...
        </div>
      </div>
    </div>
  </AppLayout>
</template>

