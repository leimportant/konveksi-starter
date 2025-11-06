<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePayrollStore, type EmployeePayroll } from '@/stores/usePayrollStore';
import { Head } from '@inertiajs/vue3';
import { CheckCircle, DollarSign } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { computed, onMounted, ref } from 'vue';

const payroll = usePayrollStore();
const { employees, startDate, endDate, selectedEmployees } = storeToRefs(payroll);
const searchQuery = ref('');
const filteredEmployees = computed(() => {
    if (!searchQuery.value) {
        return employees.value;
    }
    return employees.value.filter((emp) =>
        emp.employee_name?.toLowerCase().includes(searchQuery.value.toLowerCase()),
    );
});
const hideDetail = ref(true);

// --- TYPE DEFINITIONS FOR GROUPING ---
// NOTE: I'm defining PayrollDetail here for completeness and to fix TS errors.
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

// Define the expected return type for the grouping function
// interface GroupedDetails {
//   [modelName: string]: PayrollDetail[];
// }
// --- END TYPE DEFINITIONS ---

// Env Base URL
// const baseUrl = import.meta.env.VITE_APP_BASE_URL || ''

// Share slip via WA (1 orang)
// const shareSlipLinkToWhatsApp = (employee: EmployeePayroll) => {
//   const url = `${baseUrl}/payroll/slip/${employee.employee_id}`
//   const text = encodeURIComponent(`Slip Gaji Anda:\n${url}`)
//   window.open(`https://wa.me/?text=${text}`, '_blank')
// }

// Share WA hanya untuk yang dipilih
// const shareAllSlips = () => {
//   const selected = employees.value.filter(emp =>
//     selectedEmployees.value.includes(emp.employee_id)
//   )

//   selected.forEach(emp => shareSlipLinkToWhatsApp(emp))
// }

// Checkbox select all
const toggleSelectAll = (e: Event) => {
    const target = e.target as HTMLInputElement;
    payroll.selectedEmployees = target.checked ? employees.value.map((emp) => emp.employee_id) : [];
};

const breadcrumbs = [{ title: 'Payroll Closing Management', href: `/payroll/closing` }];

onMounted(() => {
    const today = new Date();
    const sixDaysAgo = new Date();
    sixDaysAgo.setDate(today.getDate() - 6);

    startDate.value = sixDaysAgo.toISOString().split('T')[0];
    endDate.value = today.toISOString().split('T')[0];

    payroll.loadPayrollData();
});

// Fungsi sumQty
const sumQty = (details: PayrollDetail[]) => {
    return details.reduce((total, item) => total + Number(item.qty), 0);
};


// --- PERBAIKAN: Mengubah Computed menjadi Function ---
// Fungsi ini sekarang menerima objek karyawan (emp) dan TIDAK bergantung pada props.
// Peringatan 'acc' implisit 'any' juga telah diperbaiki.
// const groupDetailsByModel = (emp: EmployeePayroll): GroupedDetails => {
//     if (!emp.details || emp.details.length === 0) {
//         return {};
//     }

//     // Memberikan tipe eksplisit pada akumulator (acc) untuk menghilangkan peringatan TS.
//     return emp.details.reduce((acc: GroupedDetails, detail: PayrollDetail) => {
//         const model = detail.model_desc ?? '-';
//         if (!acc[model]) {
//             acc[model] = [];
//         }
//         acc[model].push(detail);
//         return acc;
//     }, {} as GroupedDetails); // Memberikan tipe eksplisit pada nilai awal
// };

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
                    <label class="mb-1 text-xs font-medium">Tanggal Mulai</label>
                    <input type="date" v-model.lazy="startDate"
                        class="rounded-md border px-3 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring" />
                </div>

                <div class="flex flex-col">
                    <label class="mb-1 text-xs font-medium">Tanggal Akhir</label>
                    <input type="date" v-model.lazy="endDate"
                        class="rounded-md border px-3 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring" />
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Button @click="payroll.loadPayrollData" size="sm"
                    class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
                    Tampilkan
                </Button>

                <input type="text" placeholder="Cari Karyawan" v-model.lazy="searchQuery"
                    class="rounded-md border px-3 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring" />

                <!-- <Button @click="shareAllSlips" size="sm" variant="outline" :disabled="selectedEmployees.length === 0"
          class="rounded text-sm bg-gray-500 px-4 py-2 text-white hover:bg-gray-600">
          Share WA ({{ selectedEmployees.length }})
        </Button> -->
            </div>

            <div class="flex items-center gap-2">
                <Button @click="payroll.closePayroll" :disabled="selectedEmployees.length === 0"
                    class="bg-green-600 text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <CheckCircle class="h-4 w-4" /> Bayar ({{ selectedEmployees.length }})
                </Button>
                <div class="ml-4 flex items-center">
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input id="detailToggle" type="checkbox" v-model="hideDetail" class="peer sr-only" />
                        <div
                            class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-checked:after:border-white">
                        </div>
                        <span class="ml-3 text-sm">Detail</span>
                    </label>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-gray-50">
                            <TableHead><input type="checkbox" @change="toggleSelectAll" /> Karyawan</TableHead>
                            <TableHead v-if="hideDetail">Detail</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <template v-for="emp in filteredEmployees" :key="emp.employee_id">
                            <TableRow class="bg-white align-top hover:bg-gray-50">
                                <!-- Employee info -->
                                <TableCell class="align-top">
                                    <div
                                        class="border rounded-lg bg-white shadow-sm p-3 mb-2 transition hover:shadow-md">
                                        <!-- Header: Checkbox + Name + Status -->
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center gap-2">
                                                <input type="checkbox"
                                                    :checked="selectedEmployees.includes(emp.employee_id)"
                                                    @change="() => payroll.toggleSelect(emp.employee_id)"
                                                    class="h-4 w-4 accent-green-500" />
                                                <span class="text-sm font-semibold text-gray-900">{{ emp.employee_name
                                                    }}</span>
                                            </div>

                                            <span :class="[
                                                'rounded-full px-2 py-0.5 text-xs font-semibold capitalize',
                                                emp.status === 'closed'
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-yellow-100 text-yellow-800'
                                            ]">
                                                {{ emp.status }}
                                            </span>
                                        </div>

                                        <!-- Informasi angka -->
                                        <div :class="[
                                            'grid gap-1 text-sm text-gray-700',
                                            hideDetail ? 'grid-cols-1' : 'sm:grid-cols-2 md:grid-cols-3'
                                        ]">
                                            <div class="flex justify-between items-center">
                                                <span>Qty</span>
                                                <span class="font-semibold text-gray-900">{{ emp.total_qty }}</span>
                                            </div>

                                            <div class="flex justify-between items-center">
                                                <span>Upah</span>
                                                <span class="font-semibold text-gray-900">{{
                                                    Number(emp.total_upah).toLocaleString() }}</span>
                                            </div>

                                            <div class="flex justify-between items-center">
                                                <span>Makan</span>
                                                <span class="font-semibold text-gray-900">{{
                                                    Number(emp.uang_makan).toLocaleString() }}</span>
                                            </div>

                                            <div class="flex justify-between items-center">
                                                <span>Potongan</span>
                                                <input type="number" v-if="emp.sisa_kasbon > 0" v-model.number="emp.potongan"
                                                    @change="payroll.updatePotongan(emp.employee_id, emp.potongan)"
                                                    class="w-20 text-right text-sm border-b border-gray-300 focus:border-green-500 focus:outline-none" />
                                                
                                                <span v-else class="font-semibold text-gray-900">0</span>
                                            </div>

                                            <!-- Total Gaji -->
                                            <div
                                                class="col-span-full flex justify-between items-center bg-green-50 rounded-md p-1 font-bold">
                                                <span>Total Gaji</span>
                                                <span>{{ Number(emp.total_gaji).toLocaleString() }}</span>
                                            </div>

                                            <!-- Sisa Kasbon -->
                                            <div
                                                class="col-span-full flex justify-between items-center bg-red-50 rounded-md p-1 font-bold">
                                                <span>Sisa Kasbon</span>
                                                <span>{{ Number(emp.sisa_kasbon).toLocaleString() }}</span>
                                            </div>
                                        </div>

                                        <!-- Tombol aksi -->
                                        <div class="mt-2 flex gap-2">
                                            <Button variant="outline" @click="payroll.closeSingle(emp)"
                                                :disabled="emp.status === 'closed'"
                                                class="flex-1 flex items-center justify-center gap-2 rounded-md bg-green-600 text-white text-sm font-semibold hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                                                <DollarSign class="h-4 w-4" v-if="emp.status !== 'closed'" />
                                                <CheckCircle class="h-4 w-4" v-else />
                                                Bayar
                                            </Button>
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Rincian Aktivitas (Detail) -->
                                <TableCell class="p-0 align-top sm:p-2" v-if="hideDetail">
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

                                                <!-- Header kolom -->
                                                <!-- <tr class="font-semibold text-gray-600">
                                                    <td class="p-2 text-left">Size - Variant</td>
                                                    <td class="p-2 text-right">Qty</td>
                                                </tr> -->

                                                <!-- Loop detail data -->
                                                <tr v-for="d in details" :key="d.id" class="border-b bg-white">
                                                    <td class="p-2">{{ d.size_id }} - {{ d.variant }}</td>
                                                    <td class="p-2 text-right">
                                                        {{ Number(d.qty).toLocaleString() }}
                                                    </td>
                                                </tr>

                                                <!-- Total per model  {{ model }} -->
                                                <tr class="bg-blue-100 font-bold">
                                                    <td class="p-2 text-right">Total </td>
                                                    <td class="p-2 text-right">{{ sumQty(details).toLocaleString() }}
                                                    </td>
                                                </tr>
                                            </template>
                                        </template>

                                        <!-- Jika kosong -->
                                        <tr v-if="!emp.details?.length">
                                            <td colspan="4" class="p-4 text-center text-gray-500">Tidak ada data rincian
                                                aktivitas.</td>
                                        </tr>
                                    </table>
                                </TableCell>
                            </TableRow>
                        </template>
                    </TableBody>
                </Table>
            </div>

            <!-- Bulk close -->
            <div class="flex justify-end">
                <Button @click="payroll.closePayroll" :disabled="selectedEmployees.length === 0"
                    class="bg-green-600 text-white">
                    Close Selected ({{ selectedEmployees.length }})
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
