<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useProductStore } from '@/stores/useProductStore';
import { useMasterActivityRoleStore } from '@/stores/useMasterActivityRoleStore';

const props = defineProps<{
  modelMaterials: {
    product_id: number | { id: number };
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
  sizeItems?: {
    size_id: string;
    qty: number;
  }[],
  startDate?: string | null;
  endDate?: string | null;
}>();

const productStore = useProductStore();
const activityRoleStore = useMasterActivityRoleStore();

const products = computed(() => productStore.items);
// const activityRoles = computed(() => activityRoleStore.items);

// Fungsi untuk mendapatkan nama material
const getMaterialName = (productId: number | { id: number }) => {
  const id = typeof productId === 'object' && productId !== null ? productId.id : productId;
  const product = products.value.find(p => p.id === id);
  return product?.name || `Material ${id}`;
};



// Map model materials to the format we need for calculations
const mappedMaterials = computed(() => {
  return props.modelMaterials.map(material => ({
    material_id: typeof material.product_id === 'object'
      ? material.product_id.id
      : material.product_id,
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
  return props.activityItems.reduce((total, item) => {
    return total + ((item.price || 0) * totalProduction.value);
  }, 0);
});


// Hitung total HPP
const totalHPP = computed(() => {
  return totalMaterialCost.value + totalActivityCost.value;
});

const hppPerPcs = computed(() => {
  if (totalProduction.value === 0) return 0;
  return totalHPP.value / totalProduction.value;
});


const totalProduction = computed(() => {
  return props.sizeItems?.reduce((sum, item) => sum + item.qty, 0) || 0;
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
        <span class="text-[14px]">Durasi Proyek</span>
        <div class="grid gap-1 sm:gap-2 text-sm">
          <div class="flex justify-between">
            <span class="text-[12px]">Tanggal Mulai</span>
            <span class="text-[12px]">{{ formatDate(startDate) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-[12px]">Tanggal Selesai</span>
            <span class="text-[12px]">{{ formatDate(endDate) }}</span>
          </div>
          <div class="flex justify-between font-medium">
            <span class="text-[12px]">Total Durasi</span>
            <span class="text-[12px]">{{ calculateDuration(startDate, endDate) }} hari</span>
          </div>
        </div>
      </div>

      <!-- Material Cost -->
      <div class="space-y-2">
        <span class="text-[14px]">Biaya Material</span>
        <div class="grid gap-1 sm:gap-2 text-sm">
          <div v-for="material in modelMaterials" :key="typeof material.product_id === 'object' ? material.product_id.id : material.product_id" class="flex justify-between">
            <span class="text-[12px]">{{ getMaterialName(material.product_id) }}</span>
            <span class="text-[12px]">{{ formatCurrency(material.qty * (material.price || 0)) }}</span>
          </div>
        </div>
        <div class="flex justify-between font-medium pt-2 border-t text-sm sm:">
          <span class="text-[12px]">Total Biaya Material</span>
          <span class="text-[12px]">{{ formatCurrency(totalMaterialCost) }}</span>
        </div>
         <div class="flex justify-between font-medium pt-2 border-t text-sm sm:">
          <span class="text-[12px]">Total Produksi</span>
          <span class="text-[12px]">{{ totalProduction }}</span>
        </div>
      </div>

      <!-- Size Cost -->
      <div class="space-y-2">
        <span class="text-[14px]">Ukuran</span>
      
        <div class="grid gap-1 sm:gap-2 text-sm">
            <div v-for="size in sizeItems" :key="size.size_id" class="flex justify-between">
              <span class="text-[12px]">{{ size.size_id }}</span> 
              <span class="text-[12px]">{{ size.qty }}</span>
            </div>
        </div>
      </div>
      <!-- Activity Cost -->
      <div class="space-y-2">
        <span class="text-[14px]">Biaya Aktivitas</span>
        <div class="grid gap-1 sm:gap-2">
          <div v-for="activity in activityItems" :key="activity.activity_role_id" class="flex justify-between">
            <div class="flex flex-col">
              <span class="text-sm">{{ getActivityName(activity) }}</span>
              <span class="text-xs sm:text-sm text-gray-500">
                {{ calculateDuration(startDate, endDate) }} hari
              </span>
            </div>
            <div class="flex flex-col items-end">
              <span class="text-xs sm:text-sm">{{ formatCurrency(activity.price || 0) }} / PCS</span>
              <span class="text-sm font-medium">
                {{ formatCurrency((activity.price || 0) * totalProduction) }}
              </span>
            </div>
          </div>
        </div>
        <div class="flex justify-between font-medium pt-2 border-t text-sm sm:">
          <span class="text-[12px]">Total Biaya Aktivitas</span>
          <span class="text-[12px]">{{ formatCurrency(totalActivityCost) }}</span>
        </div>
      </div>

      <!-- Total HPP -->
      <div class="flex justify-between font-bold pt-4 border-t  sm:text-lg">
        <span class="text-[12px]">Total HPP</span>
        <span class="text-[12px]">{{ formatCurrency(totalHPP) }}</span>
      </div>

      <!-- HPP per PCS -->
      <div class="flex justify-between font-medium text-sm sm:">
        <span class="text-[12px]">HPP per PCS</span>
        <span class="text-[12px]">{{ formatCurrency(hppPerPcs) }}</span>
      </div>
    </div>
  </div>
</template>