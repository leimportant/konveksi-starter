<script setup lang="ts">
import { Button } from '@/components/ui/button';
// import { DateInput } from '@/components/ui/date-input';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { useModelStore } from '@/stores/useModelStore';
import { Head, router } from '@inertiajs/vue3';
import { Edit, Plus, Trash2 } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { onMounted, watch } from 'vue';

const toast = useToast();
const modelStore = useModelStore();
const { models, loading, currentPage, filters, lastPage } = storeToRefs(modelStore);

const breadcrumbs = [
    { title: 'Konveksi', href: '/konveksi' },
    { title: 'List Model', href: '/konveksi/model' },
];

onMounted(() => {
    modelStore.fetchModels(1, '-');
});

watch(
    filters,
    () => {
        modelStore.fetchModels(1, '-');
    },
    { deep: true },
);

const handleSearchInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    modelStore.setFilter('search', target.value);
};

const toggleStatus = async (id: number) => {
  await modelStore.updateCloseStatus(id)
}

// const handleStartDateChange = (date: string | null) => {
//     modelStore.setFilter('start_date', date);
// };

// const handleEndDateChange = (date: string | null) => {
//     modelStore.setFilter('end_date', date);
// };

const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to delete this model?')) return;

    try {
        await modelStore.deleteModel(id);
        toast.success('Model deleted successfully');
        await modelStore.fetchModels(1, '-');
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
        <div class="px-4 py-4">
            <div class="mb-6 flex items-center justify-between">
                <Button
                    @click="router.visit('/konveksi/model/create')"
                    aria-label="Tambah Model Baru"
                    class="rounded-md bg-indigo-600 py-2 text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500"
                >
                    <Plus class="h-4 w-4" />
                    Tambah
                </Button>

                <Input
                    :value="filters.search" 
                    :debounce="500"
                    placeholder="Search Model"
                    @input="handleSearchInput"
                    class="w-64"
                    aria-label="Search Model"
                    :disabled="loading"
                />
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-md border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <Table class="w-full text-sm md:table-auto">
                    <TableHeader>
                        <TableRow class="bg-gray-100 dark:bg-gray-800">
                            <TableHead class="px-3 py-2 text-left">Status</TableHead>
                            <TableHead class="px-3 py-2 text-left">Nama Model</TableHead>
                            <TableHead class="px-3 py-2">Est Harga/Pcs</TableHead>
                            <TableHead class="px-3 py-2">Qty</TableHead>
                            <TableHead class="hidden px-3 py-2 md:table-cell">Catatan</TableHead>
                            <TableHead class="px-3 py-2 text-right">Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
  <!-- ✅ Skeleton modern saat loading -->
  <template v-if="loading">
    <TableRow v-for="n in 5" :key="n" class="animate-pulse">
      <TableCell class="py-3">
        <div class="h-6 w-20 bg-gray-200 dark:bg-gray-700 rounded"></div>
      </TableCell>
      <TableCell class="py-3">
        <div class="h-4 w-40 bg-gray-200 dark:bg-gray-700 rounded mb-2"></div>
        <div class="h-3 w-24 bg-gray-200 dark:bg-gray-700 rounded"></div>
      </TableCell>
      <TableCell class="py-3">
        <div class="h-4 w-16 bg-gray-200 dark:bg-gray-700 rounded"></div>
      </TableCell>
      <TableCell class="py-3">
        <div class="h-4 w-12 bg-gray-200 dark:bg-gray-700 rounded"></div>
      </TableCell>
      <TableCell class="py-3 hidden md:table-cell">
        <div class="h-4 w-24 bg-gray-200 dark:bg-gray-700 rounded"></div>
      </TableCell>
      <TableCell class="py-3 text-right">
        <div class="h-6 w-20 bg-gray-200 dark:bg-gray-700 rounded ml-auto"></div>
      </TableCell>
    </TableRow>
  </template>

  <!-- ✅ Data Table -->
  <TableRow
    v-else
    v-for="item in models"
    :key="item.id"
    class="border-b border-gray-100 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800 transition-colors duration-150"
  >
    <!-- Status Toggle -->
    <TableCell class="text-center">
     <Button
  @click="toggleStatus(item.id)"
  :disabled="modelStore.loading"
  :class="[
    'flex items-center justify-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium shadow-sm transition-all duration-200 cursor-pointer',
    item.is_close === 'Y'
      ? 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900 dark:text-green-300 hover:scale-105 hover:shadow-md'
      : 'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900 dark:text-red-300 hover:scale-105 hover:shadow-md',
    modelStore.loading ? 'opacity-60 cursor-not-allowed' : ''
  ]"
>
  <Loader2
    v-if="modelStore.loading"
    class="h-4 w-4 animate-spin"
  />
  <span v-else>
    {{ item.is_close === 'Y' ? 'Close' : 'Open' }}
  </span>
</Button>


    </TableCell>

    <!-- Nama Model -->
    <TableCell class="px-3 py-2 font-medium text-foreground">
      <div class="font-semibold">{{ item.description }}</div>
      <div class="text-xs text-gray-500 dark:text-gray-400">
        Mulai: {{ formatDate(item.start_date) }}
      </div>
    </TableCell>

    <!-- Estimasi Harga -->
    <TableCell class="px-3 py-2">{{ formatPrice(item.estimation_price_pcs) }}</TableCell>

    <!-- Qty -->
    <TableCell class="px-3 py-2">
      <template v-if="item.sizes?.length">
        <div class="flex flex-col text-xs">
          <span v-for="(size, index) in item.sizes" :key="index">
            {{ size.size_id }} {{ size.variant }} ({{ size.qty }})
          </span>
        </div>
      </template>
      <span v-else>-</span>
    </TableCell>

    <!-- Catatan -->
    <TableCell class="hidden px-3 py-2 text-sm text-gray-500 dark:text-gray-400 md:table-cell">
      {{ item.remark || '-' }}
    </TableCell>

    <!-- Action -->
    <TableCell class="space-x-1 px-3 py-2 text-right">
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

  <!-- ✅ State kosong -->
  <TableRow v-if="!loading && models.length === 0">
    <TableCell colspan="6" class="py-6 text-center text-gray-500 dark:text-gray-400">
      Belum ada model yang ditemukan.
    </TableCell>
  </TableRow>
</TableBody>

                </Table>
                <div v-if="loading" class="p-4 text-center text-sm text-gray-500 dark:text-gray-400">Memuat data...</div>
            </div>
            <!-- Pagination -->
            <div class="mt-4 flex justify-end space-x-2">
                <button
                    @click="modelStore.prevPage"
                    :disabled="currentPage === 1 || loading"
                    class="rounded border border-gray-300 px-3 py-1 text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Previous
                </button>

                <template v-for="page in lastPage" :key="page">
                    <button
                        @click="modelStore.goToPage(page)"
                        :class="[
                            'rounded border px-3 py-1 text-sm',
                            page === currentPage ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-100',
                        ]"
                        :disabled="loading"
                    >
                        {{ page }}
                    </button>
                </template>

                <button
                    @click="modelStore.nextPage"
                    :disabled="currentPage === lastPage || loading"
                    class="rounded border border-gray-300 px-3 py-1 text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Next
                </button>
            </div>
        </div>
    </AppLayout>
</template>
