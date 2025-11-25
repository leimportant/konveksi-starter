<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useTransferStockStore } from '@/stores/useTransferStockStore';
import { useToast } from '@/composables/useToast';
import { Button } from '@/components/ui/button';
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow
} from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { useTransferActions } from '@/composables/useTransferActions';

const props = defineProps<{ id: string }>();

const breadcrumbs = [{ title: 'Transfer Stock', href: '/transfer-stock' }];

const store = useTransferStockStore();
const toast = useToast();
const { acceptTransfer, rejectTransfer, loading } = useTransferActions();

// Modal states
const showConfirmAccept = ref(false);
const showConfirmReject = ref(false);

onMounted(async () => {
  try {
    await store.fetchTransferByID(props.id);
    await store.fetchProducts();
  } catch (error) {
    console.error(error);
    toast.error('Failed to load transfer details');
  }
});

// const totalQty = () => {
//   const details = store.transfer?.transfer_detail ?? [];
//   return details.reduce((sum: number, item: any) => {
//     const q = Number(item.qty ?? 0);
//     return sum + (Number.isNaN(q) ? 0 : q);
//   }, 0);
// };

// group ke berdasarkan nama produk
const groupByProduct = (items: any[]) => {
  return items.reduce((acc: any, cur: any) => {
    const productName = cur.product?.name ?? 'Unknown Product';
    acc[productName] = acc[productName] || [];
    acc[productName].push(cur);
    return acc;
  }, {});
};

// total qty per product helper to avoid implicit any in template reduce
const productTotal = (group: any[]): number => {
  return group.reduce((s: number, x: any) => s + Number(x.qty ?? 0), 0);
};

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
              <TableHead>Lokasi Asal</TableHead>
              <TableHead>Lokasi Tujuan</TableHead>
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

         <!-- Warning -->
        <div class="bg-blue-50 border border-blue-200 text-blue-700 p-3 rounded text-sm">
          ⚠ Harap periksa kembali *Qty, Size, Variant, dan Produk* sebelum melakukan penerimaan barang.
        </div>

        <!-- Details Table -->
        <div class="overflow-x-auto rounded-md border border-gray-200 dark:border-gray-700">
          <Table class="min-w-full table-auto text-left text-sm text-gray-800 dark:text-gray-100">
            <TableHeader>
              <TableRow>
                <TableHead class="w-1/3">Produk</TableHead>
                <TableHead>Size/Qty</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              <template v-for="(group, productName) in groupByProduct(store.transfer.transfer_detail)"
                :key="productName">
                <TableRow>
                  <!-- Produk -->
                  <TableCell class="font-semibold align-top">
                    {{ productName }}
                  </TableCell>

                  <!-- Size / Variant / Qty List -->
                  <TableCell>
                    <div
                      class="flex flex-col gap-1 border border-gray-200 rounded bg-gray-50 p-2 text-[11px] sm:text-xs text-gray-800">
                      <div v-for="(it, idx) in group" :key="idx"
                        class="flex justify-between bg-white px-2 py-1 rounded shadow-sm">
                        <span>{{ it.size_id }} - {{ it.variant }}</span>
                        <span class="font-semibold">{{ it.qty }}</span>
                      </div>

                      <!-- Total per produk -->
                      <div v-if="group.length > 1"
                        class="flex justify-between bg-white px-2 py-1 rounded shadow-sm border-t font-semibold mt-1 text-gray-900">
                        <span>Total</span>
                        <span>{{ productTotal(group) }}</span>
                      </div>
                    </div>
                  </TableCell>
                 
                </TableRow>
              </template>
            </TableBody>
          </Table>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-between gap-4">
          <Button variant="outline" @click="router.visit('/transfer-stock')">
            Kembali
          </Button>

          <div v-if="store.transfer.status === 'Pending'" class="flex gap-2">
            <Button variant="default" @click="showConfirmAccept = true" :disabled="loading">
              Accept
            </Button>
            <Button variant="destructive" @click="showConfirmReject = true" :disabled="loading">
              Reject
            </Button>
          </div>
        </div>
      </div>

      <div v-else-if="loading" class="text-center py-8">
        Loading...
      </div>
    </div>

    <!-- Modal Confirm Accept -->
    <Dialog v-model:open="showConfirmAccept">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Konfirmasi Penerimaan</DialogTitle>
          <DialogDescription>
            Pastikan seluruh barang sudah sesuai dengan fisik di gudang.
            Apakah Anda yakin ingin <strong>menerima</strong> barang ini?
          </DialogDescription>
        </DialogHeader>
        <div class="flex justify-end gap-2 mt-4">
          <Button variant="outline" @click="showConfirmAccept = false">Batal</Button>
          <Button variant="default" @click="() => { acceptTransfer(props.id); showConfirmAccept = false }">
            Ya, Terima
          </Button>
        </div>
      </DialogContent>
    </Dialog>

    <!-- Modal Confirm Reject -->
    <Dialog v-model:open="showConfirmReject">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Konfirmasi Penolakan</DialogTitle>
          <DialogDescription>
            Apakah Anda yakin ingin <strong>menolak</strong> barang ini?
            Tindakan ini tidak dapat dibatalkan.
          </DialogDescription>
        </DialogHeader>
        <div class="flex justify-end gap-2 mt-4">
          <Button variant="outline" @click="showConfirmReject = false">Batal</Button>
          <Button variant="destructive" @click="() => { rejectTransfer(props.id); showConfirmReject = false }">
            Ya, Tolak
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
