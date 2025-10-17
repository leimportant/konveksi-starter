<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { useReportStore } from '@/stores/useReportStore';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

import type { User } from '@/types';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';

const reportStore = useReportStore();

const startDate = ref('');
const endDate = ref('');
const searchKey = ref('');
const page = usePage<SharedData>();
const userLogin = page.props.auth.user as User;
const userId = (userLogin as any)?.id;

const currentPage = computed(() => reportStore.currentPage);
const perPage = computed(() => reportStore.perPage);

const fetchReport = async (page: number = 1, perPage: number = 10) => {
    // The first argument should be customerId (number), but searchKey is string, so we need to handle this properly
    // For now, pass 0 or parseInt if searchKey is a number string
    const customerId = userId || 0;
    await reportStore.fetchOmsetPerCustomer(customerId, searchKey.value, startDate.value, endDate.value, page, perPage);
};

const goToPage = (page: number) => {
    reportStore.currentPage = page;
    fetchReport(page, perPage.value);
};

const formatRupiah = (value: string): string => {
    const number = parseFloat(value);
    if (isNaN(number)) return 'Rp0,00';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(number);
};

onMounted(() => {
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    startDate.value = firstDayOfMonth.toISOString().split('T')[0];
    endDate.value = today.toISOString().split('T')[0];
    fetchReport(currentPage.value, perPage.value);
});
</script>

<template>
    <Head title="Laporan Pembelian" />
    <AppLayout>
        <template #header>
            <h2 class="text-lg font-semibold text-gray-800">Laporan Pembelian</h2>
        </template>

        <div class="space-y-4 p-4">
            <!-- Filter -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <Input type="date" v-model="startDate" class="w-full sm:w-auto" />
                <Input type="date" v-model="endDate" class="w-full sm:w-auto" />
                <Input type="text" v-model="searchKey" placeholder="Cari produk..." class="flex-1" />
                <Button @click="fetchReport(1, 10)" class="bg-indigo-600 text-white transition hover:bg-indigo-700"> Tampilkan </Button>
            </div>

            <div v-if="reportStore.loading">Loading...</div>
            <div v-else-if="reportStore.error" class="text-red-500">Error: {{ reportStore.error.message }}</div>
            <div v-else-if="reportStore.omsetSummaryPerCustomer.length === 0" class="text-gray-500">Tidak ada data ditampilkan.</div>
            <div v-else class="overflow-auto">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-gray-100 text-sm font-bold">
                            <TableHead>Tanggal</TableHead>
                            <TableHead>Produk ID</TableHead>
                            <TableHead>Produk</TableHead>
                            <TableHead>Qty</TableHead>
                            <TableHead>Harga</TableHead>
                            <TableHead>Total</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableRow
                            v-for="(item, index) in reportStore.omsetSummaryPerCustomer"
                            :key="index"
                            :class="
                                ['text-sm', item.product === 'SUBTOTAL' || item.product === 'TOTAL' ? 'bg-green-100 font-semibold' : ''].join(' ')
                            "
                        >
                            <TableCell>{{ item.tanggal || '' }}</TableCell>
                            <TableCell>{{ item.product_id || '' }}</TableCell>
                            <TableCell>{{ item.product || '' }}</TableCell>
                            <TableCell>{{ item.qty || '' }}</TableCell>
                            <TableCell>{{ item.price ? formatRupiah(item.price.toString()) : '' }}</TableCell>
                            <TableCell>{{ item.total ? formatRupiah(item.total.toString()) : '' }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div class="mt-4 flex justify-end space-x-2">
                    <button
                        @click="goToPage(reportStore.currentPage - 1)"
                        :disabled="reportStore.currentPage <= 1"
                        class="rounded border bg-gray-100 px-3 py-1 hover:bg-gray-200 disabled:opacity-50"
                    >
                        Previous
                    </button>
                    <span class="py-1">Page {{ reportStore.currentPage }} of {{ reportStore.lastPage }}</span>
                    <button
                        @click="goToPage(reportStore.currentPage + 1)"
                        :disabled="reportStore.currentPage >= reportStore.lastPage"
                        class="rounded border bg-gray-100 px-3 py-1 hover:bg-gray-200 disabled:opacity-50"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
