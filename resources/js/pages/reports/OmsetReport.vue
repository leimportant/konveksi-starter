<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { useReportStore } from '@/stores/useReportStore';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
const reportStore = useReportStore();

const breadcrumbs = [{ title: 'Report Omset', href: '/reports/omzet' }];

// interface OmsetPerPayment {
//   tanggal: string;
//   payment_method: string;
//   total_omset: number | string;
// }


const startDate = ref('');
const endDate = ref('');

const currentPage = computed(() => reportStore.currentPage);
const lastPage = computed(() => reportStore.lastPage);
// const totalOmsetRecords = computed(() => reportStore.totalOmsetRecords);
const perPage = computed(() => reportStore.perPage);

const fetchReport = async (page: number = 1, perPage: number = 10) => {
    await reportStore.fetchOmsetPerPayment(startDate.value, endDate.value, page, perPage);
    console.log('Current Page:', reportStore.currentPage);
    console.log('Last Page:', reportStore.lastPage);
    console.log('Total Omset Records:', reportStore.totalOmsetRecords);
};

const totalPages = computed(() => Math.ceil(reportStore.totalOmsetRecords / reportStore.perPage));

const prevPage = () => {
    goToPage(currentPage.value - 1);
};

const nextPage = () => {
    goToPage(currentPage.value + 1);
};

const goToPage = (page: number) => {
    console.log('Attempting to go to page:', page);
    console.log('Current Page (before check):', currentPage.value);
    console.log('Last Page (before check):', lastPage.value);
    fetchReport(page, perPage.value);
};

const formatRupiah = (value: string): string => {
    const number = parseFloat(value);
    if (isNaN(number)) return 'Rp0,00';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 2,
    }).format(number);
};

const getBarWidth = (amount: string | number, max = 500000): number => {
    const value = typeof amount === 'string' ? parseFloat(amount) : amount;
    const percentage = Math.min((value / max) * 100, 100); // Batas 100%
    return Math.round(percentage);
};

// const omsetSummary = computed(() => reportStore.omsetSummary);

// const totalOmset = computed(() => {
//   return omsetSummary.value
//     .filter((item: OmsetPerPayment) => item.payment_method !== 'SUBTOTAL')
//     .reduce((sum: number, item) => sum + Number(item.total_omset), 0);
// });


onMounted(() => {
    // Set default dates for demonstration or initial load
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    startDate.value = firstDayOfMonth.toISOString().split('T')[0];
    endDate.value = today.toISOString().split('T')[0];
    fetchReport(currentPage.value, perPage.value);
});
</script>
<template>

    <Head title="Omset Report" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-2">
            <div class="mx-auto max-w-7xl sm:px-2 lg:px-2">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="mb-4 flex space-x-4">
                        <Input type="date" v-model="startDate" />
                        <Input type="date" v-model="endDate" />
                        <Button @click="fetchReport(1, perPage)" class="bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">Tampilkan</Button>
                    </div>

                    <div v-if="reportStore.loading">Loading...</div>
                    <div v-else-if="reportStore.error" class="text-red-500">Error: {{ reportStore.error.message }}</div>
                    <div v-else>
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-gray-100">
                                    <TableHead>Tanggal</TableHead>
                                    <TableHead>Payment Method</TableHead>
                                    <TableHead>Omset</TableHead>
                                    <TableHead>Bars</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="item in reportStore.omsetSummary" :key="item.tanggal"
                                    :class="item.payment_method === 'SUBTOTAL' ? 'bg-green-100 font-semibold' : ''">
                                    <TableCell>{{ item.tanggal }}</TableCell>
                                    <TableCell>{{ item.payment_method }}</TableCell>
                                    <TableCell>{{ formatRupiah(item.total_omset.toString()) }}</TableCell>
                                    <TableCell>
                                        <div class="flex gap-1 items-end h-4">
                                            <div class="bg-green-500 h-full"
                                                :style="{ width: `${getBarWidth(item.total_omset)}%` }"
                                                v-if="item.payment_method === 'CASH'">
                                            </div>
                                            <div class="bg-indigo-500 h-full"
                                                :style="{ width: `${getBarWidth(item.total_omset)}%` }"
                                                v-if="item.payment_method === 'QRIS' || item.payment_method === 'TRANSFER'">
                                            </div>
                                            <div class="bg-blue-500 h-full"
                                                :style="{ width: `${getBarWidth(item.total_omset)}%` }"
                                                v-else-if="item.payment_method === 'CREDIT'"></div>
                                        </div>
                                    </TableCell>

                                </TableRow>
                                <!-- <TableFooter>
                                    <TableRow class="bg-blue-100 font-bold">
                                        <TableCell colspan="2">TOTAL</TableCell>
                                        <TableCell>{{ formatRupiah(totalOmset.toString()) }}</TableCell>
                                        <TableCell></TableCell>
                                    </TableRow>
                                </TableFooter> -->

                            </TableBody>
                        </Table>

                        <div class="mt-4 flex items-center justify-between">

                            <!-- Pagination -->
                            <div class="mt-4 flex justify-end space-x-2">
                                      <button @click="prevPage" :disabled="currentPage === 1"
                                    class="rounded border border-gray-300 px-3 py-1 text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50">
                                    Previous
                                </button>

                                <template v-for="page in totalPages" :key="page">
                                    <button @click="goToPage(page)" :class="[
                                        'rounded border px-3 py-1 text-sm',
                                        page === currentPage ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-100',
                                    ]">
                                        {{ page }}
                                    </button>
                                </template>

                                <button @click="nextPage" :disabled="currentPage === totalPages"
                                    class="rounded border border-gray-300 px-3 py-1 text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50">
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
