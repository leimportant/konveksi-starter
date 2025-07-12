<script setup lang="ts">
import { onMounted, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { usePurchaseOrder } from '@/stores/usePurchaseOrder';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';

const { props } = usePage();
const { purchaseOrder, fetchPurchaseOrder } = usePurchaseOrder();


onMounted(() => {
    fetchPurchaseOrder(props.purchaseOrder.id);
});

const grandTotal = computed(() => {
    return purchaseOrder && purchaseOrder.items ? purchaseOrder.items.reduce((sum, item) => sum + item.total, 0) : 0;
});
</script>

<template>
    <Head title="Purchase Order Details" />
    <AppLayout title="Purchase Order Details">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Purchase Order Details
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Purchase Order Information</h3>
                        <p><strong>Nota Number:</strong> {{ purchaseOrder?.nota_number }}</p>
                        <p><strong>Date:</strong> {{ purchaseOrder?.date }}</p>
                        <p><strong>Supplier:</strong> {{ purchaseOrder?.supplier }}</p>
                        <p><strong>Status:</strong> {{ purchaseOrder?.status }}</p>
                    </div>

                    <h3 class="text-lg font-medium text-gray-900 mb-4">Items</h3>
                    <div class="rounded-md border mb-4">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Product</TableHead>
                                    <TableHead>Qty</TableHead>
                                    <TableHead>UOM</TableHead>
                                    <TableHead>Price</TableHead>
                                    <TableHead>Total</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="item in purchaseOrder?.items" :key="item.id">
                                    <TableCell>{{ item.product?.name }}</TableCell>
                                    <TableCell>{{ item.qty }}</TableCell>
                                    <TableCell>{{ item.uom_id }}</TableCell>
                                    <TableCell>{{ item.price.toLocaleString('id-ID', { style: 'currency', currency: 'IDR' }) }}</TableCell>
                                    <TableCell>{{ item.total.toLocaleString('id-ID', { style: 'currency', currency: 'IDR' }) }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <div class="text-right mb-6">
                        <h3 class="text-xl font-bold text-gray-900">Grand Total: {{ grandTotal.toLocaleString('id-ID', { style: 'currency', currency: 'IDR' }) }}</h3>
                    </div>

                    <div class="flex justify-end">
                        <Link :href="route('purchase-order.index')">
                            <Button variant="outline">
                                Back to List
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>