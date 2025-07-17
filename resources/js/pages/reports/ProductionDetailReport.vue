<script setup lang="ts">
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { useReportStore } from '@/stores/useReportStore';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';

const reportStore = useReportStore();

const startDate = ref('');
const endDate = ref('');
const searchKey = ref('');

const fetchReport = () => {
    reportStore.fetchProductionDetail(startDate.value, endDate.value, searchKey.value, reportStore.currentPage);
};
const formatDate = (date: string | null | undefined) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
const formatRupiah = (value: number): string => {
    if (typeof value !== 'number' || isNaN(value)) return '';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 2,
    }).format(value);
};

// const allActivityRoles = computed(() => {
//   const roleMap = new Map<number, string>();
//   reportStore.productionDetailItem.forEach((item) => {
//     if (item.activities) {
//       for (const [roleId, value] of Object.entries(item.activities)) {
//         const id = Number(roleId);
//         if (!roleMap.has(id)) {
//           roleMap.set(id, value.name || `Role ${id}`);
//         }
//       }
//     }
//   });
//   return Array.from(roleMap.entries()).map(([id, name]) => ({ id, name }));
// });

onMounted(() => {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    startDate.value = firstDay.toISOString().split('T')[0];
    endDate.value = today.toISOString().split('T')[0];
    fetchReport();
});

watch(() => reportStore.currentPage, fetchReport);
</script>

<template>
    <Head title="Production Detail Report" />

    <AppLayout>
        <template #header>
            <h2 class="text-sm font-semibold text-gray-800">Production Detail Report</h2>
        </template>

        <div class="p-4">
            <!-- Filter -->
            <div class="mb-4 flex flex-wrap gap-2">
                <input type="date" v-model="startDate" class="rounded border px-2 py-1" />
                <input type="date" v-model="endDate" class="rounded border px-2 py-1" />
                <input type="text" v-model="searchKey" placeholder="Search by product name" class="rounded border px-2 py-1" />
                <button @click="fetchReport" class="rounded bg-blue-600 px-4 py-1 text-white hover:bg-blue-700">Tampilkan</button>
            </div>

            <!-- Table -->
            <div v-if="reportStore.loading">Loading...</div>
            <div v-else-if="reportStore.error" class="text-red-500">Error: {{ reportStore.error.message }}</div>
            <div v-else-if="reportStore.productionDetailItem.length === 0" class="text-gray-500">Tidak ada data ditampilkan.</div>
            <div v-else class="overflow-auto">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-gray-100">
                            <TableHead>Tanggal</TableHead>
                            <TableHead>Staff</TableHead>
                            <TableHead>Model</TableHead>
                            <TableHead>Activity Role</TableHead>
                            <TableHead>Variant</TableHead>
                            <TableHead>Qty</TableHead>
                            <TableHead>Unit Price</TableHead>
                            <TableHead>Subtotal</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableRow v-for="item in reportStore.productionDetailItem" :key="item.model_id">
                            <template v-if="item.staff_name && (item.staff_name.startsWith('SUBTOTAL') || item.staff_name.startsWith('TOTAL'))">
                                <TableCell :colspan="7" class="text-center font-bold">{{ item.staff_name }}</TableCell>
                                <TableCell class="font-bold">{{ formatRupiah(item.total) }}</TableCell>
                            </template>
                            <template v-else>
                                <TableCell>{{ formatDate(item.created_at) }}</TableCell>
                                <TableCell>{{ item.staff_name }}</TableCell>
                                <TableCell>{{ item.model_name }}</TableCell>
                                <TableCell>{{ item.activity_role_name }}</TableCell>
                                <TableCell>{{ item.variant }}</TableCell>
                                <TableCell>{{ item.qty }}</TableCell>
                                <TableCell>{{ formatRupiah(item.unit_price) }}</TableCell>
                                <TableCell>{{ formatRupiah(item.total) }}</TableCell>
                            </template>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div class="mt-4 flex justify-end space-x-2">
                    <button
                        @click="reportStore.currentPage--"
                        :disabled="reportStore.currentPage <= 1"
                        class="rounded border bg-gray-100 px-3 py-1 hover:bg-gray-200 disabled:opacity-50"
                    >
                        Previous
                    </button>
                    <span class="py-1">Page {{ reportStore.currentPage }} of {{ reportStore.lastPage }}</span>
                    <button
                        @click="reportStore.currentPage++"
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
