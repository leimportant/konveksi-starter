<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { Button } from '@/components/ui/button';
// import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Edit, Trash2, Plus } from 'lucide-vue-next';
import { useToast } from "@/composables/useToast";
import { useGoodReceiveStore } from '@/stores/useGoodReceiveStore';
import { storeToRefs } from 'pinia';

const toast = useToast();
const goodReceiveStore = useGoodReceiveStore();
const { items: goodReceives, loading } = storeToRefs(goodReceiveStore);

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
        <Button @click="$inertia.visit('/good-receive/create')">
          <Plus class="h-4 w-4" />
          Add
        </Button>
      </div>

      <div class="space-y-4">
        <div v-if="loading" class="text-center py-4">
          Loading...
        </div>
        <div v-else-if="goodReceives.length === 0" class="text-center py-4">
          No records found
        </div>
        <template v-else>
          <!-- Mobile Cards -->
          <div class="md:hidden space-y-2">
            <div
              v-for="item in goodReceives"
              :key="item.id"
              class="rounded-2xl border border-gray-200 p-4 shadow-sm bg-white w-full space-y-4 transition hover:shadow-md"
            >
              <div class="flex justify-between items-center">
                <h3 class="font-semibold text-sm text-gray-900">{{ item.recipent || '-' }}</h3>
              </div>

              <div class="flex justify-between items-start gap-4">
                <div class="flex-1">
                  <p class="text-xs font-semibold text-gray-500">Material / Qty</p>
                  <div v-if="item.items?.length">
                    <div v-for="i in item.items" :key="i.id" class="text-sm text-gray-800">
                      {{ i.model_material_item }} ({{ i.qty_convert }} {{ i.uom_convert }})
                    </div>
                  </div>
                  <div v-else class="text-sm text-gray-400">-</div>
                </div>
                <div class="flex-1">
                  <p class="text-xs font-semibold text-gray-500">Created At</p>
                  <p class="text-sm text-gray-800">
                    {{ item.created_at ? new Date(item.created_at).toLocaleDateString() : '-' }}
                  </p>
                </div>
              </div>

              <div class="flex justify-end gap-2">
                <Button variant="ghost" size="icon" class="hover:bg-gray-100" @click="$inertia.visit(`/good-receive/${item.id}/edit`)">
                  <Edit class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="icon" class="hover:bg-gray-100" @click="handleDelete(item.id)">
                  <Trash2 class="h-4 w-4" />
                </Button>
              </div>
            </div>
          </div>

          <!-- Desktop Table -->
          <div class="overflow-x-auto rounded-xl border shadow-sm hidden md:block">
            <Table class="w-full text-sm text-gray-700">
              <TableHeader>
                <TableRow class="bg-gray-100">
                  <TableHead>Recipient</TableHead>
                  <TableHead>Material / Qty</TableHead>
                  <TableHead>Created At</TableHead>
                  <TableHead class="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="item in goodReceives" :key="item.id">
                  <TableCell>{{ item.recipent || '-' }}</TableCell>
                  <TableCell>
                    <template v-if="item.items?.length">
                      <div v-for="i in item.items" :key="i.id">
                        {{ i.model_material_item }} ({{ i.qty_convert }} {{ i.uom_convert }})
                      </div>
                    </template>
                    <template v-else>-</template>
                  </TableCell>
                  <TableCell>{{ item.created_at ? new Date(item.created_at).toLocaleDateString() : '-' }}</TableCell>
                  <TableCell class="text-right">
                    <div class="flex justify-end gap-2">
                      <Button variant="ghost" size="icon" class="hover:bg-gray-100" @click="$inertia.visit(`/good-receive/${item.id}/edit`)">
                        <Edit class="h-4 w-4" />
                      </Button>
                      <Button variant="ghost" size="icon" class="hover:bg-gray-100" @click="handleDelete(item.id)">
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </template>
      </div>
    </div>
  </AppLayout>
</template>

