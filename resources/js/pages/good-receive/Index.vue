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
        <div v-else v-for="item in goodReceives" :key="item.id" 
          class="bg-white rounded-lg shadow p-4 border border-gray-200">
          <div class="flex flex-col h-full">
            <div class="space-y-2 flex-1">
              <div class="flex justify-between items-center">
                <span class="font-semibold">Tanggal : {{ new Date(item.date).toLocaleDateString() }}</span>
                <div class="flex items-center gap-1">
                  <div class="text-sm text-gray-500">Recipient:</div>
                  <div class="font-medium">{{ item.recipent }}</div>
                </div>
              </div>
              <p class="text-sm text-gray-700">Model : {{ item.model?.description }}</p>
              
            </div>
            <div class="flex justify-end gap-2 mt-4 bg-gray-50 p-2 rounded-md">
              <Button variant="ghost" size="icon" @click="$inertia.visit(`/good-receive/${item.id}/edit`)">
                <Edit class="h-4 w-4" />
              </Button>
              <Button variant="ghost" size="icon" @click="handleDelete(item.id)">
                <Trash2 class="h-4 w-4" />
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>