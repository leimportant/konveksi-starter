<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Edit, Trash2, Plus } from 'lucide-vue-next';
import { useToast } from "@/composables/useToast";
import { useGoodReceiveStore } from '@/stores/useGoodReceiveStore';
import { storeToRefs } from 'pinia';

const toast = useToast();
const goodReceiveStore = useGoodReceiveStore();
const { items: goodReceives } = storeToRefs(goodReceiveStore);

const breadcrumbs = [
  { title: 'Good Receive', href: '/good-receive' }
];

onMounted(async () => {
  try {
    await goodReceiveStore.fetchGoodReceives();
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to fetch Good Receives");
  }
});

const handleDelete = async (id: number) => {
  if (!confirm('Are you sure you want to delete this Good Receive?')) return;

  try {
    await goodReceiveStore.deleteGoodReceive(id);
    toast.success("Good Receive deleted successfully");
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to delete Good Receive");
  }
};
</script>
<template>
  <Head title="Good Receive Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="flex justify-between items-center mb-6">
        <Button @click="$inertia.visit('/good-receive/create')" class="flex items-center gap-2">
          <Plus class="h-4 w-4" />
          Add
        </Button>
      </div>

      <div class="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow class="bg-gray-100">
              <TableHead class="px-4 py-3">Recipient</TableHead>
              <TableHead class="px-4 py-3">Material / Qty</TableHead>
              <TableHead class="px-4 py-3">Created At</TableHead>
              <TableHead class="w-24">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="item in goodReceives" :key="item.id">
              <TableCell>{{ item.recipent || '-' }}</TableCell>
              <TableCell class="px-4 py-2">
                  <template v-if="item.items?.length">
                    <div v-for="i in item.items" :key="i.id">
                      {{ item.model?.description }} ({{ i.qty_convert }} {{ i.uom_convert }})
                    </div>
                  </template>
                  <template v-else>-</template>
                </TableCell>
                <TableCell class="px-4 py-2">{{ item.created_at ? new Date(item.created_at).toLocaleDateString() : '-' }}</TableCell>
              <TableCell class="flex gap-2">
                <Button variant="ghost" size="icon" class="hover:bg-gray-100" @click="$inertia.visit(`/good-receive/${item.id}/edit`)">
                      <Edit class="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="icon" class="hover:bg-gray-100" @click="handleDelete(item.id)">
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



