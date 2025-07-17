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
  <div class="border rounded-lg p-4 shadow-sm bg-white">
    <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
      <h2 class="text-lg font-semibold text-gray-800">Detail Barang</h2>
      <Button
        variant="outline"
        @click="openDialog"
        :disabled="!form.location_id || !form.sloc_id"
        class="text-sm"
      >
        Tambah Baris
      </Button>
    </div>

    <div class="overflow-x-auto">
      <Table class="min-w-full text-sm">
        <TableHeader>
          <TableRow class="bg-gray-100">
            <TableHead class="px-3 py-2">Produk</TableHead>
            <TableHead class="px-3 py-2">Size</TableHead>
            <TableHead class="px-3 py-2">UOM</TableHead>
            <TableHead class="px-3 py-2">Qty</TableHead>
            <TableHead class="px-3 py-2 text-center">Action</TableHead>
          </TableRow>
        </TableHeader>

        <TableBody>
          <TableRow
            v-for="(detail, index) in form.transfer_detail"
            :key="index"
            class="hover:bg-gray-50"
          >
            <TableCell class="px-3 py-2">


               <input
            type="text"
            :value="getProductName(detail.product_id, detail.product_name)"
            readonly
            class="w-full bg-gray-100 text-sm border rounded px-2 py-1"
          />
            </TableCell>
            <TableCell class="px-3 py-2">
              <Input type="text" v-model="detail.size_id" readonly class="w-full" />
            </TableCell>
            <TableCell class="px-3 py-2">
              <Input type="text" v-model="detail.uom_id" readonly class="w-full" />
            </TableCell>
            <TableCell class="px-3 py-2">
              <Input type="number" min="1" v-model="detail.qty" class="w-full" />
            </TableCell>
            <TableCell class="px-3 py-2 text-center">
              <Button variant="destructive" size="sm" @click="removeItem(index)">
                <Trash class="h-4 w-4" />
              </Button>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>
  </div>

      <!-- Aksi -->
      <div class="flex justify-end gap-2">
        <Button @click="submit" class="bg-indigo-600 text-white py-2 h-10 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">Simpan</Button>
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
         <div v-else class="space-y-4">
    <!-- Search Input -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
      <input
        v-model="filters.productName"
        placeholder="Cari Produk"
        :disabled="inventoryStore.loading"
        class="w-full sm:w-64 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
        aria-label="Search"
      />
    </div>

    <!-- Inventory Table -->
    <div class="overflow-x-auto rounded-md border border-gray-200 shadow-sm">
      <table class="min-w-full text-sm text-left whitespace-nowrap">
        <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
          <tr>
            <th class="px-3 py-2">Produk</th>
            <th class="px-3 py-2">Size</th>
            <th class="px-3 py-2">UOM</th>
            <th class="px-3 py-2 text-right">Qty</th>
            <th class="px-3 py-2 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in inventoryStore.inventoryRpt"
            :key="`${item.product_id}-${item.location_id}-${item.sloc_id}-${item.size_id}`"
            class="hover:bg-gray-50 border-t"
          >
            <td class="px-3 py-2 font-medium text-gray-800">{{ item.product_id }} - {{ item.product_name }}</td>
            <td class="px-3 py-2">{{ item.size_id }}</td>
            <td class="px-3 py-2">{{ item.uom_id }}</td>
            <td class="px-3 py-2 text-right">{{ item.qty }}</td>
            <td class="px-3 py-2 text-center">
              <button
                @click="selectInventoryItem(item)"
                class="text-blue-600 hover:text-blue-800 hover:underline transition"
              >
                Pilih
              </button>
            </td>
          </tr>
          <tr v-if="inventoryStore.inventoryRpt.length === 0">
            <td colspan="5" class="px-3 py-4 text-center text-gray-500">Tidak ada inventory ditemukan.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
      </div>
    </div>
  </AppLayout>
</template>


<script setup lang="ts">
import { reactive, onMounted, watch, ref } from 'vue'
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
import debounce from 'lodash-es/debounce';

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
    product_name: string,
    size_id: string
    size_name: string
    uom_id: string
    uom_name: string
    qty: number
  }[],
})

const errors = reactive<Record<string, string>>({})

const locations = reactive<any[]>([])
const slocs = reactive<any[]>([])
const products = reactive<any[]>([])
// const uoms = reactive<any[]>([])

const dialogOpen = ref(false)

const filters = reactive({
  productName: ''
});

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

watch(() => filters.productName, debounce((newVal) => {
  inventoryStore.setFilter('productName', newVal);
  inventoryStore.fetchInventory();
}, 500));

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
    d => d.product_id === item.product_id && d.size_id === item.size_id && d.uom_id === item.uom_id
  )
  if (exists) {
    toast.error('Produk ini sudah ada di daftar detail')
    return
  }

  form.transfer_detail.push({
    product_id: item.product_id,
    product_name: item.product_name,
    size_id: item.size_id,
    size_name: item.size_id,
    uom_id: item.uom_id,
    uom_name: item.uom_id,
    qty: item.qty_available ?? 1,
  })
  dialogOpen.value = false
}

const getProductName = (product_id: number | null, product_name: string) => {
  const prod = products.find(p => p.id === product_id)
  return prod ? prod.name : product_name
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
          product_name: d.product_name ?? '',
          size_id: d.size_id ?? '',
          size_name: d.size_id ?? '',
          uom_id: d.uom_id ?? '',
          uom_name: d.uom_id ?? '',
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
