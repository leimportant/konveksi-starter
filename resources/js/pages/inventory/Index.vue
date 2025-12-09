<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { useInventoryStore } from '@/stores/useInventoryStore';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { Edit, Plus, Trash2 } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { computed, onMounted, ref, watch } from 'vue';

const toast = useToast();
const isDialogEditQty = ref(false);
const newQty = ref(0);

type InventoryItem = {
    product_id: number;
    product_name: string;
    location_id: number;
    location_name: string;
    sloc_id: string;
    sloc_name: string;
    uom_id: string;
    size_id: string;
    qty: number;
};

// ✅ only one definition with correct type
const dialogEditQtyItem = ref<InventoryItem | null>(null);

const store = useInventoryStore();
const { inventoryRpt, filters, currentPage, loading, lastPage } = storeToRefs(store);

const breadcrumbs = [{ title: 'Inventory', href: '/inventory' }];

onMounted(async () => {
    await store.fetchInventory(1);
});

watch(
    filters,
    async () => {
        await store.fetchInventory(1);
    },
    { deep: true },
);

const totalPages = computed(() => lastPage.value || 1);
const goToPage = async (page: number) => {
    if (page < 1 || page > totalPages.value) return;
    await store.fetchInventory(page);
};

const nextPage = () => goToPage(currentPage.value + 1);
const prevPage = () => goToPage(currentPage.value - 1);

const handleDialogEditQty = (item: InventoryItem) => {
    isDialogEditQty.value = true;
    dialogEditQtyItem.value = item;
    newQty.value = item.qty;
};

const handleSaveEditQty = async () => {
    if (!dialogEditQtyItem.value) return;

    try {
        await axios.put(
            `/api/inventory/${dialogEditQtyItem.value.product_id}/${dialogEditQtyItem.value.location_id}/${dialogEditQtyItem.value.sloc_id}/${dialogEditQtyItem.value.size_id}`,
            {
                qty: newQty.value,
            },
        );
        toast.success('Inventory updated successfully');
        isDialogEditQty.value = false;
        await store.fetchInventory(currentPage.value);
    } catch (error) {
        console.error('Error updating inventory:', error);
        toast.error('Failed to update inventory');
    }
};

const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to delete this inventory?')) return;
    try {
        await store.deleteInventory(id);
        toast.success('Inventory deleted');
        await store.fetchInventory(currentPage.value);
    } catch (error) {
        console.error('Error deleting inventory:', error);
        toast.error('Failed to delete inventory');
    }
};
</script>

<template>
    <Head title="Inventory Management" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-4">
            <!-- Top bar -->
            <div class="mb-6 flex items-center justify-between">
                <Button @click="$inertia.visit('/inventory/create')" class="gap-2 bg-blue-600 text-white hover:bg-blue-700">
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

            <!-- Inventory Table -->
            <div class="rounded-md border">
                <Table>
                    <TableHeader class="bg-gray-100">
                        <TableRow>
                            <TableHead>Product Name</TableHead>
                            <TableHead>Location</TableHead>
                            <TableHead>SLoc</TableHead>
                            <TableHead>Unit</TableHead>
                            <TableHead>Size</TableHead>
                            <TableHead>Variant</TableHead>
                            <TableHead>Qty</TableHead>
                            <TableHead>Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="item in inventoryRpt" :key="`${item.id}`">
                            <TableCell>{{ item.product_name }}</TableCell>
                            <TableCell>{{ item.location_name }}</TableCell>
                            <TableCell>{{ item.sloc_name }}</TableCell>
                            <TableCell>{{ item.uom_id }}</TableCell>
                            <TableCell>{{ item.size_id }}</TableCell>
                            <TableCell>{{ item.variant }}</TableCell>
                            <TableCell>{{ item.qty }}</TableCell>
                            <TableCell>
                                <div class="flex justify-end gap-2">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        @click="
                                            handleDialogEditQty({
                                                ...item,
                                                product_id: Number(item.product_id),
                                                location_id: Number(item.location_id),
                                                qty: Number(item.qty),
                                            })
                                        "
                                    >
                                        <Edit class="h-4 w-4" />
                                    </Button>

                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        @click="handleDelete(Number(item.id))"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
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

        <!-- Edit Qty Dialog -->
       <Teleport to="body">
  <div
    v-if="isDialogEditQty"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
  >
    <div class="w-full max-w-sm rounded-2xl bg-white shadow-xl">
      <div class="border-b px-5 py-4">
        <h2 class="text-lg font-semibold text-gray-800">Edit Quantity</h2>
      </div>

      <div v-if="dialogEditQtyItem" class="space-y-3 px-5 py-4 text-sm text-gray-700">
        <div class="grid grid-cols-2 gap-2">
          <span class="font-medium">Product ID:</span>
          <span>{{ dialogEditQtyItem.product_id }}</span>

          <span class="font-medium">Product Name:</span>
          <span>{{ dialogEditQtyItem.product_name }}</span>

          <span class="font-medium">Location:</span>
          <span>{{ dialogEditQtyItem.location_name }}</span>

          <span class="font-medium">Size:</span>
          <span>{{ dialogEditQtyItem.size_id }}</span>

          <span class="font-medium">Current Qty:</span>
          <span>{{ dialogEditQtyItem.qty }}</span>
        </div>

        <div>
          <label for="newQty" class="block text-sm font-medium text-gray-700 mb-1">
            New Quantity
          </label>
          <Input
            id="newQty"
            v-model.number="newQty"
            type="number"
            min="0"
            class="w-full"
          />
        </div>
      </div>

      <div class="flex justify-end gap-2 border-t px-5 py-3">
        <Button variant="ghost" @click="isDialogEditQty = false" class="h-10">Cancel</Button>
        <Button @click="handleSaveEditQty" class="bg-blue-600 text-white hover:bg-blue-700">
          Save
        </Button>
      </div>
    </div>
  </div>
</Teleport>

    </AppLayout>
</template>
