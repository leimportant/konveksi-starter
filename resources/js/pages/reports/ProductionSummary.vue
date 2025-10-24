<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { useReportStore } from '@/stores/useReportStore';
import { Head } from '@inertiajs/vue3';

const reportStore = useReportStore();

const startDate = ref('');
const endDate = ref('');
const searchKey = ref('');

const breadcrumbs = [{ title: 'Production Summary', href: '/reports/production-summary' }];

const fetchReport = () => {
  reportStore.fetchProductionSummary(startDate.value, endDate.value, searchKey.value);
};

onMounted(() => {
  const today = new Date();
  const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
  startDate.value = firstDay.toISOString().split('T')[0];
  endDate.value = today.toISOString().split('T')[0];
  fetchReport();
});

// Compute unique activity roles for table header
const allActivityRoles = computed(() => {
  const map = new Map<string, string>();
  Object.values(reportStore.productionSummary).forEach(item => {
    Object.values(item.activities ?? {}).forEach(act =>  {
      if (!map.has(act.name)) map.set(act.name, act.name);
    });
  });
  return Array.from(map.entries()).map(([name]) => ({ id: name, name }));
});

// Helper: get activity qty for detail row
const getDetailQty = (detail: any, roleName: string) => {
  return detail.activity_role_name === roleName ? detail.total_qty : 0;
};
</script>

<template>
  <Head title="Production Summary Report" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <template #header>
      <h2 class="text-xl font-semibold text-gray-800">Production Summary Report</h2>
    </template>

    <div class="py-6 px-2 max-w-7xl mx-auto space-y-4">
      <!-- Filter Bar -->
      <div class="flex flex-wrap gap-2 mb-4 items-end">
        <Input type="date" v-model="startDate" class="w-40" />
        <Input type="date" v-model="endDate" class="w-40" />
        <Input type="text" v-model="searchKey" placeholder="Search Model / Activity" class="flex-1 min-w-[200px]" />
        <Button class="bg-indigo-600 text-white hover:bg-indigo-700" @click="fetchReport">Show</Button>
      </div>

      <!-- Loading / Error -->
      <div v-if="reportStore.loading" class="py-4 text-center text-gray-500">Loading...</div>
      <div v-else-if="reportStore.error" class="py-4 text-center text-red-500">{{ reportStore.error.message }}</div>

      <!-- Modern Table Report -->
      <div v-else class="overflow-x-auto bg-white shadow rounded-lg">
        <table class="min-w-full text-sm text-gray-800">
          <thead class="bg-gray-50 font-semibold text-xs uppercase text-gray-600">
            <tr>
              <th class="px-3 py-2">Model</th>
              <th class="px-3 py-2">Variant</th>
              <th
                v-for="role in allActivityRoles"
                :key="'header-role-' + role.id"
                class="px-2 py-1 text-center"
              >
                {{ role.name }}
              </th>
              <th class="px-3 py-2 text-center">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="item in reportStore.productionSummary" :key="item.model_id">
              <!-- Summary Row -->
              <tr class="bg-gray-100 font-semibold hover:bg-gray-200">
                <td class="px-3 py-2">{{ item.description }}</td>
                <td class="px-3 py-2">-</td>
                <td
                  v-for="role in allActivityRoles"
                  :key="'summary-' + role.id + '-' + item.model_id"
                  class="px-2 py-1 text-center"
                >
                  {{
                    (Object.values(item.activities).find(act => act.name === role.name)?.qty) ?? '-'
                  }}
                </td>
                <td class="px-3 py-2 text-center">{{ item.subtotal_qty }}</td>
              </tr>

              <!-- Detail Rows -->
              <template v-for="detail in item.details" :key="detail.production_id">
                <tr class="hover:bg-gray-50">
                  <td class="px-3 py-1">- {{ detail.employee_name }}</td>
                  <td class="px-3 py-1">
                    <div class="flex flex-wrap gap-1">
                      <div
                        v-for="itm in detail.items"
                        :key="itm.size_id"
                        class="bg-gray-50 border px-2 py-1 rounded text-xs"
                      >
                        {{ itm.size_id }} - {{ itm.variant }} : {{ itm.qty }}
                      </div>
                    </div>
                  </td>
                  <td
                    v-for="role in allActivityRoles"
                    :key="'detail-' + role.id + '-' + detail.production_id"
                    class="px-2 py-1 text-center"
                  >
                    {{ getDetailQty(detail, role.name) || '-' }}
                  </td>
                  <td class="px-3 py-1 text-center">{{ detail.total_qty }}</td>
                </tr>
              </template>
            </template>
          </tbody>

          <!-- Grand Total Footer -->
          <tfoot class="bg-gray-50 font-semibold text-xs text-gray-600">
            <tr>
              <td colspan="2" class="px-3 py-2 text-right">Grand Total:</td>
              <td
                v-for="role in allActivityRoles"
                :key="'footer-role-' + role.id"
                class="px-2 py-1 text-center"
              >
                {{
                  Object.values(reportStore.productionSummary).reduce((sum, model) => {
                    return sum + (Object.values(model.activities).find(act => act.name === role.name)?.qty ?? 0);
                  }, 0)
                }}
              </td>
              <td class="px-3 py-2 text-center">
                {{
                  Object.values(reportStore.productionSummary).reduce((sum, model) => sum + (model.subtotal_qty ?? 0), 0)
                }}
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
