<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { useInventoryStore } from '@/stores/useInventoryStore';
import { Head } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { Input } from '@/components/ui/input'
import { storeToRefs } from 'pinia';
import { computed, onMounted, watch } from 'vue';

const store = useInventoryStore();
const { inventoryRpt, filters, currentPage, loading, lastPage } = storeToRefs(store);

const breadcrumbs = [{ title: 'Inventory', href: '/inventory' }];

// Fetch filter options on mounted

onMounted(async () => {
    await store.fetchInventory(1);
});

// Watch filters to reload inventory on change, reset page to 1
watch(
    filters,
    async () => {
        await store.fetchInventory(1);
    },
    { deep: true },
);

// Computed total pages
const totalPages = computed(() => lastPage.value || 1);

// Pagination navigation
const goToPage = async (page: number) => {
    if (page < 1 || page > totalPages.value) return;
    await store.fetchInventory(page);
};

const nextPage = () => goToPage(currentPage.value + 1);
const prevPage = () => goToPage(currentPage.value - 1);
</script>

<template>
    <Head title="Inventory Management" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-4">
            <div class="mb-6 flex items-center justify-between">
                <Button @click="$inertia.visit('/inventory/create')" class="gap-2 bg-blue-600 text-white hover:bg-blue-700">
                    <Plus class="h-4 w-4" /> Add
                </Button>
                <Input
                    v-model="filters.productName"
                    placeholder="Search"
                    @input="store.setFilter('productName', ($event.target as HTMLInputElement).value)"
                    class="w-64 border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                    aria-label="Search"
                    :disabled="loading"
                />
            </div>

            <!-- Inventory Table -->
            <div class="rounded-md border">
                <Table>
                    <TableHeader class="bg-gray-100">
                        <TableRow>
                            <TableHead>Produk</TableHead>
                            <TableHead>Lokasi</TableHead>
                            <TableHead>SLoc</TableHead>
                            <TableHead>Satuan</TableHead>
                            <TableHead>Ukuran</TableHead>
                            <TableHead>Jumlah</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="item in inventoryRpt"
                            :key="`${item.product_id}-${item.location_id}-${item.sloc_id}-${item.size_id}`"
                            
                        >
                            <TableCell>
                                {{ item.product_name }}
                            </TableCell>
                            <TableCell>
                                {{ item.location_name }}
                            </TableCell>
                            <TableCell>
                                {{ item.sloc_name }}
                            </TableCell>
                            <TableCell>
                                {{ item.uom_id }}
                            </TableCell>
                            <TableCell>
                                {{ item.size_id }}
                            </TableCell>
                            <TableCell>
                                {{ item.qty_in }}
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
                    class="rounded border border-gray-300 px-3 py-1 text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
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
                    class="rounded border border-gray-300 px-3 py-1 text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Next
                </button>
            </div>
        </div>
    </AppLayout>
</template>
