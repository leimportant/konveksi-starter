<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { useTransferStockStore } from '@/stores/useTransferStockStore';
import { Head, usePage } from '@inertiajs/vue3';
import { Edit, Eye, Plus, Trash2 } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { onMounted } from 'vue';

const toast = useToast();
const store = useTransferStockStore();
const { transfers } = storeToRefs(store);

onMounted(() => {
    store.fetchTransfers();
});

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
            <div class="mb-6 flex items-center justify-between">
                <Button
                    @click="$inertia.visit(`/transfer-stock/create`)"
                    class="rounded-md bg-indigo-600 py-2 text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500"
                >
                    <Plus class="mr-2 h-4 w-4" />
                    Add
                </Button>
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
        </div>
    </AppLayout>
</template>
