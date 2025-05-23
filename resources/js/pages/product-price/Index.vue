<template>
  <Head title="Product Prices" />
  <AppLayout>
    <div class="container mx-auto py-6">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold">Product Prices</h1>
        <Button @click="openCreateModal">
          <Plus class="w-4 h-4 mr-2" />
          Add New Price
        </Button>
      </div>

      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Product</TableHead>
            <TableHead>Cost Price</TableHead>
            <TableHead>Effective Date</TableHead>
            <TableHead>End Date</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="price in productPrices" :key="price.id">
            <TableCell>{{ price.product?.name }}</TableCell>
            <TableCell>{{ formatCurrency(price.cost_price) }}</TableCell>
            <TableCell>{{ formatDate(price.effective_date) }}</TableCell>
            <TableCell>{{ formatDate(price.end_date) }}</TableCell>
            <TableCell>
              <Badge2 :variant="price.is_active ? 'success' : 'destructive'">
                {{ price.is_active ? 'Active' : 'Inactive' }}
              </Badge2>
            </TableCell>
            <TableCell>
              <div class="flex space-x-2">
                <Button variant="ghost" size="icon" @click="handleEdit(price)">
                  <Edit class="w-4 h-4" />
                </Button>
                <Button variant="ghost" size="icon" @click="handleDelete(price.id)">
                  <Trash2 class="w-4 h-4" />
                </Button>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import { onMounted } from 'vue'
import { Button } from '@/components/ui/button'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Badge2 } from '@/components/ui/badge2'
import { Edit, Trash2, Plus } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'
import { useProductPriceStore } from '@/stores/useProductPriceStore'
import { storeToRefs } from 'pinia'
import { router } from '@inertiajs/vue3'

const store = useProductPriceStore()
const { productPrices } = storeToRefs(store)
const toast = useToast()

onMounted(async () => {
  await store.fetchAll()
})

const formatDate = (dateString: string) => dateString ? new Date(dateString).toLocaleDateString() : '-'
const formatCurrency = (amount: number) => amount ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(amount) : '-'

const handleEdit = (price: any) => {
  router.get(route('product-price.edit', price.id))
}

const handleDelete = async (id: number) => {
  if (confirm('Are you sure you want to delete this price?')) {
    try {
      await store.deleteProductPrice(id)
      toast.success('Product price deleted successfully')
    } catch (error) {
      console.error('Failed to delete product price:', error)
      toast.error('Failed to delete product price')
    }
  }
}

const openCreateModal = () => {
  router.get(route('product-price.create'))
}
</script>
  