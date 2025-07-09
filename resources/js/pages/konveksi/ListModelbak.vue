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
    modelStore.fetchModels();
});

watch(
    filters,
    () => {
        modelStore.fetchModels();
    },
    { deep: true },
);

const handleSearchInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    modelStore.setFilter('search', target.value);
};

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
        await modelStore.fetchModels();
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
                    Add
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

            <!-- Filter & Action -->
            <!-- <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"> -->
                <!-- Tombol Tambah + Search di 1 baris -->
                <!-- <div class="flex w-full flex-col justify-between gap-2 sm:w-auto sm:flex-row sm:items-center">
                    <Button
                        @click="router.visit('/konveksi/model/create')"
                        class="flex items-center gap-2 rounded-md bg-indigo-600 px-3 py-2 text-sm text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500"
                    >
                        <Plus class="h-4 w-4" /> Tambah
                    </Button>

                    <Input :value="filters.search" placeholder="Cari data" class="w-full sm:w-64" @input="handleSearchInput" />
                </div> -->

                <!-- Date Range di baris berikutnya -->
                <!-- <div class="flex w-full items-center gap-2 sm:w-auto">
                    <DateInput
                        :model-value="filters.start_date"
                        @update:model-value="handleStartDateChange"
                        placeholder="Start"
                        class="w-full sm:w-[8rem]"
                    />
                    <span class="text-gray-500">–</span>
                    <DateInput
                        :model-value="filters.end_date"
                        @update:model-value="handleEndDateChange"
                        placeholder="End"
                        class="w-full sm:w-[8rem]"
                    />
                </div> -->
            <!-- </div> -->
            <!-- Table -->
            <div class="overflow-x-auto rounded-md border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <Table class="w-full min-w-[600px] text-sm">
                    <TableHeader>
                        <TableRow class="bg-gray-100 dark:bg-gray-800">
                            <TableHead class="px-3 py-2 text-left">Nama Model</TableHead>
                            <TableHead class="px-3 py-2">Est Harga/Pcs</TableHead>
                            <TableHead class="px-3 py-2">Qty</TableHead>
                            <TableHead class="hidden px-3 py-2 md:table-cell">Catatan</TableHead>
                            <TableHead class="px-3 py-2 text-right">Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="item in models"
                            :key="item.id"
                            class="border-b border-gray-100 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800"
                        >
                            <TableCell class="px-3 py-2 font-medium text-foreground">
                                <div class="font-semibold">{{ item.description }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Mulai: {{ formatDate(item.start_date) }}</div>
                            </TableCell>
                            <TableCell class="px-3 py-2">{{ formatPrice(item.estimation_price_pcs) }}</TableCell>
                            <TableCell class="px-3 py-2">
                                <template v-if="item.sizes?.length">
                                    <div class="flex flex-col text-xs">
                                        <span v-for="(size, index) in item.sizes" :key="index">
                                            {{ size.size_id }} {{ size.variant }} ({{ size.qty }})
                                            <template v-if="index !== item.sizes.length - 1"> ,<br /> </template>
                                        </span>
                                    </div>
                                </template>
                                <span v-else>-</span>
                            </TableCell>

                            <TableCell class="hidden px-3 py-2 text-sm text-gray-500 dark:text-gray-400 md:table-cell">
                                {{ item.remark || '-' }}
                            </TableCell>
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

                        <TableRow v-if="!modelStore.loading && models.length === 0">
                            <TableCell colspan="5" class="px-3 py-6 text-center text-sm text-muted-foreground">
                                Tidak ada model yang ditemukan.
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
