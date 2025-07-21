<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { useTransferStockStore } from '@/stores/useTransferStockStore';
import { Head, usePage } from '@inertiajs/vue3';
import { Edit, Eye, Plus, Trash2 } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { onMounted, computed, watch, ref } from 'vue';

const toast = useToast();
const store = useTransferStockStore();
const { transfers, filters, loading, currentPage, lastPage } = storeToRefs(store);

const perPage = ref(10);

onMounted(() => {
    store.fetchTransfers(currentPage.value, perPage.value);
});

watch(
    filters,
    async () => {
        await store.fetchTransfers(1, perPage.value);
    },
    { deep: true },
);

watch(currentPage, async (newPage) => {
    await store.fetchTransfers(newPage, perPage.value);
});

const totalPages = computed(() => lastPage.value || 1);
const goToPage = async (page: number) => {
    if (page < 1 || page > totalPages.value) return;
    currentPage.value = page;
};

const nextPage = () => goToPage(currentPage.value + 1);
const prevPage = () => goToPage(currentPage.value - 1);

const handleDelete = async (id: string) => {
    if (!confirm('Are you sure you want to delete this transfer?')) return;
    try {
        await store.deleteTransfer(id);
        await store.fetchTransfers();
        toast.success('Transfer deleted successfully');
    } catch (error: any) {
        toast.error(error?.response?.data?.message ?? 'Failed to delete transfer');
    }
};
interface User {
    id: number;
    type: string | 'admin';
    name: string;
    avatar: string;
    location_id: number;
    employee_status: string | 'admin';
}

const page = usePage();
const user = page.props.auth.user as User;
const locationId = user.location_id;

const breadcrumbs = [{ title: 'Transfer Stocks', href: '/transfer-stocks' }];




</script>

<template>
    <Head title="Transfer Stocks" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-4">

             <!-- Top bar -->
            <div class="mb-6 flex items-center justify-between">
                <Button @click="$inertia.visit(`/transfer-stock/create`)" class="gap-2 bg-blue-600 text-white hover:bg-blue-700">
                    <Plus class="h-4 w-4" /> Add
                </Button>
                <Input
                    v-model="filters.productName"
                    placeholder="Search"
                    class="w-64 border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                    aria-label="Search"
                    :disabled="loading"
                />
            </div>


            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-gray-100">
                            <TableHead>Location</TableHead>
                            <TableHead>Destination</TableHead>
                            <TableHead>Sloc</TableHead>
                            <TableHead>Date</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="w-24">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="transfer in transfers" :key="transfer.id">
                            <TableCell>{{ transfer.location?.name }}</TableCell>
                            <TableCell>{{ transfer.location_destination?.name }}</TableCell>
                            <TableCell>{{ transfer.sloc_id }}</TableCell>
                            <TableCell>{{ transfer.created_at ? new Date(transfer.created_at).toLocaleDateString() : '-' }}</TableCell>
                            <TableCell>
                                <!-- 'Pending','Accept','Reject' -->
                                <span
                                    :class="transfer.status == 'Pending' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                    class="rounded-full px-2 py-1 text-xs font-semibold"
                                >
                                    {{ transfer.status }}
                                </span>
                            </TableCell>
                            <TableCell class="flex gap-2">
                                <Button
                                    v-if="
                                        transfer?.id &&
                                        (transfer.status === 'Accepted' || transfer.status === 'Rejected') ||
                                        transfer.location_destination?.id === locationId
                                    "
                                    variant="ghost"
                                    size="icon"
                                    class="hover:bg-gray-100"
                                    @click="$inertia.visit(`/transfer-stock/${transfer.id}/view`)"
                                >
                                    <Eye class="h-4 w-4" />
                                </Button>
                                <Button
                                    v-if="transfer?.id && (transfer.status === 'Pending' || transfer.status === 'Rejected')"
                                    variant="ghost"
                                    size="icon"
                                    class="hover:bg-gray-100"
                                    @click="$inertia.visit(`/transfer-stock/${transfer.id}/edit`)"
                                >
                                    <Edit class="h-4 w-4" />
                                </Button>
                                <Button
                                    v-if="transfer?.id && (transfer.status === 'Pending' || transfer.status === 'Rejected')"
                                    variant="ghost"
                                    size="icon"
                                    class="hover:bg-gray-100"
                                    @click="handleDelete(transfer.id)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            <!-- Pagination -->
            <div class="mt-4 flex justify-end space-x-2">
                <button
                    @click="prevPage"
                    :disabled="currentPage === 1"
                    class="rounded border border-gray-300 px-3 py-1 text-gray-700 hover:bg-gray-100 disabled:opacity-50"
                >
                    Previous
                </button>
                <template v-for="page in totalPages" :key="page">
                    <button
                        @click="goToPage(page)"
                        :class="[
                            'rounded border px-3 py-1 text-sm',
                            page === currentPage ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-100',
                        ]"
                    >
                        {{ page }}
                    </button>
                </template>
                <button
                    @click="nextPage"
                    :disabled="currentPage === totalPages"
                    class="rounded border border-gray-300 px-3 py-1 text-gray-700 hover:bg-gray-100 disabled:opacity-50"
                >
                    Next
                </button>
            </div>
        </div>
    </AppLayout>
</template>
