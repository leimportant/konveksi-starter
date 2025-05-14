<template>
    <Head title="Product Prices" />
    <AppLayout>
      <div class="px-4 py-6">
        <div class="flex justify-between items-center mb-6">
          <Button @click="openCreateModal">
            <Plus class="h-4 w-4" />
            Add Product Price
          </Button>
        </div>
  
        <div class="overflow-x-auto rounded-md border">
          <Table>
            <TableHeader>
              <TableRow class="bg-gray-100">
                <TableHead>Product</TableHead>
                <TableHead>Price Type</TableHead>
                <TableHead>Price</TableHead>
                <TableHead>Effective Date</TableHead>
                <TableHead>Status</TableHead>
                <TableHead class="w-24">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="productPrice in productPrices" :key="productPrice.id">
                <TableCell>{{ productPrice.product?.name || productPrice.product_id }}</TableCell>
                <TableCell>{{ productPrice.price_type?.name || productPrice.price_type_id }}</TableCell>
                <TableCell>{{ productPrice.price }}</TableCell>
                <TableCell>{{ formatDate(productPrice.effective_date) }}</TableCell>
                <TableCell>
                  <Badge :variant="productPrice.is_active ? 'default' : 'secondary'">
                    {{ productPrice.is_active ? 'Active' : 'Inactive' }}
                  </Badge>
                </TableCell>
                <TableCell class="flex gap-2">
                  <Button variant="ghost" size="icon" @click="handleEdit(productPrice)">
                    <Edit class="h-4 w-4" />
                  </Button>
                  <Button variant="ghost" size="icon" @click="handleDelete(productPrice.id)">
                    <Trash2 class="h-4 w-4" />
                  </Button>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
  
        <!-- Modal -->
        <Dialog v-model:open="showModal">
          <DialogContent>
            <DialogHeader>
              <DialogTitle>{{ isEditMode ? 'Edit' : 'Create' }} Product Price</DialogTitle>
            </DialogHeader>
            <div class="space-y-4">
              <div>
                <Label>Product</Label>
                <select v-model="form.product_id" class="input w-full">
                  <option disabled value="">Select Product</option>
                  <option v-for="product in products" :key="product.id" :value="product.id">
                    {{ product.name }}
                  </option>
                </select>
                <p v-if="form.errors.product_id" class="text-red-500 text-xs mt-1">{{ form.errors.product_id }}</p>
              </div>
              <div>
                <Label>Price Type</Label>
                <select v-model="form.price_type_id" class="input w-full">
                  <option disabled value="">Select Price Type</option>
                  <option v-for="type in priceTypes" :key="type.id" :value="type.id">
                    {{ type.name }}
                  </option>
                </select>
                <p v-if="form.errors.price_type_id" class="text-red-500 text-xs mt-1">{{ form.errors.price_type_id }}</p>
              </div>
              <div>
                <Label>Price</Label>
                <Input v-model="form.price" type="number" />
                <p v-if="form.errors.price" class="text-red-500 text-xs mt-1">{{ form.errors.price }}</p>
              </div>
              <div>
                <Label>Effective Date</Label>
                <Input v-model="form.effective_date" type="date" />
              </div>
              <div class="flex items-center space-x-2">
                <Checkbox id="is_active" v-model:checked="form.is_active" />
                <Label for="is_active">Active</Label>
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" @click="closeModal">Cancel</Button>
              <Button :disabled="isSaving" @click="handleSave">
                <span v-if="isSaving">Saving...</span>
                <span v-else>Save</span>
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>
    </AppLayout>
  </template>
  
  <script setup lang="ts">
  import AppLayout from '@/layouts/AppLayout.vue'
  import { Head, useForm } from '@inertiajs/vue3'
  import { ref, onMounted } from 'vue'
  import { Button } from '@/components/ui/button'
  import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
  import { Edit, Trash2, Plus } from 'lucide-vue-next'
  import { storeToRefs } from 'pinia'
  import { useToast } from '@/composables/useToast'
  import { Checkbox } from '@/components/ui/checkbox'
  import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
  import { Label } from '@/components/ui/label'
  import { useProductPriceStore } from '@/stores/useProductPriceStore'
  
  const showModal = ref(false) // Tetap gunakan ini sebagai satu-satunya variabel modal
  const isEditMode = ref(false)
  const isSaving = ref(false)
  const editingId = ref<number | null>(null)
  
  const store = useProductPriceStore()
  const { productPrices, products, priceTypes } = storeToRefs(store)
  const toast = useToast()
  
  const form = useForm({
    product_id: '',
    price_type_id: '',
    price: '',
    effective_date: new Date().toISOString().split('T')[0],
    is_active: true,
    errors: {} as Record<string, string>
  })
  
  onMounted(async () => {
    await store.fetchAll()
  })
  
  const formatDate = (dateString: string) => new Date(dateString).toLocaleDateString()
  
  const resetForm = () => {
    form.reset()
    editingId.value = null
    isEditMode.value = false
    form.errors = {}
  }
  
  const openCreateModal = () => {
    resetForm()
    isEditMode.value = false
    showModal.value = true
  }
  
  const handleEdit = (productPrice: any) => {
    isEditMode.value = true
    editingId.value = productPrice.id
    Object.assign(form, {
      product_id: productPrice.product_id,
      price_type_id: productPrice.price_type_id,
      price: productPrice.price,
      effective_date: productPrice.effective_date,
      is_active: productPrice.is_active
    })
    showModal.value = true
  }
  
  const closeModal = () => {
    resetForm()
    showModal.value = false
  }
  
  const handleDelete = async (id: number) => {
    try {
      await store.deleteProductPrice(id)
      toast.success("Product price deleted.")
    } catch {
      toast.error("Failed to delete.")
    }
  }
  
  const handleSave = async () => {
    form.errors = {}
  
    if (!form.product_id || !form.price_type_id || !form.price) {
      return toast.error("Product, Price Type and Price are required.")
    }
  
    isSaving.value = true
    try {
      if (isEditMode.value && editingId.value) {
        await store.updateProductPrice(editingId.value, {
          product_id: Number(form.product_id),
          price_type_id: Number(form.price_type_id),
          price: Number(form.price),
          effective_date: form.effective_date,
          is_active: form.is_active
        })
        toast.success("Product price updated.")
      } else {
        await store.createProductPrice({
          product_id: Number(form.product_id),
          price_type_id: Number(form.price_type_id),
          price: Number(form.price),
          effective_date: form.effective_date,
          is_active: form.is_active
        })
        toast.success("Product price created.")
      }
      closeModal()
    } catch (error: any) {
      const errors = error?.response?.data?.errors
      if (errors) {
        Object.keys(errors).forEach(key => {
          form.errors[key] = errors[key][0]
        })
      }
      toast.error("Failed to save.")
    } finally {
      isSaving.value = false
    }
  }
  </script>
  