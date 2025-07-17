<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Plus, View } from 'lucide-vue-next';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useStockOpnameStore } from '@/stores/useStockOpnameStore';
import { storeToRefs } from 'pinia';

const breadcrumbs = [{ title: 'Stock Opname', href: '/stock-opnames' }];
const OpnameStore = useStockOpnameStore();
const { items: opnames } = storeToRefs(OpnameStore);

onMounted(() => {
  OpnameStore.fetchOpnames();
});
</script>

<template>
   <Head title="Stock Opname Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-4">
      <div class="flex justify-between items-center gap-2 mb-2">
        <Button @click="$inertia.visit(`/stock-opnames/create`)" class="bg-indigo-600 text-white py-2 h-10 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">
          <Plus class="h-4 w-4" />
          Add
        </Button>
      </div>

      <div class="rounded-md border">
       <Table>
          <TableHeader>
            <TableRow class="bg-gray-100">
              <TableHead>Location</TableHead>
              <TableHead>Product</TableHead>
              <TableHead>Sloc ID</TableHead>
              <TableHead>Remark</TableHead>
              <TableHead>Action</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="op in opnames" :key="op.id">
              <TableCell>{{ op.location?.name }}</TableCell>
              <TableCell>{{ op.product?.name }}</TableCell>
              <TableCell>{{ op.sloc_id }}</TableCell>
              <TableCell>{{ op.remark }}</TableCell>
              <TableCell>
                <Button variant="ghost" size="icon" @click="$inertia.visit(`/stock-opnames/${String(op.id)}/view`)">
                 <View class="h-4 w-4"/>
                </Button>
                
              </TableCell>

            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>
  </AppLayout>
</template>
