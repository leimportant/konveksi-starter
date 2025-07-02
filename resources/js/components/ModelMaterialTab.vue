<template>
  <div class="space-y-4">
    <!-- Material List -->
    <div v-for="(material, index) in modelValue" :key="index" class="p-4 border rounded-lg space-y-4">
      <div class="flex justify-between items-center border-b pb-2">
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

        <Vue3Select
          v-model="material.product_id"
          :options="productOption"
          label="name"
          value="id"
          :onSearch="searchProducts"
          placeholder="Pilih Material"
          class="w-full text-sm"
          @update:modelValue="(val: Product) => handleProductChange(val, index)"
        />


        </div>

        <!-- Quantity, UOM, dan Price dalam satu baris -->
        <div>
          <label class="text-sm font-medium block mb-1">Quantity, Unit & Price</label>
          <div class="flex gap-2">
            <input 
              type="number" 
              v-model="material.qty"
              min="1"
              class="block w-1/3 rounded-md border bg-background text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring py-2 px-3 shadow-sm sm:text-sm"
            />
            <select
              v-model="material.uom_id"
              class="block w-1/3 rounded-md border bg-background text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring py-2 px-3 shadow-sm sm:text-sm"
            >
              <option value="">Pilih Unit</option>
              <option v-if="uoms.length === 0" disabled>Loading units...</option>
              <option
                v-for="uom in uoms"
                :key="uom.id"
                :value="uom.id"
                :selected="uom.id === getDefaultUom(material.product_id)"
              >
                {{ uom.name }}
              </option>
            </select>
            <input 
              type="number" 
              v-model="material.price"
              min="0"
              placeholder="Harga"
              class="block w-1/3 rounded-md border bg-background text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring py-2 px-3 shadow-sm sm:text-sm"
            />
          </div>
        </div>

        <!-- Remark Input -->
        <div class="md:col-span-2">
          <label class="text-sm font-medium block mb-1 ">Remark</label>
          <input 
            type="text" 
            v-model="material.remark"
            class="block w-full rounded-md border bg-background text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring py-2 px-3 shadow-sm sm:text-sm"
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
import { onMounted, computed, watch, ref } from 'vue';  // Add watch to imports
import { Button } from '@/components/ui/button';
import { useUomStore } from '@/stores/useUomStore';
import { useProductStore } from '@/stores/useProductStore';
import { Trash } from 'lucide-vue-next';
import axios from 'axios'
import Vue3Select from 'vue3-select'
import 'vue3-select/dist/vue3-select.css'

interface Material {
  product_id: number | null;
  qty: number | null;
  uom_id: number | null;
  remark: string | "";
  price: number | null;
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

interface Product {
  id: number;
  name: string;
}

const productOption = ref<Product[]>([]);

const addMaterial = () => {
  const materials = [...props.modelValue];
  materials.push({
    product_id: null,
    qty: 1,
    uom_id: null,
    remark: '',
    price: null  // Add this line
  });
  emit('update:modelValue', materials);
};

const searchProducts = async (search: string) => {
  if (search.length < 2) {
    productOption.value = []
    return
  }
  try {
    const res = await axios.get('/api/products', { params: { search } })
    productOption.value = res.data.data
  } catch (error) {
    console.error('Search error:', error);
  }
}

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

// Add function to get default UOM from selected product
const getDefaultUom = (productId: number | null) => {
  if (!productId) return null;
  const product = products.value.find(p => p.id === productId);
  return product?.uom_id || null;
};

// Watch for product_id changes to set default UOM
watch(() => props.modelValue, (newValue: Material[]) => {
  newValue.forEach(material => {
    if (material.product_id && !material.uom_id) {
      material.uom_id = getDefaultUom(material.product_id);
    }
  });
}, { deep: true });

// Handle product selection to ensure product_id is stored as number
const handleProductChange = (selectedProduct: any, index: number) => {
  // Get the current materials array
  const materials = [...props.modelValue];
  
  // Ensure index is valid
  if (index >= 0 && index < materials.length) {
    // If selectedProduct is an object, extract the id
    if (selectedProduct && typeof selectedProduct === 'object') {
      materials[index].product_id = selectedProduct.id;
    } else if (typeof selectedProduct === 'number') {
      // If it's already a number, use it directly
      materials[index].product_id = selectedProduct;
    }
    
    // Set default UOM if not already set
    if (!materials[index].uom_id) {
      materials[index].uom_id = getDefaultUom(materials[index].product_id);
    }
    
    // Emit the updated array
    emit('update:modelValue', materials);
  }
};


</script>