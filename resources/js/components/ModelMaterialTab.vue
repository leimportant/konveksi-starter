<template>
  <div class="space-y-4">
    <!-- Material List -->
    <div v-for="(material, index) in modelValue" :key="index" class="p-4 border rounded-lg space-y-4">
      <div class="flex justify-between items-center">
        <h3 class="font-medium">Material #{{ index + 1 }}</h3>
        <Button 
          type="button"
          variant="destructive" 
          size="icon"
          @click.prevent="removeMaterial(index)">
          <Trash/>
        </Button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Material Selection -->
        <div>
          <label class="text-sm font-medium block mb-1">Material</label>
          <select
            v-model="material.product_id"
            class="block w-full rounded-md border border-gray-300 bg-white py-2 px-3 shadow-sm sm:text-sm"
          >
            <option value="">Pilih Material</option>
            <option v-if="products.length === 0" disabled>Loading materials...</option>
            <option v-for="product in products" :key="product.id" :value="product.id">
              {{ product.name }}
            </option>
          </select>
        </div>

        <!-- Quantity dan UOM dalam satu baris -->
        <div>
          <label class="text-sm font-medium block mb-1">Quantity & Unit</label>
          <div class="flex gap-2">
            <input 
              type="number" 
              v-model="material.qty"
              min="1"
              class="block w-1/2 rounded-md border border-gray-300 bg-white py-2 px-3 shadow-sm sm:text-sm"
            />
            <!-- Change this part in your template -->
            <select
              v-model="material.uom_id"
              class="block w-1/2 rounded-md border border-gray-300 bg-white py-2 px-3 shadow-sm sm:text-sm"
            >
              <option value="">Pilih Unit</option>
              <option v-if="uoms.length === 0" disabled>Loading units...</option>
              <option
                v-for="uom in uoms"
                :key="uom.id"
                :value="uom.id"
              >
                {{ uom.name }}
              </option>
            </select>
          </div>
        </div>

        <!-- Remark Input -->
        <div class="md:col-span-2">
          <label class="text-sm font-medium block mb-1">Remark</label>
          <input 
            type="text" 
            v-model="material.remark"
            class="block w-full rounded-md border border-gray-300 bg-white py-2 px-3 shadow-sm sm:text-sm"
            placeholder="Optional remark"
          />
        </div>
      </div>
    </div>

    <!-- Add Material Button -->
    <div class="flex justify-end">
      <Button 
        type="button"
        variant="secondary"
        @click.prevent="addMaterial"
      >
        <i class="pi pi-plus mr-2" />
        Tambah Material
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { useUomStore } from '@/stores/useUomStore';
import { useProductStore } from '@/stores/useProductStore';
import { Trash } from 'lucide-vue-next';

interface Material {
  product_id: number | null;
  qty: number | null;
  uom_id: number | null;
  remark: string | "";
}

interface Props {
  modelValue: Material[]
}

const props = defineProps<Props>();
const emit = defineEmits<{
  (e: 'update:modelValue', value: Material[]): void
}>();

// Initialize stores
const productStore = useProductStore();
const uomStore = useUomStore();

// Fix computed properties to access correct store state properties
const products = computed(() => productStore.items);
const uoms = computed(() => uomStore.items);

const addMaterial = () => {
  const materials = [...props.modelValue];
  materials.push({
    product_id: null,
    qty: 1,
    uom_id: null,
    remark: ''
  });
  emit('update:modelValue', materials);
};

const removeMaterial = (index: number) => {
  const materials = [...props.modelValue];
  materials.splice(index, 1);
  emit('update:modelValue', materials);
};

// Fetch data using stores
onMounted(async () => {
  try {
    await Promise.all([
      productStore.fetchProducts(),
      uomStore.fetchUoms()
    ]);
  } catch (error) {
    console.error('Failed to fetch data:', error);
  }
});
</script>