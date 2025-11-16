<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePayrollStore, type EmployeePayroll } from '@/stores/usePayrollStore';
import { Head } from '@inertiajs/vue3';
import { PrinterCheck } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { onMounted, ref, watch } from 'vue';

const payroll = usePayrollStore();
const { employees, startDate, endDate } = storeToRefs(payroll);
const breadcrumbs = [{ title: 'Payroll Management', href: `/payroll` }];
const hideDetail = ref(true);
const searchQuery = ref('');
const debouncedSearchQuery = ref('');

let debounceTimer: ReturnType<typeof setTimeout>;
const debounce = (func: (...args: any[]) => void, delay: number) => {
    return (...args: any[]) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => func(...args), delay);
    };
};

watch(searchQuery, (newValue) => {
    debounce(() => {
        debouncedSearchQuery.value = newValue;
        payroll.loadData(newValue); // Call loadData with the debounced value
    }, 500)(); // 500ms debounce delay
});

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
    startDate.value = '2025-11-01';
    endDate.value = '2025-11-16';

    payroll.loadData();
});

watch(debouncedSearchQuery, () => {
    payroll.loadData(debouncedSearchQuery.value);
});

const formatDate = (iso?: string): string => {
    if (!iso) return '-';
    // Ambil hanya bagian tanggal (sebelum "T")
    const onlyDate = iso.split('T')[0]; // hasil: 2025-10-17

    const [y, m, d] = onlyDate.split('-');
    return `${d}/${m}/${y}`; // DD/MM/YYYY
};

const groupByDateAndModel = (
  emp: EmployeePayroll
): Record<string, Record<string, PayrollDetail[]>> => {
  // ✅ jaga supaya nggak error walau undefined atau nested [[...]]
  const details = Array.isArray(emp.details)
    ? emp.details.flat() // flatten nested array
    : [];

  if (!details.length) return {};

  const grouped: Record<string, Record<string, PayrollDetail[]>> = {};

  details.forEach((d) => {
    const date = d.created_at?.split("T")[0] || "-";
    const model = d.model_desc || "-";

    if (!grouped[date]) grouped[date] = {};
    if (!grouped[date][model]) grouped[date][model] = [];

    grouped[date][model].push(d);
  });

  // ✅ sort tanggal descend dan model ascend
  const sorted: Record<string, Record<string, PayrollDetail[]>> = {};
  Object.keys(grouped)
    .sort((a, b) => b.localeCompare(a))
    .forEach((date) => {
      sorted[date] = {};
      Object.keys(grouped[date])
        .sort()
        .forEach((model) => {
          sorted[date][model] = grouped[date][model];
        });
    });

  return sorted;
};

const formatRupiah = (value: number | string, withPrefix: boolean = false): string => {
    if (value === null || value === undefined || value === '') return '';
    const numberValue = typeof value === 'string' ? parseFloat(value.replace(/[^\d,-]/g, '').replace(/,/g, '.')) : value;
    if (isNaN(numberValue)) return '';

    const options: Intl.NumberFormatOptions = {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    };

    if (withPrefix) {
        options.style = 'currency';
        options.currency = 'IDR';
    }

    return new Intl.NumberFormat('id-ID', options).format(numberValue);
};

// Fungsi sumQty
const sumQty = (details: PayrollDetail[]) => {
    return details.reduce((total, item) => total + Number(item.qty), 0);
};

const sumPrice = (details: any[]): number => {
    return details.reduce((sum, d) => {
        const price = d.price_per_unit || d.price || 0;
        return sum + d.qty * price;
    }, 0);
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
                <input
                    type="text"
                    placeholder="Cari"
                    v-model="searchQuery"
                    class="rounded-md border px-3 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring"
                />
                <Button @click="payroll.loadData" size="sm" class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
                    Tampilkan
                </Button>
                  <div class="ml-4 flex items-center">
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input id="detailToggle" type="checkbox" v-model="hideDetail" class="peer sr-only" />
                        <div
                            class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-checked:after:border-white"
                        ></div>
                        <span class="ml-3 text-sm">Detail</span>
                    </label>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-gray-50">
                            <TableHead>Karyawan</TableHead>
                            <TableHead  v-if="hideDetail">Detail</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <template v-for="emp in employees" :key="emp.employee_id">
                            <TableRow class="bg-white align-top hover:bg-gray-50">
                                <!-- Employee info -->
                                <TableCell class="align-top">
                                    <div class="flex items-center justify-between rounded bg-gray-200 p-2 font-bold dark:bg-gray-700">
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

                                    <div :class="['grid text-[11px] text-gray-700 sm:grid-cols-2 sm:gap-x-4']">
         
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

                                        <!-- Tombol aksi -->
                                        <div class="mt-2 p-2 flex gap-2">

                                            <Button
                                                variant="outline"
                                                size="icon"
                                                class="flex flex-1 items-center justify-center gap-2 rounded-md bg-green-600 text-sm font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
                                                @click="$inertia.visit(`/payroll/${emp.id}/view`)"
                                            >
                                                <PrinterCheck class="h-4 w-4" />
                                                Payslip
                                            </Button>
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Rincian Aktivitas (Detail) -->
                                <TableCell class="p-0 align-top sm:p-2"  v-if="hideDetail">
                                    <table class="w-full text-xs">
                                        <template v-for="(models, date) in groupByDateAndModel(emp)" :key="date">
                                            <!-- Header tanggal -->
                                            <tr class="bg-gray-200 font-bold dark:bg-gray-700">
                                                <td colspan="4" class="px-3 py-2">📅 {{ formatDate(date) }}</td>
                                            </tr>

                                            <!-- Loop model -->
                                            <template v-for="(details, model) in models" :key="model">
                                               <tr class="bg-blue-50 font-semibold px-3 mp-2">
                                                        <td colspan="5" class="px-3 py-2">🧵 Model: {{ model }}</td>
                                                    </tr>
                                                 <tr class="bg-gray-100 font-semibold text-gray-600 dark:bg-gray-700">
                                                        <td class="w-1/3 p-2 text-left">Size - Variant</td>
                                                        <td class="w-1/6 p-2 text-right">Qty</td>
                                                        <td class="w-1/6 p-2 text-right">Price</td>
                                                        <td class="w-1/3 p-2 text-right">Total Price</td>
                                                    </tr>
                                                <!-- Loop detail data -->
                                                 <tr v-for="d in details" :key="d.id" class="border-b bg-white">
                                                        <td class="p-2">{{ d.size_id }} - {{ d.variant }}</td>
                                                        <td class="p-2 text-right">
                                                            {{ Number(d.qty).toLocaleString() }}
                                                        </td>
                                                        <td class="p-2 text-right">
                                                            {{ formatRupiah(d.price || 0) }}
                                                        </td>
                                                        <td class="p-2 text-right font-semibold">
                                                            {{ formatRupiah(d.qty * (d.price || 0)) }}
                                                        </td>
                                                    </tr>
                                                    <tr class="bg-blue-100 font-bold">
                                                        <td class="p-2 text-right">Total :</td>
                                                        <td class="p-2 text-right">
                                                            {{ sumQty(details) }}
                                                        </td>
                                                        <td></td>
                                                        <td class="p-2 text-right">
                                                            {{ formatRupiah(sumPrice(details)) }}
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
