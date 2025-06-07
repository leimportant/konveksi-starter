<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { useTransferStockStore } from '@/stores/useTransferStockStore';
import { storeToRefs } from 'pinia';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Trash2, Plus, Edit, Eye } from 'lucide-vue-next';
import { useToast } from '@/composables/useToast';

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

const breadcrumbs = [
  { title: 'Transfer Stocks', href: '/transfer-stocks' },
];
</script>

<template>
  <Head title="Transfer Stocks" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="flex justify-between items-center mb-6">
        <Button @click="$inertia.visit(`/transfer-stock/create`)">
          <Plus class="h-4 w-4 mr-2" />
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
                  class="px-2 py-1 rounded-full text-xs font-semibold"
                  >
                  {{ transfer.status }}
                </span>
              </TableCell>
              <TableCell class="flex gap-2">

                <Button  v-if="transfer?.id && (transfer.status === 'Accepted' || transfer.status === 'Rejected')" variant="ghost" size="icon" class="hover:bg-gray-100" @click="$inertia.visit(`/transfer-stock/${transfer.id}/view`)">
                      <Eye class="h-4 w-4" />
                    </Button>
                    <Button v-if="transfer?.id && (transfer.status === 'Pending' || transfer.status === 'Rejected')" variant="ghost" size="icon" class="hover:bg-gray-100" @click="$inertia.visit(`/transfer-stock/${transfer.id}/edit`)">
                      <Edit class="h-4 w-4" />
                    </Button>
                    <Button v-if="transfer?.id && (transfer.status === 'Pending' || transfer.status === 'Rejected')" variant="ghost" size="icon" class="hover:bg-gray-100" @click="handleDelete(transfer.id)">
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
