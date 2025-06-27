<template>
  <Head title="Edit Product Prices" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-6">
      <!-- Header -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <Label for="product_id">Produk</Label>
          <Vue3Select
            v-model="form.product_id"
            :options="searchResults"
            label="name"
            value="id"
            :onSearch="searchProducts"
            placeholder="Search a product"
            :disabled="true"
          />
        </div>
        <div>
          <Label for="effective_date">Tanggal Berlaku</Label>
          <Input type="date" v-model="form.effective_date" id="effective_date" @input="clearError('effective_date')" />
          <p v-if="errors.effective_date" class="text-red-600 text-sm mt-1">{{ errors.effective_date }}</p>
        </div>
        <div>
          <Label for="cost_price">Harga Pokok</Label>
          <Input type="number" 
          v-model.number="form.cost_price" 
          id="cost_price"
           @input="clearError('cost_price')" />
          <p v-if="errors.cost_price" class="text-red-600 text-sm mt-1">{{ errors.cost_price }}</p>
        </div>
      </div>

      <!-- Detail Harga -->
      <div class="border rounded p-4">
        <div class="font-semibold text-lg mb-4">Detail Harga</div>

        
    <Table>
      <TableHeader>
        <TableRow>
            <TableHead>Jenis Harga</TableHead>
            <TableHead>Ukuran</TableHead>
            <TableHead>Harga</TableHead>
            <TableHead>Discount</TableHead>
            <TableHead>Harga Jual</TableHead>
            <TableHead>Actions</TableHead>
        </TableRow>
      </TableHeader>

      <TableBody>
        <TableRow
          v-for="(detail, index) in form.product_price"
          :key="index"
          class="hover:bg-gray-50"
        >
          <TableCell>
            <select
              v-model.number="detail.price_type_id"
              :id="`price_type_id_${index}`"
              class="w-full border rounded p-1"
              @change="clearError(`product_price.${index}.price_type_id`)"
            >
              <option value="" disabled>Pilih jenis harga</option>
              <option v-for="type in priceTypes" :key="type.id" :value="type.id">
                {{ type.name }}
              </option>
            </select>
            <p
              v-if="errors[`product_price.${index}.price_type_id`]"
              class="text-red-600 text-xs mt-1"
            >
              {{ errors[`product_price.${index}.price_type_id`] }}
            </p>
          </TableCell>


          <TableCell>
            <select
              v-model.number="detail.size_id"
              :id="`size_id_${index}`"
              class="w-full border rounded p-1"
              @change="clearError(`product_price.${index}.size_id`)"
            >
              <option value="" disabled>Pilih ukuran</option>
              <option v-for="size in sizes" :key="size.id" :value="size.id">
                {{ size.name }}
              </option>
            </select>
            <p
              v-if="errors[`product_price.${index}.size_id`]"
              class="text-red-600 text-xs mt-1"
            >
              {{ errors[`product_price.${index}.size_id`] }}
            </p>
          </TableCell>

          <TableCell>
            <Input
              type="number"
              v-model.number="detail.price"
              :id="`price_${index}`"
              @input="clearError(`product_price.${index}.price`)"
              class="w-full"
            />
            <p
              v-if="errors[`product_price.${index}.price`]"
              class="text-red-600 text-xs mt-1"
            >
              {{ errors[`product_price.${index}.price`] }}
            </p>
          </TableCell>

           <TableCell>
                <Input type="number" v-model.number="detail.discount" :id="`discount_${index}`"
                  @input="clearError(`product_price.${index}.discount`)" class="w-full" />
                <p v-if="errors[`product_price.${index}.discount`]" class="text-red-600 text-xs mt-1">
                  {{ errors[`product_price.${index}.discount`] }}
                </p>
              </TableCell>
             <TableCell>
                <Input type="number" readonly v-model.number="detail.price_sell" :id="`price_sell_${index}`"
                  @input="clearError(`product_price.${index}.price_sell`)" class="w-full" />
                <p v-if="errors[`product_price.${index}.price_sell`]" class="text-red-600 text-xs mt-1">
                  {{ errors[`product_price.${index}.price_sell`] }}
                </p>
              </TableCell>

          <TableCell class="text-center">
            <Button variant="ghost" size="icon"
              @click="removeDetailRow(index)"
            >
              <Trash2 class="h-4 w-4" />
            </Button>
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>

        <Button @click="addDetailRow" variant="outline" class="bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">Add</Button>
      </div>

      <!-- Submit -->
      <div class="flex justify-end gap-2 mt-4">
        <Button @click="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600" :disabled="form.processing">Update</Button>
        <Button
          @click="router.visit(`/product-prices`)"
          type="button"
          class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600"
        >
          Cancel
        </Button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { useForm, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/composables/useToast'
import axios from 'axios'
import Vue3Select from 'vue3-select'
import 'vue3-select/dist/vue3-select.css'
import { useProductPriceStore } from '@/stores/useProductPriceStore'
import { Head } from '@inertiajs/inertia-vue3' // IMPORT Head component
import { Trash2 } from 'lucide-vue-next'

const toast = useToast()

const props = defineProps<{
  id: number;
}>()

const breadcrumbs = [
  { title: 'Edit Product Prices', href: `/product-prices/${props.id}/edit` }
]

const errors = reactive<Record<string, string>>({})
const searchResults = ref<Array<{ id: number; name: string }>>([])
const priceTypes = ref<{ id: number; name: string }[]>([])
const uoms = ref<{ id: number; name: string }[]>([])
const sizes = ref<{ id: number; name: string }[]>([])

const form = useForm({
  product_id: undefined as { id: number; name: string } | undefined,
  effective_date: new Date().toISOString().split('T')[0],
  cost_price: 0,
  product_price: [] as {
    price_type_id: number | undefined
    discount: number | 0
    size_id: string | "",
    price: number,
    price_sell: number | 0,
  }[],
})

const productPriceStore = useProductPriceStore()

watch(() => form.product_price, (newVal) => {
  newVal.forEach(detail => {
    detail.price_sell = detail.price - detail.discount;
  });
}, { deep: true, immediate: true });

onMounted(async () => {
  await fetchDropdowns()
  await fetchInitialData()
})

const fetchDropdowns = async () => {
  try {
    const [priceTypeRes, uomRes, sizeRes] = await Promise.all([
      axios.get('/api/price-types'),
      axios.get('/api/uoms'),
      axios.get('/api/sizes'),
    ])
    priceTypes.value = priceTypeRes.data.data
    uoms.value = uomRes.data.data
    sizes.value = sizeRes.data.data
  } catch (error) {
    toast.error('Gagal mengambil data dropdown')
    console.error(error)
  }
}

const fetchInitialData = async () => {
  try {
    const data = await productPriceStore.fetchProductPriceById(props.id)
    console.log('Fetched data:', data) // debug output
    
    if (!data) {
      toast.error('Data produk tidak ditemukan')
      return
    }

    form.product_id = data.product ?? null
    form.effective_date = data.effective_date
        ? new Date(data.effective_date).toISOString().slice(0, 10)
        : ''
            if (!form.effective_date) {
            form.effective_date = new Date().toISOString().slice(0, 10)
            }
            
    form.cost_price = data.cost_price ?? 0
    form.product_price = data.price_types ?? []
    searchResults.value = data.product ? [data.product] : []
  } catch (error) {
    toast.error('Gagal memuat data')
    console.error(error)
  }
}

const searchProducts = async (search: string) => {
  if (search.length < 2) {
    searchResults.value = []
    return
  }
  try {
    const res = await axios.get('/api/products', { params: { search } })
    searchResults.value = res.data.data
  } catch (error) {
    console.error('Search error:', error)
    toast.error('Failed to search products')
  }
}

const removeDetailRow = (index: number) => {
  if (form.product_price.length > 1) {
    form.product_price.splice(index, 1)
  } else {
    toast.error('Setidaknya harus ada satu baris harga')
  }
}

const addDetailRow = () => {
  form.product_price.push({
    price_type_id: undefined,
    size_id: "",
    price: 0,
    discount: 0,
    price_sell: 0, // Add this line
  })
}

const clearError = (field: string) => {
  Object.keys(errors).forEach((key) => {
    if (key === field || key.startsWith(field + '.')) {
      delete errors[key]
    }
  })
}

const submit = async () => {
  if (!form.product_id) {
    toast.error("Product must be selected")
    return
  }

  for (const [index, item] of form.product_price.entries()) {
    if (!item.price_type_id || !item.size_id) {
      toast.error(`Lengkapi semua field pada baris harga ke-${index + 1}`)
      return
    }
  }

  const productId = typeof form.product_id === 'object' ? form.product_id.id : form.product_id

  const priceTypes =  form.product_price.map(item => ({
        price_id: props.id,
        product_id: productId,
        price_type_id: item.price_type_id!,
        size_id: item.size_id,
        price: item.price,
        discount: item.discount,
        price_sell: (item.price ?? 0) - (item.discount ?? 0),
      }))

  console.log('Submitting data:', {
    product_id: productId,
    effective_date: form.effective_date,
    cost_price: form.cost_price,
    price_types: priceTypes,
  });

  try {
    await productPriceStore.updateProductPrice(props.id, {
      product_id: productId,
      effective_date: form.effective_date,
      cost_price: form.cost_price,
      price_types: priceTypes,
    })
    toast.success('Data updated successfully')
    router.visit('/product-prices')
  } catch (error: any) {
    if (error.response?.status === 422) {
      Object.assign(errors, error.response.data.errors)
      toast.error('Periksa kesalahan input dan coba lagi')
    } else {
      console.error('Submit error:', error)
      toast.error('Gagal memperbarui data')
    }
  }
}
</script>
