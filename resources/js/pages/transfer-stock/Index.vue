<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { useTransferStockStore } from '@/stores/useTransferStockStore';
import { Head, usePage } from '@inertiajs/vue3';
import { Edit, Eye, Search, Trash2 } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import debounce from 'lodash-es/debounce';
import { onMounted, computed, watch, ref, reactive } from 'vue';
import type { TransferStock, TransferDetail } from '@/stores/useTransferStockStore';
import Vue3Select from 'vue3-select';
import 'vue3-select/dist/vue3-select.css';

const toast = useToast();
const store = useTransferStockStore();
const { transfers, currentPage, lastPage } = storeToRefs(store);

const perPage = ref(50);
const filterStatus = ref({ label: 'Pending', value: 'Pending' });

const debouncedFetchTransfers = debounce(() => {
    store.fetchTransfers(1, perPage.value);
}, 500);

const filters = reactive({
    search: '',
    status: '', // Add status property here
});

const handleSearch = () => {

    const statusVal =
        typeof filterStatus.value === 'string'
            ? filterStatus.value
            : filterStatus.value?.value ?? '';

    store.filters.productName = filters.search;
    store.filters.status = statusVal;
    store.fetchTransfers(1, perPage.value);
};

onMounted(() => {
    store.fetchTransfers(currentPage.value, perPage.value);
});

watch(
    () => filters.search,
    debouncedFetchTransfers,
);

watch(currentPage, async (newPage) => {
    await store.fetchTransfers(newPage, perPage.value);
});

const totalPages = computed(() => lastPage.value || 1);

const groupedDetailsByProduct = computed(() => (details: TransferDetail[]) => {
  const groups = new Map<string, TransferDetail[]>();
  details.forEach(detail => {
    const productName = detail.product?.name || 'Unknown Product';
    if (!groups.has(productName)) {
      groups.set(productName, []);
    }
    groups.get(productName)?.push(detail);
  });
  return Array.from(groups.entries());
});

const groupedTransfers = computed(() => {
    const groups = new Map<string, TransferStock[]>();
    transfers.value.forEach(transfer => {
        const date = transfer.created_at ? new Date(transfer.created_at).toLocaleDateString('id-ID') : '-';
        if (!groups.has(date)) {
            groups.set(date, []);
        }
        groups.get(date)?.push(transfer);
    });
    return Array.from(groups.entries()).map(([key, items]) => ({ key, items }));
});
const goToPage = async (page: number) => {
    if (page < 1 || page > totalPages.value) return;
    currentPage.value = page;
};

const nextPage = () => goToPage(currentPage.value + 1);
const prevPage = () => goToPage(currentPage.value - 1);

const handleDelete = async (id: string) => {
    if (!confirm('Are you sure you want to delete this transfer?')) return;
    try {
        await store.deleteTransfer(id);
        await store.fetchTransfers();
        toast.success('Transfer deleted successfully');
    } catch (error: any) {
        toast.error(error?.response?.data?.message ?? 'Failed to delete transfer');
    }
};
interface User {
    id: number;
    type: string | 'admin';
    name: string;
    avatar: string;
    location_id: number;
    employee_status: string | 'admin';
}

const page = usePage();
const user = page.props.auth.user as User;
const locationId = user.location_id;

const breadcrumbs = [{ title: 'Transfer Stocks', href: '/transfer-stocks' }];




</script>

<template>

    <Head title="Transfer Stocks" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-4">
        <!-- Header -->
        <div class="mb-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <!-- Filter kiri -->
            <div class="flex items-center gap-3 w-full sm:w-auto">

                    <!-- Dropdown Status -->
                    <Vue3Select v-model="filterStatus" :options="[
                        { label: 'Semua', value: '' },
                        { label: 'Pending', value: 'Pending' },
                        { label: 'Approve', value: 'Approved' },
                        { label: 'Reject', value: 'Rejected' }
                    ]" placeholder="Pilih Status" class="w-full text-sm sm:w-[150px]" />

                    <!-- Input Pencarian -->
                    <input v-model="filters.search" type="text" placeholder="Cari data..." @keyup.enter="handleSearch"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:w-48" />

                    <!-- Tombol Cari -->
                    <Button @click="handleSearch"
                        class="flex items-center gap-1 bg-indigo-600 text-white hover:bg-indigo-700">
                        <Search class="h-4 w-4" /> Cari
                    </Button>

                </div>


                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-gray-100">
                                <TableHead class="py-2 text-xs font-semibold text-gray-700">Lokasi Tujuan</TableHead>
                                <TableHead class="py-2 text-xs font-semibold text-gray-700">Detail</TableHead>
                                <TableHead class="py-2 text-xs font-semibold text-gray-700">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <template v-for="(group) in groupedTransfers" :key="group.key">
                                <template v-for="transfer in group.items" :key="transfer.id">
                                    <TableRow class="border-b hover:bg-gray-50">
                                        <TableCell class="whitespace-nowrap py-2 text-sm align-top">
                                            {{ transfer.location_destination?.name || '-' }}
                                            <div class="text-xs text-gray-500">
                                                <span>{{ group.key }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell class="py-2 text-sm  align-top">
                                            <div v-if="transfer.transfer_detail?.length" class="mt-1 space-y-1">
                                                <div v-for="([productName, details]) in groupedDetailsByProduct(transfer.transfer_detail)" :key="productName"
                                                    class="flex flex-col gap-0.5 rounded-lg border border-gray-200 bg-gray-50 p-1 text-[10px] text-gray-700">
                                                    <div class="mb-1 font-semibold text-gray-800">{{ productName }}</div>
                                                    <div v-for="(detail, detailIndex) in details"
                                                        :key="detailIndex"
                                                        class="flex items-center justify-between rounded-md bg-white px-1.5 py-0.5 shadow-sm">
                                                        <span class="ml-2 font-semibold flex-shrink-0">{{ detail.variant }} {{ detail.qty }}
                                                            {{ detail.uom_id }}</span>
                                                    </div>
                                                </div>
                                                <div v-if="transfer.transfer_detail.length > 1"
                                                        class="order-t mt-0.5 flex items-center justify-between rounded-md border-gray-300 bg-white px-1.5 pt-0.5 font-semibold text-[10px] text-gray-800 shadow-sm">
                                                        <span>Total</span>
                                                        <span class="ml-2 flex-shrink-0">
                                                            {{
                                                                transfer.transfer_detail.reduce(
                                                                    (sum: number, s: any) => sum + Number(s.qty || 0),
                                                                    0,
                                                                )
                                                            }}
                                                        </span>
                                                    </div>
                                            </div>
                                        </TableCell>
                                        <TableCell class="w-1/12 py-2 text-right">
                                            <div class="flex justify-end space-x-1">
                                                <Button v-if="
                                                    transfer?.id &&
                                                    (transfer.status === 'Accepted' || transfer.status === 'Rejected') ||
                                                    transfer.location_destination?.id === locationId
                                                " variant="ghost" size="icon" class="h-7 w-7 hover:bg-gray-100"
                                                    @click="$inertia.visit(`/transfer-stock/${transfer.id}/view`)">
                                                    <Eye class="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    v-if="transfer?.id && (transfer.status === 'Pending' || transfer.status === 'Rejected')"
                                                    variant="ghost" size="icon" class="h-7 hidden w-7 hover:bg-gray-100"
                                                    @click="$inertia.visit(`/transfer-stock/${transfer.id}/edit`)">
                                                    <Edit class="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    v-if="transfer?.id && (transfer.status === 'Pending' || transfer.status === 'Rejected')"
                                                    variant="ghost" size="icon" class="h-7 hidden w-7 hover:bg-gray-100"
                                                    @click="handleDelete(transfer.id)">
                                                    <Trash2 class="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </template>
                            </template>
                        </TableBody>
                    </Table>
                </div>
                <!-- Pagination -->
                <div class="mt-4 flex flex-wrap justify-end gap-1">
                    <button @click="prevPage" :disabled="currentPage === 1"
                        class="rounded border border-gray-300 px-2 py-1 text-xs text-gray-700 hover:bg-gray-100 disabled:opacity-50">
                        Previous
                    </button>
                    <template v-for="page in totalPages" :key="page">
                        <button @click="goToPage(page)" :class="[
                            'rounded border px-2 py-1 text-xs',
                            page === currentPage ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-100',
                        ]">
                            {{ page }}
                        </button>
                    </template>
                    <button @click="nextPage" :disabled="currentPage === totalPages"
                        class="rounded border border-gray-300 px-2 py-1 text-xs text-gray-700 hover:bg-gray-100 disabled:opacity-50">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
