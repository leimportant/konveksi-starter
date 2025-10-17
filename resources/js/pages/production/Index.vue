<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
// import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { useProductionStore } from '@/stores/useProductionStore';
import { Head } from '@inertiajs/vue3';
import { Edit, LucideView, Plus, Trash2 } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { onMounted, ref, computed } from 'vue';

const toast = useToast();
const productionStore = useProductionStore();
const { productions, currentPage, lastPage, loading } = storeToRefs(productionStore);


const perPage = ref(10);
const sortField = ref('created_at');
const sortOrder = ref<'asc' | 'desc'>('desc');

const props = defineProps<{
    activity_role: string | number;
}>();

const breadcrumbs = [{ title: 'Production', href: `/production/${props.activity_role}` }];


const totalPages = computed(() => lastPage.value || 1);

const goToPage = async (page: number) => {
  if (page < 1 || page > totalPages.value) return;
  await productionStore.fetchProductions({
    page,
    per_page: perPage.value,
    sort_field: sortField.value,
})
};
const nextPage = () => goToPage(currentPage.value + 1);
const prevPage = () => goToPage(currentPage.value - 1);

const searchQuery = ref('');
interface DateRange {
    from: Date | null;
    to: Date | null;
}

const dateRange = ref<DateRange>({
    from: null,
    to: null,
});

function formatRupiah(value: number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(value);
}


const fetchData = async () => {
    try {
        await productionStore.fetchProductions({
            page: currentPage.value,
            per_page: perPage.value,
            sort_field: sortField.value,
            sort_order: sortOrder.value,
            activity_role_id: props.activity_role,
            search: searchQuery.value,
            date_from: dateRange.value.from?.toISOString(),
            date_to: dateRange.value.to?.toISOString(),
        });
    } catch (error: any) {
        toast.error(error?.response?.data?.message ?? 'Failed to fetch productions');
    }
};

onMounted(fetchData);

const handleDelete = async (id: string) => {
    if (!confirm('Are you sure you want to delete this production?')) return;

    try {
        await productionStore.deleteProduction(id);
        toast.success('Production deleted successfully');
        await fetchData();
    } catch (error: any) {
        toast.error(error?.response?.data?.message ?? 'Failed to delete production');
    }
};
</script>

<template>
  <Head title="Production Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-3 py-4">

      <!-- Top Bar -->
      <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex flex-1 gap-2">
          <Button
            @click="$inertia.visit(`/production/${props.activity_role}/create`)"
            class="flex-shrink-0 flex items-center gap-2 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500"
          >
            <Plus class="h-4 w-4" />
            Tambah Data
          </Button>
          <Input
            v-model="searchQuery"
            placeholder="Search..."
            class="flex-1 text-sm"
            @keyup.enter="fetchData"
          />
        </div>
      </div>

      <!-- Data Table Section -->
      <div v-if="loading" class="py-4 text-center text-sm text-gray-500">Loading...</div>

      <div v-else class="space-y-2">
        <div class="overflow-x-auto rounded-md border border-gray-200 dark:border-gray-700">
          <table class="min-w-full table-auto text-sm text-left text-gray-800 dark:text-gray-100">
            <thead class="bg-gray-100 dark:bg-gray-800 text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
              <tr>
                <th class="px-3 py-2">Model</th>
                <th class="px-3 py-2">Activity</th>
                <th class="px-3 py-2">Size/Qty</th>
                <th class="px-3 py-2">Price/PCS</th>
                <th class="px-3 py-2">Total</th>
                <th class="px-3 py-2">Created At</th>
                <th class="px-3 py-2 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in productions"
                :key="item.id"
                class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
              >
                <td class="px-3 py-2 max-w-[160px] truncate">
                  {{ item.model?.description || '-' }}
                </td>
                <td class="px-3 py-2 truncate">
                  {{ item.activity_role?.name || '-' }}
                </td>
                <td class="px-3 py-2 whitespace-nowrap">
                  <div v-if="item.items?.length">
                    <div v-for="i in item.items" :key="i.id">
                      {{ i.size_id }}: {{ i.qty }}
                    </div>
                  </div>
                  <div v-else>-</div>
                </td>
                <td class="px-3 py-2 max-w-[160px] truncate">
                  {{ formatRupiah(item?.price_per_pcs || 0) }}
                </td>
                <td class="px-3 py-2 max-w-[160px] truncate">
                  {{ item?.total_price || 0 }}
                </td>
                <td class="px-3 py-2 whitespace-nowrap">
                  {{ item.created_at ? new Date(item.created_at).toLocaleDateString() : '-' }}
                </td>
                <td class="px-3 py-2 text-right">
                  <div class="flex justify-end gap-1 sm:gap-2">
                    <Button
                      v-if="item.status === 1 || item.status === 3"
                      variant="ghost"
                      size="icon"
                      class="hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$inertia.visit(`/production/${item.activity_role_id}/edit/${item.id}`)"
                    >
                      <Edit class="h-4 w-4" />
                    </Button>
                    <Button
                      v-if="item.status === 1 || item.status === 3"
                      variant="ghost"
                      size="icon"
                      class="hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="handleDelete(item.id)"
                    >
                      <Trash2 class="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      class="hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$inertia.visit(`/production/${item.activity_role_id}/view/${item.id}`)"
                    >
                      <LucideView class="h-4 w-4" />
                    </Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

              <!-- Pagination -->
      <div class="flex justify-end mt-4 space-x-2">
        <button
          @click="prevPage"
          :disabled="currentPage === 1 || loading"
          class="px-3 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Previous
        </button>

        <template v-for="page in totalPages" :key="page">
          <button
            @click="goToPage(page)"
            :class="[
              'px-3 py-1 rounded border text-sm',
              page === currentPage ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-100'
            ]"
            :disabled="loading"
          >
            {{ page }}
          </button>
        </template>

        <button
          @click="nextPage"
          :disabled="currentPage === totalPages || loading"
          class="px-3 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Next
        </button>
      </div>
      </div>
    </div>
  </AppLayout>
</template>

