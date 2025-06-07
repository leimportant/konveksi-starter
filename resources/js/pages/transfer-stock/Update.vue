<template>
     <Head title="Transfer Stock Management" />
     <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-6">

      <!-- Header: Pilih Lokasi dan Sloc -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Lokasi Asal -->
        <div>
          <Label for="location_id">Lokasi Asal</Label>
          <select
            v-model="form.location_id"
            id="location_id"
            class="w-full border rounded p-2"
            @change="onLocationOrSlocChange"
          >
            <option disabled value="">Pilih lokasi</option>
            <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
          </select>
        </div>

        <!-- Lokasi Tujuan -->
        <div>
          <Label for="location_destination_id">Lokasi Tujuan</Label>
          <select
            v-model="form.location_destination_id"
            id="location_destination_id"
            class="w-full border rounded p-2"
          >
            <option disabled value="">Pilih lokasi</option>
            <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
          </select>
        </div>

        <!-- Sloc -->
        <div>
          <Label for="sloc_id">Sloc</Label>
          <select
            v-model="form.sloc_id"
            id="sloc_id"
            class="w-full border rounded p-2"
            @change="onLocationOrSlocChange"
          >
            <option disabled value="">Pilih sloc</option>
            <option v-for="sloc in slocs" :key="sloc.id" :value="sloc.id">{{ sloc.name }}</option>
          </select>
        </div>
      </div>

      <!-- Detail Barang -->
      <div class="border rounded p-4">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-semibold">Detail Barang</h2>
          <Button variant="outline" @click="openDialog" :disabled="!form.location_id || !form.sloc_id">
            Tambah Baris
          </Button>
        </div>

        <!-- Transfer Detail -->

        <div class="grid grid-cols-1 sm:grid-cols-5 gap-4 mb-4 items-end">
          <Label>Produk</Label>
          <Label>Size</Label>
          <Label>UOM</Label>
          <Label>Qty</Label>
          <Label>Action</Label>
        </div>

        <div
          v-for="(detail, index) in form.transfer_detail"
          :key="index"
          class="grid grid-cols-1 sm:grid-cols-5 gap-4 mb-4 items-end"
        >
          <div>
            <Input type="text" :value="getProductName(detail.product_id)" readonly />
          </div>
          <div>
            <Input type="text" v-model="detail.size_id" readonly />
          </div>
          <div>
            <Input type="text" v-model="detail.uom_id" readonly />
          </div>
          <div>
            <Input type="number" min="1" v-model="detail.qty" />
          </div>
          <div class="pt-6 text-center">
            <Button variant="destructive" size="sm" @click="removeItem(index)">
              <Trash class="h-4 w-4"/>
            </Button>
          </div>
        </div>
      </div>

      <!-- Aksi -->
      <div class="flex justify-end gap-2">
        <Button @click="submit">Simpan</Button>
        <Button variant="outline" @click="resetForm">Batal</Button>
      </div>
    </div>

    <!-- Dialog Modal -->
    <div
      v-if="dialogOpen"
      class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50"
    >
      <div class="bg-white rounded-lg shadow-lg w-11/12 max-w-4xl p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Pilih Produk dari Inventory</h3>
          <button @click="dialogOpen = false" class="text-2xl font-bold text-gray-600 hover:text-gray-900">×</button>
        </div>

        <div v-if="inventoryStore.loading" class="text-center py-10">Loading inventory...</div>
        <div v-else>

          <Table>
          <TableHeader>
            <TableRow class="bg-gray-100">
              <TableHead>Product</TableHead>
              <TableHead>Size</TableHead>
              <TableHead>UOM</TableHead>
              <TableHead>Stock</TableHead>
              <TableHead>Action</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow  v-for="item in inventoryStore.inventory"
            :key="item.id">
               <TableCell>{{ item.product?.name }}</TableCell>
                <TableCell>{{ item.size_id }}</TableCell>
                <TableCell>{{ item.uom_id }}</TableCell>
                <TableCell>{{ item.qty }}</TableCell>
              <TableCell>
                <button
                    @click="selectInventoryItem(item)"
                    class="text-blue-600 hover:underline"
                  >
                    Pilih
                  </button>         
              </TableCell>

            </TableRow>
          </TableBody>
        </Table>

          
           
        </div>
      </div>
    </div>
  </AppLayout>
</template>


<script setup lang="ts">
import { reactive, onMounted, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import axios from 'axios'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/composables/useToast'
import { router } from '@inertiajs/vue3'
import { useTransferStockStore } from '@/stores/useTransferStockStore'
import { useInventoryStore } from '@/stores/useInventoryStore'
import { Trash } from 'lucide-vue-next';

const breadcrumbs = [{ title: 'Transfer Stock', href: '/transfer-stock' }];
const toast = useToast()
const store = useTransferStockStore()
const inventoryStore = useInventoryStore()

const form = useForm({
  location_id: undefined as number | undefined,
  location_destination_id: undefined as number | undefined,
  sloc_id: undefined as number | undefined,
  transfer_detail: [] as {
    product_id: number | null
    size_id: string
    uom_id: string
    qty: number
  }[],
})

const errors = reactive<Record<string, string>>({})

const locations = reactive<any[]>([])
const slocs = reactive<any[]>([])
const products = reactive<any[]>([])
// const uoms = reactive<any[]>([])

const dialogOpen = ref(false)

const props = defineProps<{
  id: string
}>()

onMounted(async () => {
  try {

  	await store.fetchTransferByID(props.id)
    populateForm()

  	   
    const [locRes, slocRes, prodRes] = await Promise.all([
      axios.get('/api/locations'),
      axios.get('/api/slocs'),
      axios.get('/api/products'),
      // axios.get('/api/uoms'),
    ])
    locations.push(...locRes.data.data)
    slocs.push(...slocRes.data.data)
    products.push(...prodRes.data.data)
    // uoms.push(...uomRes.data.data)
  } catch (err) {
    console.error(err)
    toast.error('Gagal memuat data')
  }
})


const openDialog = async () => {
  if (!form.location_id || !form.sloc_id) {
    toast.error('Pilih lokasi dan sloc terlebih dahulu')
    return
  }

  try {
    await inventoryStore.fetchInventory()
    dialogOpen.value = true
  } catch (err) {
    console.error(err)
    toast.error('Gagal memuat inventory')
  }
}


const selectInventoryItem = (item: any) => {
  // Cek apakah sudah ada di detail
  const exists = form.transfer_detail.find(
    d => d.product_id === item.product.id && d.size_id === item.size_id && d.uom_id === item.uom.id
  )
  if (exists) {
    toast.error('Produk ini sudah ada di daftar detail')
    return
  }

  form.transfer_detail.push({
    product_id: item.product?.id,
    size_id: item.size_id,
    uom_id: item.uom_id,
    qty: 1,
  })
  dialogOpen.value = false
}

const getProductName = (product_id: number | null) => {
  const prod = products.find(p => p.id === product_id)
  return prod ? prod.name : ''
}

const clearError = (field: string) => {
  delete errors[field]
}

const resetForm = () => {
  router.visit('/transfer-stock')
}

const removeItem = (index: number) => {
  form.transfer_detail.splice(index, 1)
}


const submit = async () => {
  try {
    if (!form.location_id || !form.location_destination_id || !form.sloc_id) {
      toast.error('Semua lokasi dan sloc wajib diisi')
      return
    }

    await store.updateTransfer(String(props.id), {
      // Pastikan form sudah terisi dengan benar
      id: props.id,
      location_id: form.location_id,
      location_destination_id: form.location_destination_id,
      sloc_id: form.sloc_id,
      transfer_detail: form.transfer_detail.map(d => ({
        product_id: d.product_id!,
        size_id: d.size_id,
        uom_id: d.uom_id,
        qty: d.qty,
      })),
    })

    toast.success('Transfer berhasil disimpan')
    router.visit('/transfer-stock')
  } catch (error: any) {
    if (error.response?.status === 422) {
      Object.assign(errors, error.response.data.errors)
    } else {
      toast.error('Gagal menyimpan transfer')
    }
  }
}

const populateForm = () => {
  const transfer = store.transfer
  if (transfer) {
    form.location_id = transfer.location_id ?? undefined
    form.location_destination_id = transfer.location_destination_id ?? undefined
    form.sloc_id = transfer.sloc_id ?? undefined

    const details = transfer.transfer_detail
      ? transfer.transfer_detail.map(d => ({
          product_id: d.product_id ?? 0,
          size_id: d.size_id ?? '',
          uom_id: d.uom_id ?? '',
          qty: d.qty,
        }))
      : []

    form.transfer_detail.splice(0, form.transfer_detail.length, ...details)
  }
}


const onLocationOrSlocChange = () => {
  clearError('location_id')
  clearError('sloc_id')

  // Reset detail karena inventory berubah
  form.transfer_detail.splice(0, form.transfer_detail.length)
}
</script>
