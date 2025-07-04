<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { useProductionStore } from '@/stores/useProductionStore';
import { Head } from '@inertiajs/vue3';
import { Edit, LucideView, Plus, Trash2 } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { onMounted, ref } from 'vue';

const toast = useToast();
const productionStore = useProductionStore();
const { productions, loading } = storeToRefs(productionStore);

const currentPage = ref(1);
const perPage = ref(10);
const sortField = ref('created_at');
const sortOrder = ref<'asc' | 'desc'>('desc');

const props = defineProps<{
    activity_role: string | number;
}>();

const breadcrumbs = [{ title: 'Production', href: `/production/${props.activity_role}` }];

const searchQuery = ref('');
interface DateRange {
    from: Date | null;
    to: Date | null;
}

const dateRange = ref<DateRange>({
    from: null,
    to: null,
});

const fetchData = async () => {
    try {
        await productionStore.fetchProductions({
            page: currentPage.value,
            per_page: perPage.value,
            sort_field: sortField.value,
            sort_order: sortOrder.value,
            activity_role_id: props.activity_role,
            search: searchQuery.value,
            date_from: dateRange.value.from?.toISOString(),
            date_to: dateRange.value.to?.toISOString(),
        });
    } catch (error: any) {
        toast.error(error?.response?.data?.message ?? 'Failed to fetch productions');
    }
};

onMounted(fetchData);

const handleDelete = async (id: string) => {
    if (!confirm('Are you sure you want to delete this production?')) return;

    try {
        await productionStore.deleteProduction(id);
        toast.success('Production deleted successfully');
        await fetchData();
    } catch (error: any) {
        toast.error(error?.response?.data?.message ?? 'Failed to delete production');
    }
};
</script>

<template>
    <Head title="Production Management" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-2 py-2">
            <!-- Top Bar -->
            <div class="mb-6 flex items-center justify-between">
                <Button
                    @click="$inertia.visit(`/production/${props.activity_role}/create`)"
                    class="rounded-md bg-indigo-600 py-2 text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500"
                >
                    <Plus class="mr-2 h-4 w-4" />
                    Tambah Data
                </Button>
                <Input v-model="searchQuery" placeholder="Search..." class="w-full md:w-64" @keyup.enter="fetchData" />
            </div>

            <div class="space-y-4">
                <div v-if="loading" class="py-4 text-center text-gray-500">Loading...</div>

                <!-- Responsive Table Container -->
                <div v-else class="w-full overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <Table class="w-full min-w-[600px] text-left text-sm text-gray-700 dark:text-gray-200">
                        <TableHeader>
                            <TableRow class="bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                <TableHead class="px-3 py-2">Model</TableHead>
                                <TableHead class="px-3 py-2">Activity</TableHead>
                                <TableHead class="px-3 py-2">Size/Qty</TableHead>
                                <TableHead class="px-3 py-2">Created At</TableHead>
                                <TableHead class="px-3 py-2 text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="item in productions"
                                :key="item.id"
                                class="border-b border-gray-100 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                            >
                                <TableCell class="px-3 py-2">
                                    {{ item.model?.description || '-' }}
                                </TableCell>
                                <TableCell class="px-3 py-2">
                                    {{ item.activity_role?.name || '-' }}
                                </TableCell>
                                <TableCell class="px-3 py-2">
                                    <template v-if="item.items?.length">
                                        <div v-for="i in item.items" :key="i.id">{{ i.size_id }} : {{ i.qty }}</div>
                                    </template>
                                    <template v-else>-</template>
                                </TableCell>
                                <TableCell class="px-3 py-2">
                                    {{ item.created_at ? new Date(item.created_at).toLocaleDateString() : '-' }}
                                </TableCell>
                                <TableCell class="px-3 py-2 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button
                                            v-if="item.status === 1 || item.status === 3"
                                            variant="ghost"
                                            size="icon"
                                            class="hover:bg-gray-100 dark:hover:bg-gray-700"
                                            @click="$inertia.visit(`/production/${item.activity_role_id}/edit/${item.id}`)"
                                        >
                                            <Edit class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            v-if="item.status === 1 || item.status === 3"
                                            variant="ghost"
                                            size="icon"
                                            class="hover:bg-gray-100 dark:hover:bg-gray-700"
                                            @click="handleDelete(item.id)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="hover:bg-gray-100 dark:hover:bg-gray-700"
                                            @click="$inertia.visit(`/production/${item.activity_role_id}/view/${item.id}`)"
                                        >
                                            <LucideView class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
