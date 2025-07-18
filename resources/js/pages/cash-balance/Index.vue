<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Edit } from 'lucide-vue-next';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { useCashBalanceStore } from '@/stores/useCashBalanceStore';
import debounce from 'lodash-es/debounce';

const store = useCashBalanceStore();
const search = ref('');

const breadcrumbs = [
  { title: 'Cash Balance', href: '/cash-balances' }
];

onMounted(() => {
  store.fetchCashBalances();
});

const debouncedSearch = debounce((value: string) => {
  store.setFilter('name', value);
}, 400);

const handleSearch = (e: Event) => {
  const target = e.target as HTMLInputElement;
  debouncedSearch(target.value);
};

const nextPage = () => {
  if (store.currentPage < store.lastPage) {
    store.fetchCashBalances(store.currentPage + 1);
  }
};

const prevPage = () => {
  if (store.currentPage > 1) {
    store.fetchCashBalances(store.currentPage - 1);
  }
};
</script>

<template>
  <Head title="Cash Balances" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6 space-y-6">
      <div class="flex justify-between items-center">
        <h1 class="text-2xl font-semibold">Saldo Kas</h1>
        <Input
          v-model="search"
          @input="handleSearch"
          type="text"
          placeholder="Search by cashier name..."
          class="w-64"
        />
      </div>

      <div v-if="store.loading" class="text-center py-6 text-gray-500">
        Loading data...
      </div>

      <div v-else>
        <div class="rounded-md border">
          <Table>
            <TableHeader>
              <TableRow class="bg-gray-100">
                <TableHead>#</TableHead>
                <TableHead>Cashier ID</TableHead>
                <TableHead>Shift Date</TableHead>
                <TableHead>Shift</TableHead>
                <TableHead>Opening</TableHead>
                <TableHead>Closing</TableHead>
                <TableHead>Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="(item, index) in store.items" :key="item.id">
                <TableCell>{{ index + 1 }}</TableCell>
                <TableCell>{{ item.cashier_id }}</TableCell>
                <TableCell>{{ item.shift_date }}</TableCell>
                <TableCell>{{ item.shift_number }}</TableCell>
                <TableCell>Rp {{ item.opening_balance.toLocaleString() }}</TableCell>
                <TableCell>
                  <span v-if="item.closing_balance !== null">
                    Rp {{ item.closing_balance.toLocaleString() }}
                  </span>
                  <span v-else class="italic text-gray-400">
                     <Button
                    variant="ghost"
                    size="icon"
                    class="hover:bg-gray-100"
                    @click="$inertia.visit(`/cash-balances/${item.id}/closing`)"
                  >
                    <Edit class="h-4 w-4 text-gray-600" />
                  </Button>
                  </span>
                </TableCell>
                <TableCell>
                  <span
                    :class="item.status === 'open' ? 'text-green-600' : 'text-gray-600'"
                  >
                    {{ item.status }}
                  </span>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <!-- Pagination -->
        <div class="flex justify-end mt-4 gap-2">
          <Button
            @click="prevPage"
            :disabled="store.currentPage === 1"
            variant="outline"
          >
            Prev
          </Button>
          <span class="px-2 py-1">Page {{ store.currentPage }} of {{ store.lastPage }}</span>
          <Button
            @click="nextPage"
            :disabled="store.currentPage === store.lastPage"
            variant="outline"
          >
            Next
          </Button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
