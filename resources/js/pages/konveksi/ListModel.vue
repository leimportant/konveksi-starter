<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DateInput } from '@/components/ui/date-input';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { useModelStore } from '@/stores/useModelStore';
import { Head, router } from '@inertiajs/vue3';
import { Edit, Plus, Trash2 } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { onMounted, ref, watch } from 'vue';

const toast = useToast();
const modelStore = useModelStore();
const { models } = storeToRefs(modelStore);

const breadcrumbs = [
    { title: 'Konveksi', href: '/konveksi' },
    { title: 'List Model', href: '/konveksi/model' },
];

const searchQuery = ref('');
const startDate = ref<string | null>(null);
const endDate = ref<string | null>(null);

const fetchModels = async () => {
    try {
        await modelStore.fetchModels({
            search: searchQuery.value,
            start_date: startDate.value,
            end_date: endDate.value,
        });
    } catch (error: any) {
        if (error.response?.data?.errors) {
            console.error(error.response.data.errors);
            toast.error('Gagal ambil data');
        } else {
            toast.error('Terjadi kesalahan saat membuat model');
        }
    }
};

onMounted(() => {
    fetchModels();
});

watch(searchQuery, () => {
    fetchModels();
});

const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to delete this model?')) return;

    try {
        await modelStore.deleteModel(id);
        toast.success('Model deleted successfully');
        await fetchModels();
    } catch (error: any) {
        toast.error(error?.response?.data?.message ?? 'Failed to delete model');
    }
};

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price);
};

const formatDate = (date: string | null | undefined) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
};

// const handleSelect = (model: any) => {
//   router.visit(`/konveksi/model/${model.id}/edit`);
// };
</script>

<template>
    <Head title="List Model" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <!-- Filter & Action -->
            <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <Button
                        @click="router.visit('/konveksi/model/create')"
                        class="rounded-md bg-indigo-600 py-2 text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500"
                    >
                        <Plus class="mr-1 h-4 w-4" /> Tambah
                    </Button>
                
                <div class="mb-4 flex items-center gap-2 justify-between">
                    <div class="flex min-w-[18rem] items-center gap-2">
                    <DateInput v-model="startDate" placeholder="Start" class="min-w-[8rem]" />
                    <span class="text-gray-500">–</span>
                    <DateInput v-model="endDate" placeholder="End" class="min-w-[8rem]" />
                </div>
    
                    <Input v-model="searchQuery" placeholder="Cari data" class="w-64" />
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto border bg-white shadow-sm dark:bg-gray-900">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-gray-100">
                            <TableHead class="w-[300px]">Nama Model</TableHead>
                            <TableHead>Est Harga/Pcs</TableHead>
                            <TableHead>Est Qty</TableHead>
                            <TableHead class="hidden md:table-cell">Catatan</TableHead>
                            <TableHead class="text-right">Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="item in models" :key="item.id">
                            <TableCell class="font-medium text-foreground">
                                <span class="font-semibold">{{ item.description }}</span> <br />
                                <span class="font-xs text-gray-500">Mulai :{{ formatDate(item.start_date) }}</span>
                            </TableCell>
                            <TableCell>{{ formatPrice(item.estimation_price_pcs) }}</TableCell>
                            <TableCell>
                                <div v-if="item.sizes && item.sizes.length">
                                    <span v-for="(size, index) in item.sizes" :key="index" class="font-xs inline-block">
                                        {{ size.size_id }} ({{ size.qty }})<span v-if="index !== item.sizes.length - 1">, </span>
                                    </span>
                                </div>
                                <span v-else>-</span>
                            </TableCell>
                            <TableCell class="hidden text-muted-foreground md:table-cell">{{ item.remark || '-' }}</TableCell>
                            <TableCell class="space-x-1 text-right">
                                <Button size="icon" variant="ghost" @click.stop="router.visit(`/konveksi/model/${item.id}/edit`)">
                                    <Edit class="h-4 w-4" />
                                </Button>
                                <Button size="icon" variant="ghost" @click.stop="handleDelete(item.id)">
                                    <Trash2 class="h-4 w-4 text-red-500" />
                                </Button>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="!modelStore.loading && models.length === 0">
                            <TableCell colspan="6" class="py-6 text-center text-muted-foreground"> Tidak ada model yang ditemukan. </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <div v-if="modelStore.loading" class="p-4 text-center text-sm text-muted-foreground">Memuat data...</div>
            </div>
        </div>
    </AppLayout>
</template>
