<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { useProductionStore } from '@/stores/useProductionStore';
import { Head } from '@inertiajs/vue3';
import { Edit, LucideView, Plus, Trash2 } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { computed, onMounted, ref } from 'vue';

const toast = useToast();
const productionStore = useProductionStore();
const { productions, currentPage, lastPage, loading } = storeToRefs(productionStore);

const startDate = ref('');
const endDate = ref('');
const perPage = ref(50);
const sortField = ref('created_at');
const sortOrder = ref<'asc' | 'desc'>('desc');

const props = defineProps<{
  activity_role: string | number;
  isCreate: string;
}>();

const breadcrumbs = [{ title: 'Production', href: `/production/${props.activity_role}` }];

const totalPages = computed(() => lastPage.value || 1);

const goToPage = async (page: number) => {
  if (page < 1 || page > totalPages.value) return;

  await productionStore.fetchProductions({
    page,
    per_page: perPage.value,
    sort_field: sortField.value,
    sort_order: sortOrder.value,
    activity_role_id: props.activity_role,
    search: searchQuery.value,
    date_from: dateRange.value.from?.toISOString(),
    date_to: dateRange.value.to?.toISOString(),
  });

  currentPage.value = page;
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
    minimumFractionDigits: 0,
  }).format(value);
}

const fetchData = async (page = 1) => {
  try {
    await productionStore.fetchProductions({
      page: page ? page : currentPage.value,
      per_page: perPage.value,
      sort_field: sortField.value,
      sort_order: sortOrder.value,
      activity_role_id: props.activity_role,
      search: searchQuery.value,
      date_from: startDate.value,
      date_to: endDate.value,
    });
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? 'Failed to fetch productions');
  }
};

// 🔹 Group berdasarkan Karyawan + Model
const groupedProductions = computed(() => {
  const groups: Record<string, any> = {};
  productions.value.forEach((p) => {
    const key = `${p.employee_name || 'Unknown'} - ${p.model?.description || 'No Model'}`;
    if (!groups[key]) {
      groups[key] = {
        employee_name: p.employee_name,
        model_description: p.model?.description,
        items: [],
      };
    }
    groups[key].items.push(p);
  });
  return groups;
});

// 🔹 Hitung total qty
const grandQty = computed(() => {
  return productions.value.reduce((total, item) => {
    const itemTotal =
      item.items?.reduce((sum, i) => {
        return sum + (parseFloat(String(i.qty)) || 0);
      }, 0) || 0;
    return total + itemTotal;
  }, 0);
});

// 🔹 Hitung total harga per pcs
// const grandPricePerPcs = computed(() => {
//   return productions.value.reduce((sum, item) => {
//     const price = parseFloat(String(item.price_per_pcs).replace(/[^\d.-]/g, '')) || 0;
//     return sum + price;
//   }, 0);
// });

// 🔹 Hitung total harga keseluruhan
const grandTotal = computed(() => {
  return productions.value.reduce((sum, item) => {
    const cleanString = String(item.total_price).replace(/[^\d]/g, '');
    const numericValue = parseFloat(cleanString) || 0;
    return sum + numericValue;
  }, 0);
});

onMounted(() => {
  const today = new Date();
  const sixDaysAgo = new Date();
  sixDaysAgo.setDate(today.getDate() - 6);

  startDate.value = sixDaysAgo.toISOString().split('T')[0];
  endDate.value = today.toISOString().split('T')[0];

  fetchData();
});

const handleDelete = async (id: string) => {
  if (!confirm('Are you sure you want to delete this production?')) return;

  try {
    await productionStore.deleteProduction(id);
    toast.success('Production deleted successfully');
    await fetchData(1);
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
      <div class="flex flex-col gap-3">
        <div class="flex-1 flex flex-col min-w-0">
          <div class="flex items-center gap-3">
          </div>

          <div class="flex gap-3">
            <div class="flex-1 flex flex-col min-w-0">
              <label class="text-xs font-medium text-gray-600 mb-1">Start Date</label>
              <input type="date" v-model="startDate"
                class="w-full max-w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
            </div>

            <div class="flex-1 flex flex-col min-w-0">
              <label class="text-xs font-medium text-gray-600 mb-1">End Date</label>
              <input type="date" v-model="endDate"
                class="w-full max-w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
            </div>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
          <div class="flex flex-1 gap-2 items-center">

            <Button v-if="props.isCreate === 'Y'" @click="$inertia.visit(`/production/${props.activity_role}/create`)"
              class="flex items-center gap-2 rounded-md bg-indigo-600 h-10 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 flex-shrink-0">
              <Plus class="h-4 w-4" />
              Tambah Data
            </Button>

            <Input v-model="searchQuery" placeholder="Search..."
              class="flex-1 rounded-md border border-gray-300 px-3 h-10 py-1 text-sm shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 min-w-0"
              @keyup.enter="fetchData(1)" />

            <Button @click="fetchData(1)"
              class="flex-shrink-0 rounded-md bg-green-600 px-3 h-10 py-1.5 text-sm font-medium text-white hover:bg-green-700 focus:ring-2 focus:ring-green-500">
              Cari
            </Button>
          </div>
        </div>
      </div>




      <!-- Data Table -->
      <div v-if="loading" class="py-4 text-center text-sm text-gray-500">Loading...</div>

      <div v-else class="space-y-2">
        <div class="overflow-x-auto rounded-md border border-gray-200 dark:border-gray-700 mt-2">
          <table class="min-w-full table-auto text-left text-sm text-gray-800 dark:text-gray-100">
            <thead
              class="bg-gray-100 text-xs font-semibold uppercase text-gray-600 dark:bg-gray-800 dark:text-gray-300">
              <tr>
                <th class="px-3 py-2">Tanggal</th>
                <th class="px-3 py-2">Size/Qty</th>
                <th class="px-3 py-2">Price/PCS</th>
                <th class="px-3 py-2">Total</th>
                <th class="px-3 py-2 text-right">Actions</th>
              </tr>
            </thead>

            <tbody>
              <template v-for="(group, groupKey) in groupedProductions" :key="groupKey">
                <!-- Header per grup -->
                <tr class="bg-gray-200 dark:bg-gray-700 font-semibold">
                  <td colspan="8" class="px-3 py-2">
                    👤 {{ group.employee_name || '-' }} — 🧵 {{ group.model_description || '-' }}
                  </td>
                </tr>

                <!-- Detail per grup -->
                <tr v-for="item in group.items" :key="item.id"
                  class="border-b border-gray-100 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">
                  <td class="whitespace-nowrap px-3 py-2 align-top">
                    {{ item.created_at ? new Date(item.created_at).toLocaleDateString('id-ID') : '-' }} <br />
                    {{ item.activity_role?.name || '-' }}
                  </td>

                  <td class="whitespace-nowrap px-3 py-2 align-top">
                    <div v-if="item.items?.length"
                      class="flex flex-col gap-1 rounded-lg border border-gray-200 bg-gray-50 p-1 text-[11px] text-gray-700">

                      <!-- Loop over items -->
                      <div v-for="(size, index) in item.items" :key="index"
                        class="flex items-center justify-between rounded-md bg-white px-1.5 py-0.5 shadow-sm">
                        <span>{{ size.size_id }} - {{ size.variant }}</span>
                        <span class="font-semibold">{{ size.qty }}</span>
                      </div>

                      <!-- Add total if more than 1 item -->
                      <div v-if="item.items.length > 1"
                        class="flex items-center justify-between rounded-md bg-white px-1.5 py-0.5 shadow-sm order-t border-gray-300 px-1.5 pt-0.5 mt-0.5 font-semibold text-[11px] text-gray-800">
                        <span>Total</span>
                        <span>{{item.items.reduce((sum: number, s: any) => sum + Number(s.qty || 0), 0)}}</span>
                      </div>

                    </div>

                    <span v-else class="text-xs italic text-gray-400">-</span>
                  </td>


                  <td class="truncate px-3 py-2 align-top">
                    {{ formatRupiah(item?.price_per_pcs || 0) }}
                  </td>

                  <td class="max-w-[80px] truncate px-3 py-2 align-top">
                    {{ item?.total_price || 0 }}
                  </td>

                  <td class="px-3 py-2 align-top">
                    <div class="flex justify-end gap-1 sm:gap-2">
                      <Button v-if="props.isCreate === 'Y' && (item.status === 1 || item.status === 3)" variant="ghost" size="icon"
                        class="hover:bg-gray-100 dark:hover:bg-gray-700"
                        @click="$inertia.visit(`/production/${item.activity_role_id}/edit/${item.id}`)">
                        <Edit class="h-4 w-4" />
                      </Button>
                      <Button v-if="item.status === 1 || item.status === 3" variant="ghost" size="icon"
                        class="hover:bg-gray-100 dark:hover:bg-gray-700" @click="handleDelete(item.id)">
                        <Trash2 class="h-4 w-4" />
                      </Button>
                      <Button variant="ghost" size="icon" class="hover:bg-gray-100 dark:hover:bg-gray-700"
                        @click="$inertia.visit(`/production/${item.activity_role_id}/view/${item.id}`)">
                        <LucideView class="h-4 w-4" />
                      </Button>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>

            <tfoot>
              <tr class="bg-gray-50 font-semibold dark:bg-gray-800">
                <td class="px-3 py-2 text-right"></td>
                <td class="px-3 py-2 text-right">Total &nbsp;:&nbsp; {{ grandQty }}</td>
                <td class="px-3 py-2"></td>
                <td class="px-3 py-2">{{ formatRupiah(grandTotal) }}</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex justify-end space-x-2">
          <button @click="prevPage" :disabled="currentPage === 1 || loading"
            class="rounded border border-gray-300 px-3 py-1 text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50">
            Previous
          </button>

          <template v-for="page in totalPages" :key="page">
            <button @click="goToPage(page)" :class="[
              'rounded border px-3 py-1 text-sm',
              page === currentPage
                ? 'border-blue-600 bg-blue-600 text-white'
                : 'border-gray-300 text-gray-700 hover:bg-gray-100',
            ]" :disabled="loading">
              {{ page }}
            </button>
          </template>

          <button @click="nextPage" :disabled="currentPage === totalPages || loading"
            class="rounded border border-gray-300 px-3 py-1 text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50">
            Next
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
