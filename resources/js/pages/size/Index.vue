<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Edit, Trash2, Plus } from 'lucide-vue-next';
import { useToast } from "@/composables/useToast";
import axios from 'axios';

const toast = useToast();

interface Size {
    id: number;
    name: string;
}

const sizes = ref<Size[]>([]);
const isLoading = ref(true);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const currentSize = ref<Size | null>(null);

const form = useForm({
    name: '',
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Size',
        href: '/sizes',
    },
];

const fetchSizes = async () => {
    try {
        const response = await axios.get('/api/sizes');
        sizes.value = response.data.data;
    } catch (error) {
        console.error('Error fetching Sizes:', error);
    } finally {
        isLoading.value = false;
    }
};

const handleCreate = async () => {
    try {
        if (!form.name) {
            toast.error("Name is required");
            return;
        }

        await axios.post('/api/sizes', {
            name: form.name
        });

        form.reset();
        showCreateModal.value = false;
        await fetchSizes();
        toast.success("Size created successfully");

    } catch (error: any) {
        const nameError = error?.response?.data?.errors?.name;

        if (Array.isArray(nameError) && nameError.length > 0) {
            toast.error(nameError[0]); // e.g., "The name has already been taken."
        } else if (error?.response?.data?.message) {
            toast.error(error.response.data.message); // fallback to general message
        } else {
            console.error('Unexpected error:', error);
            toast.error("Failed to create Size");
        }
    }
};


const handleEdit = (size: Size) => {
    currentSize.value = size;
    form.name = size.name;
    showEditModal.value = true;
};

const handleUpdate = async () => {
    if (!currentSize.value) return;
    
    try {
        if (!form.name) {
            toast.error("Name is required");
            return;
        }

        await axios.put(`/api/sizes/${currentSize.value.id}`, {
            name: form.name
        });
        form.reset();
        showEditModal.value = false;
        currentSize.value = null;
        await fetchSizes();
        toast.success("Size updated successfully");
    } catch (error: any) {
        if (error.response?.data?.errors?.name) {
            toast.error(error.response.data.errors.name[0]);
        } else {
            console.error('Error updating Size:', error);
            toast.error("Failed to update Size");
        }
    }
};

const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to delete this Size?')) return;
    
    try {
        await axios.delete(`/api/sizes/${id}`);
        toast.success("Size deleted successfully");
        await fetchSizes();
    } catch (error) {
        console.error('Error deleting Size:', error);
        toast.error("Failed to delete Size");
    }
};

onMounted(() => {
    fetchSizes();
});
</script>

<template>
    <Head title="Size Management" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold">Size</h1>
                <Button @click="showCreateModal = true">
                    <Plus class="h-4 w-4" />
                    Add
                </Button>
            </div>

            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead class="w-24">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="size in sizes" :key="size.id">
                            <TableCell>{{ size.name }}</TableCell>
                            <TableCell class="flex gap-2">
                                <Button variant="ghost" size="icon" @click="handleEdit(size)">
                                    <Edit class="h-4 w-4" />
                                </Button>
                                <Button variant="ghost" size="icon" @click="handleDelete(size.id)">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Create Modal -->
            <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                <div class="bg-white p-6 rounded-lg w-96">
                    <h2 class="text-lg font-semibold mb-4">Add New Size</h2>
                    <form @submit.prevent="handleCreate">
                        <div class="mb-4">
                            <Input v-model="form.name" placeholder="Size Name" required />
                        </div>
                        <div class="flex justify-end gap-2">
                            <Button type="button" variant="outline" @click="showCreateModal = false">Cancel</Button>
                            <Button type="submit">Create</Button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit Modal -->
            <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                <div class="bg-white p-6 rounded-lg w-96">
                    <h2 class="text-lg font-semibold mb-4">Edit Size</h2>
                    <form @submit.prevent="handleUpdate">
                        <div class="mb-4">
                            <Input v-model="form.name" placeholder="Size Name" required />
                        </div>
                        <div class="flex justify-end gap-2">
                            <Button type="button" variant="outline" @click="showEditModal = false">Cancel</Button>
                            <Button type="submit">Update</Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
