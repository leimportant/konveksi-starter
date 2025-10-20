<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { useModelStore } from '@/stores/useModelStore';
import { Head, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { Edit, Plus, Trash2 } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { onMounted, onUnmounted, ref, watch } from 'vue';

const toast = useToast();
const modelStore = useModelStore();
const { models, loading, currentPage, filters, lastPage } = storeToRefs(modelStore);

const breadcrumbs = [
    { title: 'Konveksi', href: '/konveksi' },
    { title: 'List Model', href: '/konveksi/model' },
];

const searchQuery = ref(filters.value.search || '');

const fetchData = async (options: { page?: number; is_close?: string } = {}) => {
    const { page = 1, is_close = '-' } = options;
    try {
        await modelStore.fetchModels({
            search: searchQuery.value,
            is_close,
            page,
        });
    } catch (error: any) {
        toast.error(error?.response?.data?.message ?? 'Failed to fetch models');
    }
};

const debouncedFetchData = debounce(() => fetchData({ page: 1 }), 300);
watch(searchQuery, debouncedFetchData);

onMounted(() => {
    fetchData();
});

// ✅ Responsive screen detection
const isMobile = ref(false);
const checkScreen = () => (isMobile.value = window.innerWidth < 768);
onMounted(() => {
    checkScreen();
    window.addEventListener('resize', checkScreen);
});
onUnmounted(() => window.removeEventListener('resize', checkScreen));

const toggleStatus = async (id: number) => {
    await modelStore.updateCloseStatus(id);
};

const handleDelete = async (id: number) => {
    if (!confirm('Yakin hapus model ini?')) return;
    try {
        await modelStore.deleteModel(id);
        toast.success('Model berhasil dihapus');
        fetchData({ page: currentPage.value });
    } catch (error: any) {
        toast.error(error?.response?.data?.message ?? 'Gagal menghapus model');
    }
};

const formatPrice = (price: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(price);

const formatDate = (date: string | null | undefined) => {
  if (!date) return '-';
  const d = new Date(date);
  if (isNaN(d.getTime())) return '-';
  return d.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  });
};

</script>

<template>
    <Head title="List Model" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-4">
            <!-- Header -->
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <!-- Tombol Tambah -->
                <Button
                    @click="router.visit('/konveksi/model/create')"
                    class="flex items-center gap-2 rounded-md bg-indigo-600 px-3 py-2 text-sm text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500"
                >
                    <Plus class="h-4 w-4" /> Tambah
                </Button>

                <!-- Input Search -->
                <div class="min-w-[180px] flex-1 sm:min-w-[240px] md:min-w-[280px]">
                    <Input v-model="searchQuery" placeholder="Cari model..." class="w-full text-sm" />
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-md bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <Table class="w-full text-xs md:text-sm">
                    <TableHeader>
                        <TableRow class="bg-gray-100 dark:bg-gray-800">
                            <TableHead class="px-2 py-2 text-left md:px-3">Status</TableHead>
                            <TableHead class="px-2 py-2 text-left md:px-3">Model</TableHead>
                            <TableHead class="hidden px-2 py-2 md:table-cell md:px-3">Catatan</TableHead>
                            <TableHead class="px-2 py-2 text-right md:px-3">Action</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <!-- Skeleton Loading -->
                        <template v-if="loading">
                            <TableRow v-for="n in 5" :key="n" class="animate-pulse">
                                <TableCell v-for="i in 5" :key="i" class="py-2">
                                    <div class="h-4 rounded bg-gray-200 dark:bg-gray-700"></div>
                                </TableCell>
                            </TableRow>
                        </template>

                        <!-- Data -->
                        <TableRow
                            v-else
                            v-for="item in models"
                            :key="item.id"
                            class="border-b border-gray-100 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800"
                        >
                            <!-- Status -->
                            <TableCell class="px-2 py-2 text-center align-top md:px-3">
                                <Button
                                    @click="toggleStatus(item.id)"
                                    :disabled="modelStore.loading"
                                    :class="[
                                        'rounded px-2 py-1 text-xs transition-all md:text-sm',
                                        item.is_close === 'Y'
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                                            : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
                                    ]"
                                >
                                    {{ item.is_close === 'Y' ? 'Close' : 'Open' }}
                                </Button>
                            </TableCell>

                            <!-- Nama Model -->
                            <TableCell class="px-2 py-2 align-top font-medium">
                                <div class="font-semibold">{{ item.description }}</div>
                                <div class="text-[10px] text-gray-500">
                                    {{ isMobile ? formatDate(item.start_date) : `Mulai: ${formatDate(item.start_date)}` }}

                                </div>
                                <div v-if="!isMobile" class="text-[10px] text-gray-500">{{ isMobile ? item.start_date : `Estimasi Harga: ${formatPrice(item.estimation_price_pcs)}` }}</div>

                                <div
                                    v-if="item.sizes?.length"
                                    class="flex flex-col gap-1 rounded-lg border border-gray-200 bg-gray-50 p-1 text-[10px] text-gray-700"
                                >
                                    <div
                                        v-for="(size, index) in item.sizes"
                                        :key="index"
                                        class="flex items-center justify-between rounded-md bg-white px-1.5 py-0.5 shadow-sm"
                                    >
                                        <span>{{ size.size_id }} - {{ size.variant }}</span> <span class="font-semibold">{{ size.qty }}</span>
                                    </div>
                                </div>
                                <span v-else class="text-xs italic text-gray-400">-</span>
                            </TableCell>

                           
                            <!-- Catatan -->
                            <TableCell class="hidden px-2 py-2 text-gray-500 md:table-cell md:px-3">
                                {{ item.remark || '-' }}
                            </TableCell>

                            <!-- Action -->
                            <TableCell class="px-2 py-2 text-right md:px-3">
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

                        <!-- Empty State -->
                        <TableRow v-if="!loading && models.length === 0">
                            <TableCell colspan="6" class="py-6 text-center text-gray-500 dark:text-gray-400"> Belum ada model ditemukan. </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 flex flex-wrap justify-center gap-2 sm:justify-end">
                <button
                    @click="modelStore.prevPage"
                    :disabled="currentPage === 1 || loading"
                    class="rounded border border-gray-300 px-3 py-1 text-sm text-gray-700 hover:bg-gray-100 disabled:opacity-50"
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
                    >
                        {{ page }}
                    </button>
                </template>
                <button
                    @click="modelStore.nextPage"
                    :disabled="currentPage === lastPage || loading"
                    class="rounded border border-gray-300 px-3 py-1 text-sm text-gray-700 hover:bg-gray-100 disabled:opacity-50"
                >
                    Next
                </button>
            </div>
        </div>
    </AppLayout>
</template>
