<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePayrollStore, type EmployeePayroll } from '@/stores/usePayrollStore';
import { Head } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { onMounted } from 'vue';

const payroll = usePayrollStore();
const { employees, startDate, endDate, selectedEmployees } = storeToRefs(payroll);
const breadcrumbs = [{ title: 'Payroll Management', href: `/payroll` }];

interface PayrollDetail {
    id: string;
    qty: number;
    price?: number | null;
    model_desc: string;
    variant: string;
    size_id: string;
    activity_role_id?: number | string;
    activity_role?: { id: number | string; name?: string };
    created_at?: string;
}

onMounted(() => {
    const today = new Date();
    const sixDaysAgo = new Date();
    sixDaysAgo.setDate(today.getDate() - 60);

    startDate.value = sixDaysAgo.toISOString().split('T')[0];
    endDate.value = today.toISOString().split('T')[0];

    payroll.loadData();
});


const formatDate = (iso?: string): string => {
    if (!iso) return '-';
    // Ambil hanya bagian tanggal (sebelum "T")
    const onlyDate = iso.split('T')[0]; // hasil: 2025-10-17

    const [y, m, d] = onlyDate.split('-');
    return `${d}/${m}/${y}`; // DD/MM/YYYY
};

const groupByDateAndModel = (emp: EmployeePayroll): Record<string, Record<string, PayrollDetail[]>> => {
  if (!emp.details) return {};

  const grouped: Record<string, Record<string, PayrollDetail[]>> = {};

  emp.details.forEach(d => {
    const date = d.created_at?.split("T")[0] || "-";
    const model = d.model_desc || "-";

    if (!grouped[date]) grouped[date] = {};
    if (!grouped[date][model]) grouped[date][model] = [];

    grouped[date][model].push(d);
  });

  // Sort tanggal descend dan model ascend
  const sorted: Record<string, Record<string, PayrollDetail[]>> = {};
  Object.keys(grouped).sort((a, b) => b.localeCompare(a)).forEach(date => {
    sorted[date] = {};
    Object.keys(grouped[date]).sort().forEach(model => {
      sorted[date][model] = grouped[date][model];
    });
  });

  return sorted;
};

</script>

<template>
    <Head title="Payroll Management" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-4 p-6">
            <!-- Filter / Buttons -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-2">
                <div class="flex flex-col">
                    <label class="mb-1 text-xs font-medium">Start Date</label>
                    <input
                        type="date"
                        v-model.lazy="startDate"
                        class="rounded-md border px-3 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring"
                    />
                </div>

                <div class="flex flex-col">
                    <label class="mb-1 text-xs font-medium">End Date</label>
                    <input
                        type="date"
                        v-model.lazy="endDate"
                        class="rounded-md border px-3 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring"
                    />
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Button @click="payroll.loadData" size="sm" class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
                    Tampilkan
                </Button>
            </div>


            <!-- Table -->
            <div class="overflow-hidden rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-gray-50">
                            <TableHead>Karyawan</TableHead>
                            <TableHead>Detail</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <template v-for="emp in employees" :key="emp.employee_id">
                            <TableRow class="bg-white align-top hover:bg-gray-50">
                                <!-- Employee info -->
                                <TableCell class="align-top">
                                    <div class="flex items-center justify-between rounded bg-gray-200 p-2 font-bold dark:bg-gray-700">
                                        <!-- Checkbox -->
                                        <input
                                            type="checkbox"
                                            :checked="selectedEmployees.includes(emp.employee_id)"
                                            @change="() => payroll.toggleSelect(emp.employee_id)"
                                            class="mr-2 h-4 w-4"
                                        />

                                        <!-- Nama -->
                                        <span class="flex-1 text-sm font-bold text-gray-800">
                                            {{ emp.employee?.name }}
                                        </span>

                                        <!-- Status -->
                                        <span
                                            :class="[
                                                'rounded-md border px-2 py-0.5 text-[10px] font-semibold capitalize',
                                                emp.status === 'closed'
                                                    ? 'border-green-300 bg-green-50 text-green-700'
                                                    : 'border-yellow-300 bg-yellow-50 text-yellow-700',
                                            ]"
                                        >
                                            {{ emp.status }}
                                        </span>
                                    </div>

                                    <!-- Informasi angka -->

                                    <div :class="['text-[11px] text-gray-700 grid sm:grid-cols-2 sm:gap-x-4']">
                                        <div class="flex justify-between">
                                            <span>Qty</span>

                                            <span class="font-bold text-gray-800">{{ emp.total_qty }}</span>
                                        </div>

                                        <div class="flex justify-between">
                                            <span>Upah</span>

                                            <span class="font-bold text-gray-800">
                                                {{ Number(emp.total_upah).toLocaleString() }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between">
                                            <span>Makan</span>

                                            <span class="font-bold text-gray-800">
                                                {{ Number(emp.uang_makan).toLocaleString() }}
                                            </span>
                                        </div>

                                        <!-- Input potongan -->

                                        <div class="flex items-center">
                                            <span>Potongan</span>

                                            <div class="mx-2 flex-grow border-b border-dotted"></div>
                                            {{ Number(emp.potongan).toLocaleString() }}
                                        </div>

                                        <div class="flex justify-between border-t pt-1 sm:border-t-0 sm:pt-0">
                                            <span class="font-semibold text-gray-800">Total Gaji</span>

                                            <span class="font-bold text-gray-900">
                                                {{ Number(emp.net_gaji).toLocaleString() }}
                                            </span>
                                        </div>
                                    </div>

                               </TableCell>

                                <!-- Rincian Aktivitas (Detail) -->
                                <TableCell class="p-0 align-top sm:p-2">
                                    <table class="w-full text-xs">
                                        <template v-for="(models, date) in groupByDateAndModel(emp)" :key="date">
                                            <!-- Header tanggal -->
                                            <tr class="bg-gray-200 font-bold dark:bg-gray-700">
                                                <td colspan="4" class="px-3 py-2">📅 {{ formatDate(date) }}</td>
                                            </tr>

                                            <!-- Loop model -->
                                            <template v-for="(details, model) in models" :key="model">
                                                <tr class="bg-blue-50 font-semibold">
                                                    <td colspan="4" class="px-3 py-2">🧵 Model: {{ model }}</td>
                                                </tr>
                                                <!-- Loop detail data -->
                                                <tr v-for="d in details" :key="d.id" class="border-b bg-white">
                                                    <td class="p-2">{{ d.size_id }} - {{ d.variant }}</td>
                                                    <td class="p-2 text-right">
                                                        {{ Number(d.qty).toLocaleString() }}
                                                    </td>
                                                </tr>
                                            </template>
                                        </template>

                                        <!-- Jika kosong -->
                                        <tr v-if="!emp.details?.length">
                                            <td colspan="4" class="p-4 text-center text-gray-500">Tidak ada data rincian aktivitas.</td>
                                        </tr>
                                    </table>
                                </TableCell>
                            </TableRow>
                        </template>
                    </TableBody>
                </Table>
            </div>

        </div>
    </AppLayout>
</template>
