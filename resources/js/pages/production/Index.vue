<script setup lang="ts">
import { onMounted, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Edit, Trash2, Plus } from 'lucide-vue-next';
import { useToast } from "@/composables/useToast";
import { useProductionStore } from '@/stores/useProductionStore';
import { storeToRefs } from 'pinia';


const toast = useToast();
const productionStore = useProductionStore();
const { items: productions, loading } = storeToRefs(productionStore);

// Pagination and sorting state
const currentPage = ref(1);
const perPage = ref(10);
const sortField = ref('created_at');
const sortOrder = ref<'asc' | 'desc'>('desc');

const props = defineProps<{ id: string | number }>();

const breadcrumbs = [
  { title: 'Production', href: `/production/${props.id}` }
];

const fetchData = async () => {
  try {
    await productionStore.fetchProductions({
      page: currentPage.value,
      per_page: perPage.value,
      sort_field: sortField.value,
      sort_order: sortOrder.value
    });
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to fetch productions");
  }
};

onMounted(() => {
  fetchData();
});

const handleDelete = async (id: string) => {
  if (!confirm('Are you sure you want to delete this production?')) return;

  try {
    await productionStore.deleteProduction(id);
    toast.success("Production deleted successfully");
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to delete production");
  }
};

const getActivityRoleName = (item: any) => {
  return item.activityRole?.name || 'N/A';
};
</script>

<template>
  <Head title="Production Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="flex justify-between items-center mb-6">
        <Button @click="$inertia.visit(`/production/${props.id}/create`)">
          <Plus class="h-4 w-4 mr-2" />
          Add Production
        </Button>
      </div>

      <div class="space-y-4">
        <div v-if="loading" class="text-center py-4">
          Loading...
        </div>
        <div v-else-if="productions.length === 0" class="text-center py-4">
          No records found
        </div>
        <div v-else class="rounded-md border">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Model</TableHead>
                <TableHead>Activity</TableHead>
                <TableHead>Remark</TableHead>
                <TableHead>Created At</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in productions" :key="item.id">
                <TableCell>{{ item.model?.description }}</TableCell>
                <TableCell>{{ getActivityRoleName(item) }}</TableCell>
                <TableCell>{{ item.remark }}</TableCell>
                <TableCell>{{ new Date(item.created_at).toLocaleDateString() }}</TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end gap-2">
                    <Button variant="ghost" size="icon" @click="$inertia.visit(`/production/${item.id}/edit`)">
                      <Edit class="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="icon" @click="handleDelete(item.id)">
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>