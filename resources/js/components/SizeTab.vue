<!-- components/SizeTab.vue -->
<template>
  <div class="space-y-6">
    <!-- 🔹 DESKTOP TABLE -->
    <table class="hidden md:table min-w-full divide-y divide-gray-200">
      <thead>
        <tr>
          <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Size</th>
          <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Variant</th>
          <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Qty</th>
          <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Harga Satuan</th>
          <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Harga Grosir</th>
          <th class="px-6 py-3 text-center text-xs font-semibold uppercase w-16">Action</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <tr v-for="(item, index) in modelItems" :key="index">
          <td class="px-6 py-4">
            <select
              v-model="item.size_id"
              class="w-full rounded-md border p-2 text-sm"
            >
              <option disabled value="">Pilih Ukuran</option>
              <option v-for="size in sizeStore.sizes" :key="size.id" :value="size.id">
                {{ size.name }}
              </option>
            </select>
          </td>
          <td class="px-6 py-4">
            <Input type="text" v-model="item.variant" class="w-full" />
          </td>
          <td class="px-6 py-4">
            <Input type="number" v-model="item.qty" class="w-full" min="0" />
          </td>
          <td class="px-6 py-4">
            <Input type="number" v-model="item.price_store" class="w-full" min="0" />
          </td>
          <td class="px-6 py-4">
            <Input type="number" v-model="item.price_grosir" class="w-full" min="0" />
          </td>
          <td class="px-6 py-4 text-center">
            <Button variant="destructive" size="icon" @click="removeItem(index)">
              <Trash />
            </Button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- 🔹 MOBILE VIEW / CARD INPUT -->
    <div class="md:hidden space-y-4">
      <div
        v-for="(item, index) in modelItems"
        :key="index"
        class="border rounded-lg p-4 shadow-sm space-y-4 bg-white"
      >
        <!-- SIZE -->
        <div>
          <label class="text-xs font-bold">Size</label>
          <select v-model="item.size_id" class="w-full rounded-md border p-2 text-sm mt-1">
            <option disabled value="">Pilih Ukuran</option>
            <option v-for="size in sizeStore.sizes" :key="size.id" :value="size.id">
              {{ size.name }}
            </option>
          </select>
        </div>

        <!-- VARIANT + QTY -->
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs font-bold">Variant</label>
            <Input type="text" v-model="item.variant" class="w-full mt-1" />
          </div>
          <div>
            <label class="text-xs font-bold">Qty</label>
            <Input type="number" v-model="item.qty" class="w-full mt-1" min="0" />
          </div>
        </div>

        <!-- HARGA TOKO + HARGA GROSIR -->
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs font-bold">Harga Toko</label>
            <Input type="number" v-model="item.price_store" class="w-full mt-1" min="0" />
          </div>
          <div>
            <label class="text-xs font-bold">Harga Grosir</label>
            <Input type="number" v-model="item.price_grosir" class="w-full mt-1" min="0" />
          </div>
        </div>

        <!-- DELETE BUTTON -->
        <div class="pt-2 flex justify-end">
          <Button variant="destructive" size="icon" @click="removeItem(index)">
            <Trash />
          </Button>
        </div>
      </div>
    </div>

    <!-- ADD BUTTON -->
    <Button type="button" @click="addItem">
      + Tambah Ukuran
    </Button>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, computed } from "vue";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Trash } from "lucide-vue-next";
import { useSizeStore } from "@/stores/useSizeStore";

// props
const props = defineProps<{
  modelValue: { size_id: string; qty: number; variant: string; price_store: number; price_grosir: number }[];
}>();

// emits
const emit = defineEmits<{
  "update:modelValue": [value: typeof props.modelValue];
  "update:totalQuantity": [value: number];
}>();

// store
const sizeStore = useSizeStore();

// data
const modelItems = ref(props.modelValue.slice());

// total qty
const totalQuantity = computed(() =>
  modelItems.value.reduce((sum, item) => sum + (Number(item.qty) || 0), 0)
);

// sync to parent
watch(totalQuantity, (val) => emit("update:totalQuantity", val));
watch(modelItems, (val) => emit("update:modelValue", val), { deep: true });

// fetch sizes
onMounted(() => {
  sizeStore.fetchSizes();
  emit("update:totalQuantity", totalQuantity.value);
});

// actions
const addItem = () =>
  modelItems.value.push({
    size_id: "",
    qty: 0,
    variant: "",
    price_store: 0,
    price_grosir: 0,
  });

const removeItem = (i: number) => modelItems.value.splice(i, 1);
</script>
