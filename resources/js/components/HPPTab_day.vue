<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useProductStore } from '@/stores/useProductStore';
import { useMasterActivityRoleStore } from '@/stores/useMasterActivityRoleStore';

const props = defineProps<{
  modelMaterials: {
    product_id: number;
    qty: number;
    uom_id: number;
    remark: string;
    price: number | null;
  }[];
  activityItems: {
    activity_role_id: number;
    price: number;
    activity_name?: string;
  }[];
  startDate?: string | null;
  endDate?: string | null;
}>();

const productStore = useProductStore();
const activityRoleStore = useMasterActivityRoleStore();

const products = computed(() => productStore.items);
// const activityRoles = computed(() => activityRoleStore.items);

// Fungsi untuk mendapatkan nama material
const getMaterialName = (productId: number) => {
  const product = products.value.find(p => p.id === productId);
  return product?.name || `Material ${productId}`;
};

// Map model materials to the format we need for calculations
const mappedMaterials = computed(() => {
  return props.modelMaterials.map(material => ({
    material_id: material.product_id,
    qty: material.qty,
    price: material.price || 0
  }));
});

// Use mappedMaterials instead of direct modelMaterials
const totalMaterialCost = computed(() => {
  return mappedMaterials.value.reduce((total, item) => {
    return total + (item.qty * item.price);
  }, 0);
});

// Fungsi untuk menghitung durasi dalam hari
const calculateDuration = (start_date?: string | null, end_date?: string | null): number => {
  if (!start_date || !end_date) return 1;
  
  const start = new Date(start_date);
  const end = new Date(end_date);
  const diffTime = Math.abs(end.getTime() - start.getTime());
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  
  return diffDays || 1; // Minimal 1 hari
};

// Hitung total biaya aktivitas dengan mempertimbangkan durasi
const totalActivityCost = computed(() => {
  const duration = calculateDuration(props.startDate, props.endDate);
  return props.activityItems.reduce((total, item) => {
    return total + ((item.price || 0) * duration);
  }, 0);
});

// Hitung total HPP
const totalHPP = computed(() => {
  return totalMaterialCost.value + totalActivityCost.value;
});

// Format currency
const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('id-ID', { 
    style: 'currency', 
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value);
};

// Tambahkan fungsi untuk memformat nama aktivitas
// Fungsi untuk mendapatkan nama aktivitas
const getActivityName = (activity: { activity_role_id: number; activity_name?: string }) => {
  const activityRole = activityRoleStore.items.find(role => role.id === activity.activity_role_id);
  if (activityRole?.name) {
    return activityRole.name;
  }
  return activity.activity_name || `Aktivitas ${activity.activity_role_id}`;
};

// Fetch activity roles on mount
onMounted(async () => {
  try {
    await Promise.all([
      productStore.fetchProducts(),
      activityRoleStore.fetchActivityRoles()
    ]);
  } catch (error) {
    console.error('Failed to fetch data:', error);
  }
});

// Fungsi untuk memformat tanggal
const formatDate = (date: string | null | undefined) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};
</script>

<template>
  <div class="space-y-4 sm:space-y-6">
    <div class="rounded-lg border p-3 sm:p-4 space-y-3 sm:space-y-4">
      <h3 class=" sm:text-lg font-semibold border-b pb-2">Ringkasan HPP</h3>
      
      <!-- Durasi Proyek -->
      <div class="space-y-2">
        <h4 class="text-sm sm: font-medium border-b pb-2">Durasi Proyek</h4>
        <div class="grid gap-1 sm:gap-2 text-sm">
          <div class="flex justify-between">
            <span>Tanggal Mulai</span>
            <span>{{ formatDate(startDate) }}</span>
          </div>
          <div class="flex justify-between">
            <span>Tanggal Selesai</span>
            <span>{{ formatDate(endDate) }}</span>
          </div>
          <div class="flex justify-between font-medium">
            <span>Total Durasi</span>
            <span>{{ calculateDuration(startDate, endDate) }} hari</span>
          </div>
        </div>
      </div>

      <!-- Material Cost -->
      <div class="space-y-2">
        <h4 class="text-sm sm: font-medium border-b pb-2">Biaya Material</h4>
        <div class="grid gap-1 sm:gap-2 text-sm">
          <div v-for="material in modelMaterials" :key="material.product_id" class="flex justify-between">
            <span>{{ getMaterialName(material.product_id) }}</span>
            <span>{{ formatCurrency(material.qty * (material.price || 0)) }}</span>
          </div>
        </div>
        <div class="flex justify-between font-medium pt-2 border-t text-sm sm:">
          <span>Total Biaya Material</span>
          <span>{{ formatCurrency(totalMaterialCost) }}</span>
        </div>
      </div>

      <!-- Activity Cost -->
      <div class="space-y-2">
        <h4 class="text-sm sm: font-medium border-b pb-2">Biaya Aktivitas</h4>
        <div class="grid gap-1 sm:gap-2">
          <div v-for="activity in activityItems" :key="activity.activity_role_id" class="flex justify-between">
            <div class="flex flex-col">
              <span class="text-sm">{{ getActivityName(activity) }}</span>
              <span class="text-xs sm:text-sm text-gray-500">
                {{ calculateDuration(startDate, endDate) }} hari
              </span>
            </div>
            <div class="flex flex-col items-end">
              <span class="text-xs sm:text-sm">{{ formatCurrency(activity.price || 0) }} / hari</span>
              <span class="text-sm font-medium">
                {{ formatCurrency((activity.price || 0) * calculateDuration(startDate, endDate)) }}
              </span>
            </div>
          </div>
        </div>
        <div class="flex justify-between font-medium pt-2 border-t text-sm sm:">
          <span>Total Biaya Aktivitas</span>
          <span>{{ formatCurrency(totalActivityCost) }}</span>
        </div>
      </div>

      <!-- Total HPP -->
      <div class="flex justify-between font-bold pt-4 border-t  sm:text-lg">
        <span>Total HPP</span>
        <span>{{ formatCurrency(totalHPP) }}</span>
      </div>
    </div>
  </div>
</template>