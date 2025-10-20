<template>
    <Head title="Uang Makan" />
    <AppLayout>
        <div class="px-4 py-4">
            <!-- Header -->
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <!-- Tombol Tambah -->
                <Button
                    @click="showCreateModal = true"
                    class="flex items-center gap-2 rounded-md bg-indigo-600 px-3 py-2 text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500"
                >
                    <Plus class="h-4 w-4" />
                    Tambah Data
                </Button>

                <!-- Input Search -->
                <div class="min-w-[180px] flex-1 sm:min-w-[240px] md:min-w-[280px]">
                     <Input v-model="filterName" @input="onSearch" placeholder="Cari.." class="w-full text-sm" />
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-md bg-white shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-gray-100">
                            <TableHead>Nama</TableHead>
                            <TableHead>Jumlah (Rp)</TableHead>
                            <TableHead class="w-24 text-center">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="loading">
                            <TableCell colspan="3" class="py-4 text-center text-gray-500">Memuat data...</TableCell>
                        </TableRow>

                        <TableRow v-for="meal in mealAllowances" :key="meal.id">
                            <TableCell>{{ meal.name }}</TableCell>
                            <TableCell>{{ formatPrice(meal.amount) }}</TableCell>
                            <TableCell class="flex justify-center gap-2">
                                <Button variant="ghost" size="icon" @click="handleEdit(meal)">
                                    <Edit class="h-4 w-4" />
                                </Button>
                                <Button variant="ghost" size="icon" @click="handleDelete(meal.id)">
                                    <Trash2 class="h-4 w-4 text-red-500" />
                                </Button>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="!loading && mealAllowances.length === 0">
                            <TableCell colspan="3" class="py-4 text-center italic text-gray-500">Belum ada data</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 flex justify-end space-x-2">
                <button
                    @click="prevPage"
                    :disabled="currentPage === 1 || loading"
                    class="rounded border border-gray-300 px-3 py-1 text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Previous
                </button>

                <template v-for="page in totalPages" :key="page">
                    <button
                        @click="goToPage(page)"
                        :class="[
                            'rounded border px-3 py-1 text-sm',
                            page === currentPage ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-100',
                        ]"
                        :disabled="loading"
                    >
                        {{ page }}
                    </button>
                </template>

                <button
                    @click="nextPage"
                    :disabled="currentPage === totalPages || loading"
                    class="rounded border border-gray-300 px-3 py-1 text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Next
                </button>
            </div>

            <!-- Create Modal -->
            <Dialog v-model:open="showCreateModal">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Tambah Uang Makan</DialogTitle>
                    </DialogHeader>
                    <div class="space-y-4">
                        <div>
                            <Label>Nama</Label>
                            <select
                                v-model="form.name"
                                class="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            >
                                <option disabled value="">Pilih jenis hari</option>
                                <option value="full">Full Day</option>
                                <option value="half">Setengah Hari</option>
                                <option value="off">Tidak Masuk</option>
                            </select>
                        </div>

                        <div>
                            <Label>Jumlah (Rp)</Label>
                            <Input v-model="form.amount" type="number" />
                        </div>
                    </div>
                    <DialogFooter>
                        <div class="flex justify-end gap-2">
                            <Button type="button" variant="outline" @click="showCreateModal = false">Batal</Button>
                            <Button @click="handleCreate" class="bg-indigo-600 text-white hover:bg-indigo-700">Simpan</Button>
                        </div>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Edit Modal -->
            <Dialog v-model:open="showEditModal">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Uang Makan</DialogTitle>
                    </DialogHeader>
                    <div class="space-y-4">
                        <div>
                            <Label>Nama</Label>
                            <select
                                v-model="form.name"
                                class="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            >
                                <option disabled value="">Pilih jenis hari</option>
                                <option value="full">Full Day</option>
                                <option value="half">Setengah Hari</option>
                                <option value="off">Tidak Masuk</option>
                            </select>
                        </div>

                        <div>
                            <Label>Jumlah (Rp)</Label>
                            <Input v-model="form.amount" type="number" />
                        </div>
                    </div>
                    <DialogFooter>
                        <div class="flex justify-end gap-2">
                            <Button type="button" variant="outline" @click="showEditModal = false">Batal</Button>
                            <Button @click="handleUpdate" class="bg-indigo-600 text-white hover:bg-indigo-700">Update</Button>
                        </div>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { useMealAllowancesStore } from '@/stores/useMealAllowancesStore';
import { Head, useForm } from '@inertiajs/vue3';
import { Edit, Plus, Trash2 } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { computed, onMounted, ref } from 'vue';

const toast = useToast();
const showCreateModal = ref(false);
const showEditModal = ref(false);
const currentMeal = ref<{ id: number; name: string; amount: number } | null>(null);

const form = useForm({
    name: '',
    amount: 0,
});

const mealAllowancesStore = useMealAllowancesStore();
const { mealAllowances, currentPage, lastPage, loading, filterName } = storeToRefs(mealAllowancesStore);

const totalPages = computed(() => lastPage.value || 1);

const goToPage = async (page: number) => {
    if (page < 1 || page > totalPages.value) return;
    await mealAllowancesStore.fetchMealAllowances(page);
};
const nextPage = () => goToPage(currentPage.value + 1);
const prevPage = () => goToPage(currentPage.value - 1);

const onSearch = (e: Event) => {
    const target = e.target as HTMLInputElement;
    mealAllowancesStore.setFilter('name', target.value);
};

onMounted(() => {
    mealAllowancesStore.fetchMealAllowances();
});

const formatPrice = (value: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);

const handleCreate = async () => {
    if (!form.name) return toast.error('Nama wajib diisi');

    try {
        await mealAllowancesStore.createMealAllowance({
            name: form.name,
            amount: form.amount,
        });
        toast.success('Uang makan berhasil ditambahkan');
        form.reset();
        showCreateModal.value = false;
    } catch (error: any) {
        toast.error(error?.response?.data?.message || 'Gagal membuat uang makan');
    }
};

const handleEdit = (meal: { id: number; name: string; amount: number }) => {
    currentMeal.value = meal;
    form.name = meal.name;
    form.amount = meal.amount;
    showEditModal.value = true;
};

const handleUpdate = async () => {
    if (!currentMeal.value) return toast.error('Tidak ada data yang dipilih');

    try {
        await mealAllowancesStore.updateMealAllowance(currentMeal.value.id, {
            name: form.name,
            amount: form.amount,
        });
        toast.success('Uang makan berhasil diperbarui');
        form.reset();
        showEditModal.value = false;
        currentMeal.value = null;
    } catch (error: any) {
        toast.error(error?.response?.data?.message || 'Gagal memperbarui uang makan');
    }
};

const handleDelete = async (id: number) => {
    if (!confirm('Yakin ingin menghapus data ini?')) return;
    try {
        await mealAllowancesStore.deleteMealAllowance(id);
        toast.success('Uang makan berhasil dihapus');
    } catch (error: any) {
        toast.error(error?.response?.data?.message || 'Gagal menghapus uang makan');
    }
};
</script>
