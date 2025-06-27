<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Edit, Trash2, Plus } from 'lucide-vue-next';
import { useToast } from "@/composables/useToast";
import { useGoodReceiveStore } from '@/stores/useGoodReceiveStore';
import { storeToRefs } from 'pinia';

const toast = useToast();
const goodReceiveStore = useGoodReceiveStore();
const { items: goodReceives, total } = storeToRefs(goodReceiveStore);

const currentPage = ref(1);
const perPage = 10;

const totalDatas = computed(() => total.value || 0);
const totalPages = computed(() => Math.ceil(totalDatas.value / perPage));

const fetchPage = async (page: number) => {
  await goodReceiveStore.fetchGoodReceives(page, perPage);
  currentPage.value = page;
};

const breadcrumbs = [
  { title: 'Good Receive', href: '/good-receive' }
];

onMounted(async () => {
  await fetchPage(currentPage.value);
});

const goToPage = async (page: number) => {
  if (page < 1 || page > totalPages.value) return;
  await fetchPage(page);
};

const nextPage = async () => {
  if (currentPage.value < totalPages.value) {
    await goToPage(currentPage.value + 1);
  }
};

const prevPage = async () => {
  if (currentPage.value > 1) {
    await goToPage(currentPage.value - 1);
  }
};

const handleDelete = async (id: number) => {
  if (!confirm('Are you sure you want to delete this Good Receive?')) return;

  try {
    await goodReceiveStore.deleteGoodReceive(id);
    toast.success("Good Receive deleted successfully");
    // refresh current page after deletion
    await fetchPage(currentPage.value);
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to delete Good Receive");
  }
};
</script>

<template>
  <Head title="Good Receive Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-4">
      <div class="flex justify-between items-center mb-6">
        <Button @click="$inertia.visit('/good-receive/create')"  class="bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">
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
              <TableCell class="px-2 py-1">
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
       <!-- Pagination Controls -->
      <div class="flex justify-end mt-4 space-x-2">
        <button @click="prevPage" :disabled="currentPage === 1"
          class="px-3 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
          Previous
        </button>

        <template v-for="page in totalPages" :key="page">
          <button @click="goToPage(page)" :class="[
            'px-3 py-1 rounded border text-sm',
            page === currentPage
              ? 'bg-blue-600 border-blue-600 text-white'
              : 'border-gray-300 text-gray-700 hover:bg-gray-100'
          ]">
            {{ page }}
          </button>
        </template>

        <button @click="nextPage" :disabled="currentPage === totalPages"
          class="px-3 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
          Next
        </button>
      </div>

    </div>
  </AppLayout>
</template>



