<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useTransferStockStore } from '@/stores/useTransferStockStore';
import { useToast } from '@/composables/useToast';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useTransferActions } from '@/composables/useTransferActions'; // Import the composable

const props = defineProps<{
  id: string;
}>();

const breadcrumbs = [{ title: 'Transfer Stock', href: '/transfer-stock' }];

const store = useTransferStockStore();
const toast = useToast();
const { acceptTransfer, rejectTransfer, loading } = useTransferActions(); // Use the composable

const getProductName = (product_id: number | null) => {
  const prod = store.products.find(p => p.id === product_id)
  return prod ? prod.name : ''
}

onMounted(async () => {
  try {
    await store.fetchTransferByID(props.id);
    await store.fetchProducts();
  } catch (error) {
    console.error(error);
    // Handle the error, e.g., show an error message t to the user
    toast.error('Failed to load transfer details');
  }
});

// Removed acceptTransfer and rejectTransfer methods as they are now in the composable

</script>

<template>

  <Head title="Transfer Stock Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-6">
      <div v-if="store.transfer" class="space-y-6">
        <!-- Header Info -->
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>From</TableHead>
              <TableHead>Transfer To</TableHead>
              <TableHead>Sloc</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow>
              <TableCell>{{ store.transfer.location?.name }}</TableCell>
              <TableCell>{{ store.transfer.location_destination?.name }}</TableCell>
              <TableCell>{{ store.transfer.sloc?.name }}</TableCell>
            </TableRow>
          </TableBody>
        </Table>

        <!-- Details Table -->
        <div class="border rounded p-4">
          <div class="font-semibold text-lg mb-2">Detail Barang</div>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Produk</TableHead>
                <TableHead>Ukuran</TableHead>
                <TableHead>UOM</TableHead>
                <TableHead class="text-right">Qty</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in store.transfer.transfer_detail" :key="item.id">
                <TableCell>{{ getProductName(item.product_id) }}</TableCell>
                <TableCell>{{ item.size_id }}</TableCell>
                <TableCell>{{ item.uom_id }}</TableCell>
                <TableCell class="text-right">{{ item.qty }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>



        <div class="flex items-center justify-between gap-4">
          <!-- Tombol Kembali di kiri -->
          <Button variant="outline" @click="router.visit('/transfer-stock')">
            Kembali
          </Button>

          <!-- Tombol Accept dan Reject di kanan -->
          <div v-if="store.transfer.status === 'Pending'" class="flex gap-2">
            <Button variant="default" @click="() => acceptTransfer(props.id)" :disabled="loading">
              Accept
            </Button>
            <Button variant="destructive" @click="() => rejectTransfer(props.id)" :disabled="loading">
              Reject
            </Button>
          </div>
        </div>

      </div>

      <!-- Loading State -->
      <div v-else-if="loading" class="text-center py-8">
        Loading...
      </div>
    </div>
  </AppLayout>
</template>