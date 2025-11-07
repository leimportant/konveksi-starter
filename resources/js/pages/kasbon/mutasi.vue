<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { useKasbonStore } from '@/stores/useKasbonStore';
import { Head } from '@inertiajs/vue3';
import { CheckCircle, CreditCard, Search } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { computed, onMounted, reactive, ref, Ref } from 'vue';

const kasbonStore = useKasbonStore();
const { mutasiList, pagination, loading } = storeToRefs(kasbonStore);
// const page = usePage();
// const user = page.props.auth.user;

const filterStatus: Ref<string | { value: string; label: string }> = ref('');

const breadcrumbs = [{ title: 'Mutasi Kasbon', href: '/kasbon/mutasi' }];

const filters = reactive({
    search: '',
});

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
                                    {{ employee }}
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
                                        <div class="mt-1 text-md text-gray-800 uppercase" v-html="kas.description || '-'"></div>
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
</template>
