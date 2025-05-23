<template>
  <AppLayout title="Tambah Harga Produk">
    <div class="p-6 space-y-6">
      <!-- Header -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <Label for="product_id">Produk</Label>
          <select v-model="form.product_id" id="product_id" class="w-full border rounded p-2" @change="clearError('product_id')">
            <option value="" disabled>Pilih produk</option>
            <option v-for="product in products" :key="product.id" :value="product.id">
              {{ product.name }}
            </option>
          </select>
          <p v-if="errors.product_id" class="text-red-600 text-sm mt-1">{{ errors.product_id }}</p>
        </div>
        <div>
          <Label for="effective_date">Tanggal Berlaku</Label>
          <Input type="date" v-model="form.effective_date" id="effective_date" @input="clearError('effective_date')" />
          <p v-if="errors.effective_date" class="text-red-600 text-sm mt-1">{{ errors.effective_date }}</p>
        </div>
        <div>
          <Label for="cost_price">Harga Pokok</Label>
          <Input type="number" v-model="form.cost_price" id="cost_price" @input="clearError('cost_price')" />
          <p v-if="errors.cost_price" class="text-red-600 text-sm mt-1">{{ errors.cost_price }}</p>
        </div>
      </div>

      <!-- Detail Harga -->
      <div class="border rounded p-4">
        <div class="font-semibold text-lg mb-4">Detail Harga</div>

        <div
          v-for="(detail, index) in form.product_price"
          :key="index"
          class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-4 items-end"
        >
          <!-- Jenis Harga -->
          <div>
            <Label :for="`price_type_id_${index}`">Jenis Harga</Label>
            <select
              v-model="detail.price_type_id"
              :id="`price_type_id_${index}`"
              class="w-full border rounded p-2"
              @change="clearError(`product_price.${index}.price_type_id`)"
            >
              <option value="" disabled>Pilih jenis harga</option>
              <option v-for="type in priceTypes" :key="type.id" :value="type.id">
                {{ type.name }}
              </option>
            </select>
            <p v-if="errors[`product_price.${index}.price_type_id`]" class="text-red-600 text-sm mt-1">
              {{ errors[`product_price.${index}.price_type_id`] }}
            </p>
          </div>

          <!-- UOM -->
          <div>
            <Label :for="`uom_id_${index}`">UOM</Label>
            <select
              v-model="detail.uom_id"
              :id="`uom_id_${index}`"
              class="w-full border rounded p-2"
              @change="clearError(`product_price.${index}.uom_id`)"
            >
              <option value="" disabled>Pilih UOM</option>
              <option v-for="uom in uoms" :key="uom.id" :value="uom.id">
                {{ uom.name }}
              </option>
            </select>
            <p v-if="errors[`product_price.${index}.uom_id`]" class="text-red-600 text-sm mt-1">
              {{ errors[`product_price.${index}.uom_id`] }}
            </p>
          </div>

          <!-- Ukuran -->
          <div>
            <Label :for="`size_id_${index}`">Ukuran</Label>
            <select
              v-model="detail.size_id"
              :id="`size_id_${index}`"
              class="w-full border rounded p-2"
              @change="clearError(`product_price.${index}.size_id`)"
            >
              <option value="" disabled>Pilih ukuran</option>
              <option v-for="size in sizes" :key="size.id" :value="size.id">
                {{ size.name }}
              </option>
            </select>
            <p v-if="errors[`product_price.${index}.size_id`]" class="text-red-600 text-sm mt-1">
              {{ errors[`product_price.${index}.size_id`] }}
            </p>
          </div>

          <!-- Harga -->
          <div>
            <Label :for="`price_${index}`">Harga</Label>
            <Input type="number" v-model="detail.price" :id="`price_${index}`" @input="clearError(`product_price.${index}.price`)" />
            <p v-if="errors[`product_price.${index}.price`]" class="text-red-600 text-sm mt-1">
              {{ errors[`product_price.${index}.price`] }}
            </p>
          </div>
        </div>

        <Button @click="addDetailRow" variant="outline" class="mt-2">Add</Button>
      </div>

      <!-- Submit -->
      <div class="flex justify-end">
        <Button @click="submit" :disabled="form.processing">Simpan</Button>
        <Button @click="resetForm" variant="outline" class="ml-2">Cancel</Button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { reactive } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useProductPriceStore } from '@/stores/useProductPriceStore'
import { storeToRefs } from 'pinia'
import { onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast } from "@/composables/useToast";// pastikan ini sesuai toast kamu

const productPriceStore = useProductPriceStore()
const { products, priceTypes, uoms, sizes } = storeToRefs(productPriceStore)

// Reactive errors object
const errors = reactive<Record<string, string>>({})
  
const toast = useToast()

const form = useForm({
  product_id: undefined as number | undefined,
  effective_date: '',
  cost_price: 0,
  product_price: [] as {
    price_type_id: number | undefined
    uom_id: string | undefined
    size_id: string | undefined
    price: number
  }[]
})

onMounted(() => {
  productPriceStore.fetchAll()
  addDetailRow()
})

const addDetailRow = () => {
  form.product_price.push({
    price_type_id: undefined,
    uom_id: undefined,
    size_id: undefined,
    price: 0
  })
}

const clearError = (field: string) => {
  delete errors[field]
}

const resetForm = () => {
  router.visit('/product-prices')
}

const submit = async () => {
  try {
    await productPriceStore.createProductPrice({
      product_id: form.product_id,
      effective_date: form.effective_date,
      cost_price: form.cost_price,
      price_types: form.product_price.map(item => ({
        price_type_id: item.price_type_id,
        uom_id: item.uom_id,
        size_id: item.size_id,
        price: item.price
      }))
    })

    // ✅ Toast sukses
    toast.success("Data created successfully");

    // ✅ Redirect (ubah route sesuai kebutuhan)
    router.visit('/product-prices')
  } catch (error: any) {
    if (error.response?.status === 422) {
      console.log('Validation errors:', error.response.data.errors)
    } else {
      console.error('Failed to create product price:', error)
    }
  }
}
</script>
