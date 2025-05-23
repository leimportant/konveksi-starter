<script setup lang="ts">
import { onMounted, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Edit, Trash2, Plus, LucideView } from 'lucide-vue-next';
import { useToast } from '@/composables/useToast';
import { useProductionStore } from '@/stores/useProductionStore';
import { storeToRefs } from 'pinia';
import { Input } from '@/components/ui/input';

const toast = useToast();
const productionStore = useProductionStore();
const { productions, loading } = storeToRefs(productionStore);

const currentPage = ref(1);
const perPage = ref(10);
const sortField = ref('created_at');
const sortOrder = ref<'asc' | 'desc'>('desc');

const props = defineProps<{
  activity_role: string | number
}>();

const breadcrumbs = [
  { title: 'Production', href: `/production/${props.activity_role}` }
];

const searchQuery = ref('');
interface DateRange {
  from: Date | null;
  to: Date | null;
}

const dateRange = ref<DateRange>({
  from: null,
  to: null
});

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
      date_to: dateRange.value.to?.toISOString()
    });
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to fetch productions");
  }
};

onMounted(fetchData);

const handleDelete = async (id: string) => {
  if (!confirm('Are you sure you want to delete this production?')) return;

  try {
    await productionStore.deleteProduction(id);
    toast.success("Production deleted successfully");
    await fetchData();
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to delete production");
  }
};
</script>

<template>
  <Head title="Production Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-2 py-2">
      <!-- Top Bar -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2 mb-4">
        <div class="flex gap-2 w-full md:w-auto">
          <Input
            v-model="searchQuery"
            placeholder="Search..."
            class="w-full md:w-64"
            @keyup.enter="fetchData"
          />
          <Button @click="fetchData" class="whitespace-nowrap">Filter</Button>
        </div>
        <Button @click="$inertia.visit(`/production/${props.activity_role}/create`)">
          <Plus class="h-4 w-4 mr-2" />
          Add
        </Button>
      </div>

      <div class="space-y-4">
        <div v-if="loading" class="text-center py-4 text-gray-500">Loading...</div>
        <div v-else-if="productions.length === 0" class="text-center py-4 text-gray-400">No records found</div>

        <!-- Desktop Table -->
        <div v-else class="overflow-x-auto rounded-xl border shadow-sm hidden md:block">
          <Table class="w-full text-sm text-gray-700">
            <TableHeader>
              <TableRow class="bg-gray-100">
                <TableHead>Model</TableHead>
                <TableHead>Activity</TableHead>
                <TableHead>Size/Qty</TableHead>
                <TableHead>Created At</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in productions" :key="item.id">
                <TableCell>{{ item.model?.description || '-' }}</TableCell>
                <TableCell>{{ item.activity_role?.name || '-' }}</TableCell>
                <TableCell>
                  <template v-if="item.items?.length">
                    <div v-for="i in item.items" :key="i.id">
                      {{ i.size_id }} : {{ i.qty }}
                    </div>
                  </template>
                  <template v-else>-</template>
                </TableCell>
                <TableCell>{{ item.created_at ? new Date(item.created_at).toLocaleDateString() : '-' }}</TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end gap-2">
                    <Button v-if="item.status === 1 || item.status === 3" variant="ghost" size="icon" class="hover:bg-gray-100" @click="$inertia.visit(`/production/${item.activity_role_id}/edit/${item.id}`)">
                      <Edit class="h-4 w-4" />
                    </Button>
                    <Button v-if="item.status === 1 || item.status === 3" variant="ghost" size="icon" class="hover:bg-gray-100" @click="handleDelete(item.id)">
                      <Trash2 class="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="icon" class="hover:bg-gray-100" @click="$inertia.visit(`/production/${item.activity_role_id}/view/${item.id}`)">
                      <LucideView class="h-4 w-4" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <!-- Mobile Cards -->
        <div class="md:hidden space-y-2">
          <div
              v-for="(item, index) in productions"
              :key="item.id"
              :class="[
                'rounded-2xl border border-gray-200 p-4 shadow-sm w-full space-y-4 transition hover:shadow-md',
                index % 2 === 1 ? 'bg-[#f0f5f0]' : 'white'
              ]"
            >
            <!-- Title Row -->
            <div class="flex justify-between items-center">
              <h3 class="font-semibold text-sm text-gray-900">{{ item.model?.description || '-' }}</h3>
              <p class="text-xs text-gray-500">{{ item.activity_role?.name || '-' }}</p>
            </div>

            <!-- Info Row -->
            <div class="flex justify-between items-start gap-4">
              <div class="flex-1">
                <p class="text-xs font-semibold text-gray-500">Size / Qty</p>
                <div v-if="item.items?.length">
                  <div v-for="i in item.items" :key="i.id" class="text-sm text-gray-800">
                    {{ i.size_id }} : {{ i.qty }}
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

            <!-- Actions -->
            <div class="flex justify-end gap-2">
              <Button v-if="item.status === 1 || item.status === 3" variant="ghost" size="icon" class="hover:bg-gray-100" @click="$inertia.visit(`/production/${item.activity_role_id}/edit/${item.id}`)">
                      <Edit class="h-4 w-4" />
                    </Button>
              <Button v-if="item.status === 1 || item.status === 3" variant="ghost" size="icon" class="hover:bg-gray-100" @click="handleDelete(item.id)">
                <Trash2 class="h-4 w-4" />
              </Button>
              <Button variant="ghost" size="icon" class="hover:bg-gray-100"  @click="$inertia.visit(`/production/${item.activity_role_id}/view/${item.id}`)">
                <LucideView class="h-4 w-4" />
              </Button>
            </div>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>
