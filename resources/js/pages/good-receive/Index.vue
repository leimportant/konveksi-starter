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
const { items: goodReceives, total, loading, filterName } = storeToRefs(goodReceiveStore);

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

// Filter handling — cukup panggil setFilter di store
let filterTimeout: ReturnType<typeof setTimeout> | null = null;

const setFilter = (field: string, event: Event) => {
  const target = event.target as HTMLInputElement;
  if (filterTimeout) {
    clearTimeout(filterTimeout);
  }
  filterTimeout = setTimeout(() => {
    goodReceiveStore.setFilter(field, target.value);
  }, 300); // Debounce for 300ms
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
      <!-- Header & Add Button -->
      <div class="flex justify-between items-center gap-2 mb-2">
        <Button  @click="$inertia.visit('/good-receive/create')" aria-label="Tambah Kategori Baru" :disabled="loading" class="bg-indigo-600 text-white py-2 h-10 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">
          <Plus class="h-4 w-4" />
          Add
        </Button>

        <Input
          :value="filterName"
          placeholder="Search data"
          @input="setFilter('name', $event)"
          class="w-64"
          aria-label="Search data"
          :disabled="loading"
        />
      </div>

      <!-- Table -->
      <div class="overflow-x-auto rounded-lg border">
        <Table class="min-w-[600px] text-sm">
          <TableHeader>
            <TableRow class="bg-gray-100 text-gray-700">
              <TableHead class="px-4 py-2 whitespace-nowrap">Recipient</TableHead>
              <TableHead class="px-4 py-2 whitespace-nowrap">Material / Qty</TableHead>
              <TableHead class="px-4 py-2 whitespace-nowrap">Created At</TableHead>
              <TableHead class="px-4 py-2 text-center w-24">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow
              v-for="item in goodReceives"
              :key="item.id"
              class="hover:bg-gray-50 transition-colors"
            >
              <TableCell class="px-4 py-2">{{ item.recipent || '-' }}</TableCell>
              <TableCell class="px-4 py-2">
                <template v-if="item.items?.length">
                  <div
                    v-for="i in item.items"
                    :key="i.id"
                    class="text-gray-700"
                  >
                    {{ item.model?.description }} ({{ i.qty_convert }} {{ i.uom_convert }})
                  </div>
                </template>
                <template v-else>-</template>
              </TableCell>
              <TableCell class="px-4 py-2 text-gray-600">
                {{ item.created_at ? new Date(item.created_at).toLocaleDateString('id-ID') : '-' }}
              </TableCell>
              <TableCell class="px-4 py-2 text-center">
                <div class="flex justify-center gap-2">
                  <Button
                    variant="ghost"
                    size="icon"
                    class="hover:bg-gray-100"
                    @click="$inertia.visit(`/good-receive/${item.id}/edit`)"
                  >
                    <Edit class="h-4 w-4 text-gray-600" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    class="hover:bg-gray-100"
                    @click="handleDelete(item.id)"
                  >
                    <Trash2 class="h-4 w-4 text-red-500" />
                  </Button>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <!-- Pagination Controls -->
      <div class="flex flex-wrap justify-end items-center gap-2 pt-4">
        <button
          @click="prevPage"
          :disabled="currentPage === 1"
          class="px-3 py-1 rounded border text-sm border-gray-300 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Previous
        </button>

        <template v-for="page in totalPages" :key="page">
          <button
            @click="goToPage(page)"
            :class="[
              'px-3 py-1 rounded border text-sm',
              page === currentPage
                ? 'bg-indigo-600 text-white border-indigo-600'
                : 'border-gray-300 text-gray-700 hover:bg-gray-100'
            ]"
          >
            {{ page }}
          </button>
        </template>

        <button
          @click="nextPage"
          :disabled="currentPage === totalPages"
          class="px-3 py-1 rounded border text-sm border-gray-300 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Next
        </button>
      </div>
    </div>
  </AppLayout>
</template>




