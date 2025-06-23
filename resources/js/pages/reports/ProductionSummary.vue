<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { useReportStore } from '@/stores/useReportStore';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const breadcrumbs = [{ title: 'Report Production Summary', href: '/reports/production-summary' }];

const reportStore = useReportStore();

const startDate = ref('');
const endDate = ref('');
const searchKey = ref('');

const fetchReport = () => {
    reportStore.fetchProductionSummary(startDate.value, endDate.value, searchKey.value);
};

const formatRupiah = (value: number): string => {
    if (typeof value !== 'number' || isNaN(value)) return '0,00';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 2,
    }).format(value);
};

const allActivityRoles = computed(() => {
    const roleMap = new Map<number, string>();

    reportStore.productionSummary.forEach((item) => {
        if (item.activities) {
            for (const [roleId, value] of Object.entries(item.activities)) {
                const id = Number(roleId);
                if (!roleMap.has(id)) {
                    roleMap.set(id, value.name || `Role ${id}`);
                }
            }
        }
    });

    return Array.from(roleMap.entries()).map(([id, name]) => ({ id, name }));
});

onMounted(() => {
    // Set default dates for demonstration or initial load
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    startDate.value = firstDayOfMonth.toISOString().split('T')[0];
    endDate.value = today.toISOString().split('T')[0];
    fetchReport();
});
</script>

<template>
    <Head title="Production Summary Report" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Production Summary Report</h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="mb-4 flex space-x-4">
                        <Input type="date" v-model="startDate" />
                        <Input type="date" v-model="endDate" />
                        <Input type="text" v-model="searchKey" placeholder="Search by product name" />
                        <Button @click="fetchReport">Generate Report</Button>
                    </div>

                    <div v-if="reportStore.loading">Loading...</div>
                    <div v-else-if="reportStore.error" class="text-red-500">Error: {{ reportStore.error.message }}</div>
                    <div v-else>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>ID</TableHead>
                                    <TableHead>Description</TableHead>
                                    <TableHead>Estimation Price/Pcs</TableHead>
                                    <TableHead>Estimation Quantity</TableHead>
                                    <TableHead>Start Date</TableHead>
                                    <TableHead>End Date</TableHead>
                                    <TableHead v-for="role in allActivityRoles" :key="role.id">
                                        {{ role.name }}
                                    </TableHead>
                                    <TableHead>Total Qty</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <TableRow v-for="item in reportStore.productionSummary" :key="item.model_id">
                                    <TableCell>{{ item.model_id }}</TableCell>
                                    <TableCell>{{ item.description }}</TableCell>
                                    <TableCell>{{ formatRupiah(item.estimation_price_pcs) }}</TableCell>
                                    <TableCell>{{ item.estimation_qty }}</TableCell>
                                    <TableCell>{{ item.start_date }}</TableCell>
                                    <TableCell>{{ item.end_date ?? '-' }}</TableCell>

                                    <!-- Loop through each role to show its qty -->
                                    <TableCell v-for="role in allActivityRoles" :key="role.id + '-' + item.model_id">
                                        {{ item.activities?.[role.id]?.qty ?? '-' }}
                                    </TableCell>

                                    <!-- Show total qty per row -->
                                    <TableCell class="font-semibold">
                                        {{ item.subtotal_qty }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>

                            <!-- Optional: Add Footer for column totals -->
                            <tfoot>
                                <TableRow>
                                    <TableCell colspan="6" class="text-right font-bold">Total</TableCell>
                                    <TableCell v-for="role in allActivityRoles" :key="'footer-' + role.id" class="font-bold">
                                        {{
                                            reportStore.productionSummary.reduce((sum, item) => {
                                                return sum + (item.activities?.[role.id]?.qty || 0);
                                            }, 0)
                                        }}
                                    </TableCell>
                                    <TableCell class="font-bold">
                                        {{
                                            reportStore.productionSummary.reduce((sum, item) => {
                                                return sum + (item.subtotal_qty || 0);
                                            }, 0)
                                        }}
                                    </TableCell>
                                </TableRow>
                            </tfoot>
                        </Table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
