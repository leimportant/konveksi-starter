<template>
  <AppLayout title="Tambah Transfer Stock">
    <div class="p-4 space-y-4 text-sm">
      <!-- Header -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
        <div>
          <Label for="location_id">Lokasi Asal</Label>
          <select
            v-model="form.location_id"
            id="location_id"
            class="w-full border rounded p-1"
            @change="onLocationOrSlocChange"
          >
            <option disabled value="">Pilih lokasi</option>
            <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
          </select>
          <p v-if="errors.location_id" class="text-red-600 text-xs mt-1">{{ errors.location_id }}</p>
        </div>

        <div>
          <Label for="location_destination_id">Lokasi Tujuan</Label>
          <select
            v-model="form.location_destination_id"
            id="location_destination_id"
            class="w-full border rounded p-1"
            @change="clearError('location_destination_id')"
          >
            <option disabled value="">Pilih lokasi</option>
            <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
          </select>
          <p v-if="errors.location_destination_id" class="text-red-600 text-xs mt-1">{{ errors.location_destination_id }}</p>
        </div>

        <div>
          <Label for="sloc_id">Sloc</Label>
          <select
            v-model="form.sloc_id"
            id="sloc_id"
            class="w-full border rounded p-1"
            @change="onLocationOrSlocChange"
          >
            <option disabled value="">Pilih sloc</option>
            <option v-for="sloc in slocs" :key="sloc.id" :value="sloc.id">{{ sloc.name }}</option>
          </select>
          <p v-if="errors.sloc_id" class="text-red-600 text-xs mt-1">{{ errors.sloc_id }}</p>
        </div>
      </div>
<!-- Detail Barang -->
<div class="border rounded-lg p-4 bg-white shadow-sm">
  <div class="flex justify-between items-center mb-4">
    <h2 class="font-semibold text-gray-800 text-base">Detail Barang</h2>
    <Button
      variant="outline"
      size="sm"
      class="text-xs"
      @click="openDialog"
      :disabled="!form.location_id || !form.sloc_id"
    >
      Tambah Baris
    </Button>
  </div>

  <Table>
    <TableHeader>
      <TableRow>
        <TableHead class="text-xs">Produk</TableHead>
        <TableHead class="text-xs">Ukuran</TableHead>
        <TableHead class="text-xs">UOM</TableHead>
        <TableHead class="text-xs text-center">Qty</TableHead>
        <TableHead class="text-xs text-center">Aksi</TableHead>
      </TableRow>
    </TableHeader>

    <TableBody>
      <TableRow v-for="(detail, index) in form.transfer_detail" :key="index">
        <TableCell>
          <input
            type="text"
            :value="detail.product_name"
            readonly
            class="w-full bg-gray-100 text-sm border rounded px-2 py-1"
          />
        </TableCell>

        <TableCell>
          <input
            type="text"
            :value="detail.size_id"
            readonly
            class="w-full bg-gray-100 text-sm border rounded px-2 py-1"
          />
        </TableCell>

        <TableCell>
          <input
            type="text"
            :value="detail.uom_id"
            readonly
            class="w-full bg-gray-100 text-sm border rounded px-2 py-1"
          />
        </TableCell>

        <TableCell class="text-center">
          <Input
            type="number"
            min="1"
            step="1"
            v-model="detail.qty"
            class="w-full text-sm px-2 py-1 border rounded"
          />
        </TableCell>

        <TableCell class="text-center">
          <Button
            variant="destructive"
            size="sm"
            class="text-xs"
            @click="removeItem(index)"
          >
            <Trash class="h-4 w-4" />
          </Button>
        </TableCell>
      </TableRow>
    </TableBody>
  </Table>
</div>

      <!-- Submit -->
      <div class="flex justify-end gap-2">
        <Button @click="submit" :disabled="form.processing" class="bg-indigo-600 text-white text-sm px-4 py-2 rounded hover:bg-indigo-700">Simpan</Button>
        <Button @click="resetForm" variant="outline" class="text-sm px-4 py-2">Batal</Button>
      </div>
    </div>

    <!-- Dialog Modal -->
    <div v-if="dialogOpen" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
      <div class="bg-white rounded-lg shadow-lg w-11/12 max-w-4xl p-4 space-y-4 text-sm">
        <div class="flex justify-between items-center">
          <h3 class="text-base font-semibold">Pilih Produk dari Inventory</h3>
          <button @click="dialogOpen = false" class="text-gray-600 hover:text-gray-900 text-lg">&times;</button>
        </div>

        <div v-if="inventoryStore.loading" class="text-center py-6">Loading inventory...</div>
       
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
            <td class="px-3 py-2 font-medium text-gray-800">{{ item.product_name }}</td>
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
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import axios from 'axios'
import { useToast } from '@/composables/useToast'
import { router } from '@inertiajs/vue3'
import { useTransferStockStore } from '@/stores/useTransferStockStore'
import { useInventoryStore } from '@/stores/useInventoryStore'
import { Trash } from 'lucide-vue-next';
import debounce from 'lodash-es/debounce';

const filters = reactive({
  productName: ''
});

watch(() => filters.productName, debounce((newVal) => {
  inventoryStore.setFilter('productName', newVal);
  inventoryStore.fetchInventory();
}, 500));

const toast = useToast()
const inventoryStore = useInventoryStore();
const store = useTransferStockStore()

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

const dialogOpen = ref(false)

onMounted(async () => {
  try {
    const [locRes, slocRes, prodRes] = await Promise.all([
      axios.get('/api/locations'),
      axios.get('/api/slocs'),
      axios.get('/api/products'),
    ])
    locations.push(...locRes.data.data)
    slocs.push(...slocRes.data.data)
    products.push(...prodRes.data.data)
  } catch (error: any) {
     if (error.response?.status === 422 && error.response?.data?.errors) {
      errors.value = error.response.data.errors;

      // Iterate and display all error messages
      for (const key in errors) {
        const errorMessages = Array.isArray(errors[key]) ? errors[key] : [errors[key]];
        errorMessages.forEach((errorMsg: string) => {
          toast.error(errorMsg);
        });
      }
    } 
     // Jika ada message dari backend (misal error 400, 500, dll)
      else if (error.response?.data?.message) {
        toast.error(error.response.data.message);
      } else {
          toast.error('Terjadi kesalahan saat menyimpan data');
      }
    
  }
})
watch(() => form.location_id, () => {
  clearError('location_id')
  onLocationOrSlocChange()
})

const openDialog = async () => {
  if (!form.location_id || !form.sloc_id) {
    toast.error('Pilih lokasi dan sloc terlebih dahulu')
    return
  }
  inventoryStore.filters.productName = '';
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
    d => d.product_id === item.product_id && d.size_id === item.size_id && d.uom_id === item.uom.id
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

// const getProductName = (product_id: number | null) => {
//   const prod = products.find(p => p.id === product_id)
//   return prod ? prod.name : ''
// }

const clearError = (field: string) => {
  delete errors[field]
}

const resetForm = () => {
  router.visit('/transfer-stock')
}

const submit = async () => {
  try {
    if (!form.location_id || !form.location_destination_id || !form.sloc_id) {
      toast.error('Semua lokasi dan sloc wajib diisi')
      return
    }

    await store.createTransfer({
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

function removeItem(index: number) {
  form.transfer_detail.splice(index, 1)
}

const onLocationOrSlocChange = () => {
  clearError('location_id')
  clearError('sloc_id')

  // Reset detail karena inventory berubah
  form.transfer_detail.splice(0, form.transfer_detail.length)
}
</script>
