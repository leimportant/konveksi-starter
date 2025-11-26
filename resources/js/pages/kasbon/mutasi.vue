<script setup lang="ts">
import Modal from '@/components/Modal.vue'; // Assuming you have a Modal component
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { useKasbonStore } from '@/stores/useKasbonStore';
import { Head, usePage } from '@inertiajs/vue3';
import { CheckCircle, CreditCard, Plus, Search } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { computed, onMounted, reactive, ref, Ref } from 'vue';

const kasbonStore = useKasbonStore();
const { mutasiList, pagination, loading } = storeToRefs(kasbonStore);
const toast = useToast();
const page = usePage();
const user = page.props.auth.user;

const filterStatus: Ref<string | { value: string; label: string }> = ref('');

const breadcrumbs = [{ title: 'Mutasi Kasbon', href: '/kasbon/mutasi' }];

const filters = reactive({
    search: '',
});

const showPaymentDialog = ref(false);
const paymentForm = reactive({
    employee_id: 0,
    amount: 0,
    lastSaldoKasbon: 0,
});
const selectedEmployeeName = ref('');

const handleSearch = () => {
    const statusVal = typeof filterStatus.value === 'string' ? filterStatus.value : (filterStatus.value?.value ?? '');

    kasbonStore.fetchMutasi(1, 50, {
        search: filters.search,
        status: statusVal,
    });
};

const groupedMutasi = computed(() => {
    const groups: Record<string, typeof mutasiList.value> = {};

    mutasiList.value.forEach((item) => {
        const name = item.employee_name || '-';
        if (!groups[name]) groups[name] = [];
        groups[name].push(item);
    });

    return groups;
});

// Pagination setup
const totalPages = computed(() => Math.ceil(pagination.value.total / pagination.value.per_page) || 1);
const goToPage = async (page: number) => {
    if (page < 1 || page > totalPages.value) return;
    await kasbonStore.fetchMutasi(page);
};
const nextPage = () => goToPage(pagination.value.current_page + 1);
const prevPage = () => goToPage(pagination.value.current_page - 1);

const tambahPembayaran = (employeeId: number, lastSaldoKasbon: number) => {
    // Find the employee name from groupedMutasi
    const employeeGroup = mutasiList.value.find((item) => item.kasbon_id === employeeId);
    selectedEmployeeName.value = employeeGroup?.employee_name || '-';

    paymentForm.employee_id = employeeId;
    paymentForm.amount = lastSaldoKasbon; // Default to paying off the full balance
    paymentForm.lastSaldoKasbon = lastSaldoKasbon;
    showPaymentDialog.value = true;
};

const storePembayaran = async () => {
    if (!confirm('Yakin ingin melakukan pembayaran kasbon ini?')) return;

    const payload = {
        employee_id: Number(paymentForm.employee_id),
        amount: Number(paymentForm.amount),
        type: 'Pembayaran',
        description: Number(paymentForm.amount) === Number(paymentForm.lastSaldoKasbon)
            ? 'Pelunasan Kasbon'
            : 'Pembayaran Kasbon',
    };


    try {
        await kasbonStore.bayarKasbon(payload);
        toast.success('Pembayaran kasbon berhasil!');
        showPaymentDialog.value = false; // Close dialog on success
    } catch (error: any) {
        toast.error(error.message || 'Gagal melakukan pembayaran kasbon.');
    }
};

// Load initial data
onMounted(() => {
    const now = new Date();
    const month = now.toISOString().slice(0, 7);
    filterStatus.value = month;
    kasbonStore.fetchMutasi();
});
</script>

<template>
    <Head title="History Mutasi Management" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-4">
            <!-- Header -->
            <div class="mb-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                <!-- Filter kiri -->
                <div class="flex w-full items-center gap-3 sm:w-auto">
                    <input
                        type="month"
                        v-model="filterStatus"
                        class="w-full rounded-lg border border-gray-300 bg-white px-2 py-2 text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 sm:w-auto"
                    />

                    <!-- Input Pencarian -->
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Cari data..."
                        @keyup.enter="handleSearch"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:w-56"
                    />

                    <!-- Tombol Cari -->
                    <Button @click="handleSearch" class="flex items-center gap-1 bg-indigo-600 text-white hover:bg-indigo-700">
                        <Search class="h-4 w-4" /> Cari
                    </Button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-lg border shadow-sm">
                <Table class="min-w-full">
                    <TableHeader>
                        <TableRow class="bg-gray-100">
                            <TableHead class="px-4 py-2 text-left text-sm font-medium text-gray-700">Name</TableHead>
                            <TableHead class="px-4 py-2 text-right text-sm font-medium text-gray-700">Amount</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <template v-for="(group, employee) in groupedMutasi" :key="employee">
                            <!-- Employee Name Row -->
                            <TableRow class="border-b bg-gray-50">
                                <TableCell class="px-4 py-2 font-semibold text-gray-800" colspan="3">
                                    <!-- WRAPPER FLEX -->
                                    <div class="flex w-full items-center justify-between">
                                        <!-- Employee Name -->
                                        <span>{{ employee }}</span>

                                        <!-- Tombol Pembayaran Tipis -->
                                        <Button
                                            v-if="user.employee_status.toUpperCase() == 'OWNER'"
                                            @click="tambahPembayaran((group[0] as any).kasbon_id, group[group.length - 1].saldo_kasbon)"
                                            variant="outline"
                                            class="flex h-7 items-center gap-1 rounded-md border-purple-600 px-2 py-1 text-purple-700 hover:bg-purple-600 hover:text-white"
                                        >
                                            <Plus class="h-4 w-4" />
                                            <span class="text-xs">Bayar Kasbon</span>
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <!-- Detail Rows -->
                            <template v-for="kas in group" :key="kas.id">
                                <TableRow class="border-b bg-white align-top">
                                    <TableCell class="flex flex-col px-4 py-1 text-sm text-gray-600">
                                        <!-- Top line: icon + date -->
                                        <div class="flex items-center gap-1">
                                            <component
                                                v-if="kas.type === 'Kasbon' || kas.type === 'Pembayaran'"
                                                :is="kas.type === 'Kasbon' ? CreditCard : CheckCircle"
                                                class="h-4 w-4 text-blue-500"
                                            />
                                            <span>{{ kas.created_at ? new Date(kas.created_at).toLocaleDateString('id-ID') : '-' }}</span>
                                        </div>

                                        <!-- Description on new line, slightly bolder -->
                                        <div class="text-md mt-1 uppercase text-gray-800">{{ kas.type }}</div>
                                    </TableCell>

                                    <TableCell
                                        class="px-4 py-1 text-right align-top text-sm font-medium"
                                        :class="kas.type === 'Kasbon' ? 'text-green-600' : kas.type === 'Pembayaran' ? 'text-red-600' : ''"
                                    >
                                        {{ Number(kas.amount || 0).toLocaleString() }}
                                        <div class="mt-1 text-xs text-gray-500">
                                            saldo kasbon: {{ Number(kas.saldo_kasbon || 0).toLocaleString() }}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </template>
                        </template>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 flex justify-end space-x-2">
                <button
                    @click="prevPage"
                    :disabled="pagination.current_page === 1 || loading"
                    class="rounded border px-3 py-1 hover:bg-gray-100 disabled:opacity-50"
                >
                    Previous
                </button>
                <span>Page {{ pagination.current_page }} / {{ totalPages }}</span>
                <button
                    @click="nextPage"
                    :disabled="pagination.current_page >= totalPages || loading"
                    class="rounded border px-3 py-1 hover:bg-gray-100 disabled:opacity-50"
                >
                    Next
                </button>
            </div>
        </div>
    </AppLayout>

    <Modal :show="showPaymentDialog" @close="showPaymentDialog = false" @confirm="storePembayaran">
        <div class="space-y-4">
            <!-- Title -->
            <div>
                <h3 class="text-lg font-semibold tracking-tight text-gray-900">Bayar Kasbon</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Pembayaran untuk <span class="font-semibold text-gray-700">{{ selectedEmployeeName }}</span>
                </p>
            </div>

            <!-- Input -->
            <div class="space-y-2">
                <label for="amount" class="block text-sm font-medium text-gray-700">Jumlah Pembayaran</label>
                <input
                    type="number"
                    id="amount"
                    v-model="paymentForm.amount"
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm shadow-sm transition focus:border-purple-500 focus:bg-white focus:ring-2 focus:ring-purple-200"
                />
            </div>

            <!-- Info Sisa Kasbon -->
            <p class="text-sm text-gray-600">
                Sisa kasbon:
                <span class="font-semibold text-gray-900">
                    {{ Number(paymentForm.lastSaldoKasbon).toLocaleString() }}
                </span>
            </p>

            <!-- Buttons -->
            <div class="flex justify-end gap-2 pt-2">
                <!-- Cancel Button -->
                <Button
                    @click="showPaymentDialog = false"
                    variant="outline"
                    class="h-9 rounded-md border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900"
                >
                    Cancel
                </Button>

                <!-- Save Button -->
                <Button
                    @click="storePembayaran"
                    class="h-9 rounded-md bg-purple-600 px-4 text-sm font-medium text-white shadow-sm hover:bg-purple-700 focus:ring-2 focus:ring-purple-500"
                >
                    Save
                </Button>
            </div>
        </div>
    </Modal>
</template>
