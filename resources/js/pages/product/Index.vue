<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Edit, Trash2, Plus } from 'lucide-vue-next';
import { useToast } from "@/composables/useToast";
import { useProductStore } from '@/stores/useProductStore';
import { useCategoryStore } from '@/stores/useCategoryStore';
import { useUomStore } from '@/stores/useUomStore';
import { storeToRefs } from 'pinia';

const toast = useToast();

const showCreateModal = ref(false);
const showEditModal = ref(false);
const currentProduct = ref<{ id: number; name: string; category_id: number; uom_id: string } | null>(null);

const form = useForm({
  name: '',
  category_id: 0,
  uom_id: '' // Change this to an empty string to match the expected type
});

const breadcrumbs = [
  { title: 'Product', href: '/products' }
];

const productStore = useProductStore();
const categoryStore = useCategoryStore();
const { items: products } = storeToRefs(productStore);
const { items: categories } = storeToRefs(categoryStore);

const uomStore = useUomStore();
const { items: uoms } = storeToRefs(uomStore);

onMounted(async () => {
  await Promise.all([productStore.fetchProducts(), categoryStore.fetchCategories(), uomStore.fetchUoms()]);
});

const handleCreate = async () => {
  if (!form.name) return toast.error("Name is required");
  if (!form.category_id) return toast.error("Category is required");
  if (!form.uom_id) return toast.error("UOM is required");

  try {
    await productStore.createProduct({
      name: form.name,
      category_id: Number(form.category_id),
      uom_id: form.uom_id
    });
    toast.success("Product created successfully");
    form.reset();
    showCreateModal.value = false;
  } catch (error: any) {
    const nameError = error?.response?.data?.errors?.name?.[0];
    toast.error(nameError || "Failed to create Product");
  }
};

const handleEdit = (product: { id: number; name: string; category_id: number; uom_id: string }) => {
  currentProduct.value = product;
  form.name = product.name;
  form.category_id = product.category_id;
  form.uom_id = product.uom_id; // Use nullish coalescing to default to 0 if null
  showEditModal.value = true;
};

const handleUpdate = async () => {
  if (!currentProduct.value || !form.name) return toast.error("Name is required");
  if (!form.category_id) return toast.error("Category is required");
  if (!form.uom_id) return toast.error("UOM is required");

  // Check for null before updating
  const productId = currentProduct.value.id;

  try {
    await productStore.updateProduct(productId, {
      name: form.name,
      category_id: form.category_id,
      uom_id: form.uom_id
    });
    toast.success("Product updated successfully");
    form.reset();
    productStore.loaded = false;
    await productStore.fetchProducts();
    showEditModal.value = false;
    currentProduct.value = null;
  } catch (error: any) {
    const nameError = error?.response?.data?.errors?.name?.[0];
    toast.error(nameError || "Failed to update Product");
  }
};

const handleDelete = async (id: number) => {
  if (!confirm('Are you sure you want to delete this Product?')) return;

  try {
    await productStore.deleteProduct(id);
    toast.success("Product deleted successfully");
    productStore.loaded = false;
    await productStore.fetchProducts();
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to delete Product");
  }
};

const getCategoryName = (categoryId: number) => {
  const category = categories.value.find(c => c.id === categoryId);
  return category?.name || 'N/A';
};

const getUomName = (uomId: string) => {
  const uom = uoms.value.find(u => u.id === uomId);
  return uom?.name || 'N/A';
};
</script>

<template>
  <Head title="Product Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="flex justify-between items-center mb-6">
        <Button @click="showCreateModal = true">
          <Plus class="h-4 w-4" />
          Add
        </Button>
      </div>

      <div class="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow class="bg-gray-100">
              <TableHead>Name</TableHead>
              <TableHead>Category</TableHead>
              <TableHead>UOM</TableHead>
              <TableHead class="w-24">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="product in products" :key="product.id">
              <TableCell>{{ product.name }}</TableCell>
              <TableCell>{{ getCategoryName(product.category_id) }}</TableCell>
              <TableCell>{{ getUomName(product.uom_id) }}</TableCell>
              <TableCell class="flex gap-2">
                <Button variant="ghost" size="icon" @click="handleEdit(product)">
                  <Edit class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="icon" @click="handleDelete(product.id)">
                  <Trash2 class="h-4 w-4" />
                </Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <!-- Create Modal -->
      <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg w-96">
          <h2 class="text-lg font-semibold mb-4">Add New Product</h2>
          <form @submit.prevent="handleCreate">
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium mb-1">Category</label>
                <select v-model="form.category_id" class="w-full rounded-md border border-input px-3 py-2" required>
                  <option value="0">Select Category</option>
                  <option v-for="category in categories" :key="category.id" :value="category.id">
                    {{ category.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Name</label>
                <Input v-model="form.name" placeholder="Product Name" required />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">UOM</label>
                <select v-model="form.uom_id" class="w-full rounded-md border border-input px-3 py-2" required>
                  <option value="">Select UOM</option>
                  <option v-for="uom in uoms" :key="uom.id" :value="uom.id">
                    {{ uom.name }}
                  </option>
                </select>
              </div>
            </div>
            <div class="flex justify-end gap-2 mt-4">
              <Button type="button" variant="outline" @click="showCreateModal = false">Cancel</Button>
              <Button type="submit">Create</Button>
            </div>
          </form>
        </div>
      </div>

      <!-- Edit Modal -->
      <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg w-96">
          <h2 class="text-lg font-semibold mb-4">Edit Product</h2>
          <form @submit.prevent="handleUpdate">
            <!-- Edit Modal form content -->
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium mb-1">Category</label>
                <select v-model="form.category_id" class="w-full rounded-md border border-input px-3 py-2" required>
                  <option value="0">Select Category</option>
                  <option v-for="category in categories" :key="category.id" :value="category.id">
                    {{ category.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Name</label>
                <Input v-model="form.name" placeholder="Product Name" required />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">UOM</label>
                <select v-model="form.uom_id" class="w-full rounded-md border border-input px-3 py-2" required>
                  <option value="">Select UOM</option>
                  <option v-for="uom in uoms" :key="uom.id" :value="uom.id">
                    {{ uom.name }}
                  </option>
                </select>
              </div>
            </div>
            <div class="flex justify-end gap-2 mt-4">
              <Button type="button" variant="outline" @click="showEditModal = false">Cancel</Button>
              <Button type="submit">Update</Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
