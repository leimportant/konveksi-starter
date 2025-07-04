<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { useReportStore } from '@/stores/useReportStore';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const breadcrumbs = [
  { title: 'Report Production Summary', href: '/reports/production-summary' },
];

const reportStore = useReportStore();

const startDate = ref('');
const endDate = ref('');
const searchKey = ref('');

const fetchReport = () => {
  reportStore.fetchProductionDetail(startDate.value, endDate.value, searchKey.value);
};

const formatRupiah = (value: number): string => {
  if (typeof value !== 'number' || isNaN(value)) return 'Rp 0,00';
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 2,
  }).format(value);
};

const allActivityRoles = computed(() => {
  const roleMap = new Map<number, string>();
  reportStore.productionSummary.forEach((item) => {
    if (item.activities) {
      for (const [roleId, value] of Object.entries(item.activities)) {
        const id = Number(roleId);
        if (!roleMap.has(id)) {
          roleMap.set(id, value.name || `Role ${id}`);
        }
      }
    }
  });
  return Array.from(roleMap.entries()).map(([id, name]) => ({ id, name }));
});

onMounted(() => {
  const today = new Date();
  const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
  startDate.value = firstDay.toISOString().split('T')[0];
  endDate.value = today.toISOString().split('T')[0];
  fetchReport();
});
</script>

<template>
  <Head title="Production Detail Report" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">Production Detail Report</h2>
    </template>

    <div class="py-6">
      <div class="mx-auto max-w-7xl sm:px-2 lg:px-2">
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
          <!-- Filters -->
          <div class="mb-4 flex space-x-4">
            <Input type="date" v-model="startDate" />
            <Input type="date" v-model="endDate" />
            <Input type="text" v-model="searchKey" placeholder="Search by product name" />
            <Button
              @click="fetchReport"
              class="bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500"
            >
              Tampilkan
            </Button>
          </div>

          <!-- Table Content -->
          <div v-if="reportStore.loading">Loading...</div>
          <div v-else-if="reportStore.error" class="text-red-500">Error: {{ reportStore.error.message }}</div>
          <div v-else>
            <Table>
              <!-- Table Header -->
              <TableHeader>
                <TableRow class="bg-gray-100">
                  <TableHead>ID</TableHead>
                  <TableHead>Staff</TableHead>
                  <TableHead>Description</TableHead>
                  <TableHead>Estimation Price/Pcs</TableHead>
                  <TableHead>Estimation Quantity</TableHead>
                  <TableHead>Start Date</TableHead>
                  <TableHead>End Date</TableHead>
                  <TableHead v-for="role in allActivityRoles" :key="role.id">
                    {{ role.name }}
                  </TableHead>
                  <TableHead>Total Qty</TableHead>
                </TableRow>
              </TableHeader>

              <!-- Table Body -->
              <TableBody>
                <TableRow
                  v-for="item in reportStore.productionDetailItem"
                  :key="item.model_id"
                >
                  <TableCell>{{ item.model_id }}</TableCell>
                  <TableCell>{{ item.created_by_name }}</TableCell>
                  <TableCell>{{ item.description }}</TableCell>
                  <TableCell>{{ formatRupiah(item.estimation_price_pcs) }}</TableCell>
                  <TableCell>{{ item.estimation_qty }}</TableCell>
                  <TableCell>{{ item.start_date }}</TableCell>
                  <TableCell>{{ item.end_date ?? '-' }}</TableCell>

                  <!-- Dynamic Activity Cells -->
                  <TableCell
                    v-for="role in allActivityRoles"
                    :key="`act-${role.id}-${item.model_id}`"
                  >
                    <div v-if="item.activities?.[role.id]">
                      <div>Qty: {{ item.activities[role.id].qty }}</div>
                      <div class="text-xs italic text-gray-500">
                        {{ item.activities[role.id].remark || '-' }}
                      </div>
                    </div>
                    <div v-else>-</div>
                  </TableCell>

                  <!-- Total Qty -->
                  <TableCell class="font-semibold">
                    {{ item.subtotal_qty }}
                  </TableCell>
                </TableRow>
              </TableBody>

              <!-- Table Footer -->
              <tfoot>
                <TableRow>
                  <TableCell colspan="7" class="text-right font-bold">Total</TableCell>
                  <TableCell
                    v-for="role in allActivityRoles"
                    :key="'footer-' + role.id"
                    class="font-bold"
                  >
                    {{
                      reportStore.productionSummary.reduce((sum, item) => {
                        return sum + (item.activities?.[role.id]?.qty || 0);
                      }, 0)
                    }}
                  </TableCell>
                  <TableCell class="font-bold">
                    {{
                      reportStore.productionSummary.reduce((sum, item) => {
                        return sum + (item.subtotal_qty || 0);
                      }, 0)
                    }}
                  </TableCell>
                </TableRow>
              </tfoot>
            </Table>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
