<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { useKasbonStore } from '@/stores/useKasbonStore';
import { Head } from '@inertiajs/vue3';
import { Edit, Search } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { computed, onMounted, reactive, ref, Ref } from 'vue';


const kasbonStore = useKasbonStore();
const { kasbonList, pagination, loading } = storeToRefs(kasbonStore);

const currentKasbon = ref<any | null>(null);

// const page = usePage();
// const user = page.props.auth.user;

const filterStatus: Ref<string | { value: string; label: string }> = ref("");



const breadcrumbs = [{ title: 'Mutasi Kasbon', href: '/kasbon/mutasi' }];

const filters = reactive({
    search: '',
});

const handleSearch = () => {

    const statusVal =
        typeof filterStatus.value === 'string'
            ? filterStatus.value
            : filterStatus.value?.value ?? '';

    kasbonStore.fetchMutasi(1, 50, {
        search: filters.search,
        status: statusVal
    });
};

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

// handle view
const handleView = (kasbon: any) => {
    currentKasbon.value = kasbon;
}

</script>

<template>

    <Head title="History Mutasi Management" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-4">
            <!-- Header -->
            <div class="mb-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                <!-- Filter kiri -->
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <input type="month" v-model="filterStatus" class="border border-gray-300 rounded-lg px-2 py-2 text-gray-700 
           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 
           bg-white w-full sm:w-auto" />
                    
                    <!-- Input Pencarian -->
                    <input v-model="filters.search" type="text" placeholder="Cari data..." @keyup.enter="handleSearch"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-full sm:w-56" />

                    <!-- Tombol Cari -->
                    <Button @click="handleSearch"
                        class="bg-indigo-600 text-white hover:bg-indigo-700 flex items-center gap-1">
                        <Search class="h-4 w-4" /> Cari
                    </Button>

                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-gray-100">
                            <TableHead class="px-3 py-2">Karyawan</TableHead>
                            <TableHead class="px-3 py-2">Details</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <template v-for="kas in kasbonList" :key="kas.id">
                            <!-- Baris atas: Employee + Status -->
                            <TableRow class="bg-gray-50">
                                <TableCell colspan="2" class="px-3 py-2">
                                    <div class="flex items-center justify-between">
                                        <span class="font-semibold text-gray-800">
                                            {{ kas.employee ? kas.employee.name : '-' }}
                                        </span>
                                        <span :class="[
                                            'inline-block rounded-full border px-2.5 py-0.5 text-xs font-semibold',
                                            {
                                                'border-yellow-400 bg-yellow-50 text-yellow-700': kas.status === 'Pending',
                                                'border-green-400 bg-green-50 text-green-700': kas.status === 'Approved',
                                                'border-red-400 bg-red-50 text-red-700': kas.status === 'Rejected',
                                            },
                                        ]">
                                            {{ kas.status }}
                                        </span>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <!-- Detail rows -->
                            <TableRow>
                                <TableCell class="w-1/4 px-3 py-1 font-medium">Tanggal</TableCell>
                                <TableCell class="px-3 py-1">
                                    {{ kas.created_at ? new Date(kas.created_at).toLocaleDateString() : '-' }}
                                </TableCell>
                            </TableRow>

                            <TableRow>
                                <TableCell class="w-1/4 px-3 py-1 font-medium">Jumlah Kasbon</TableCell>
                                <TableCell class="px-3 py-1">
                                    {{ Number(kas.amount).toLocaleString() }}
                                </TableCell>
                            </TableRow>

                            <TableRow>
                                <TableCell class="px-3 py-1 font-medium">Keterangan</TableCell>
                                <TableCell class="px-3 py-1">
                                    {{ kas.description || '-' }}
                                </TableCell>
                            </TableRow>

                            <TableRow>
                                <TableCell class="px-3 py-1 font-medium">Actions</TableCell>
                                <TableCell class="flex gap-2 px-3 py-1">
                                    <Button size="icon" variant="ghost" @click="handleView(kas)">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                </TableCell>
                            </TableRow>

                            <!-- Spacer antar employee -->
                            <TableRow>
                                <TableCell colspan="2" class="h-3"></TableCell>
                            </TableRow>
                        </template>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 flex justify-end space-x-2">
                <button @click="prevPage" :disabled="pagination.current_page === 1 || loading"
                    class="rounded border px-3 py-1 hover:bg-gray-100 disabled:opacity-50">
                    Previous
                </button>
                <span>Page {{ pagination.current_page }} / {{ totalPages }}</span>
                <button @click="nextPage" :disabled="pagination.current_page >= totalPages || loading"
                    class="rounded border px-3 py-1 hover:bg-gray-100 disabled:opacity-50">
                    Next
                </button>
            </div>

        </div>
    </AppLayout>
</template>
