<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import DocumentUpload from '@/components/DocumentUpload.vue';
import { Button } from '@/components/ui/button';
import { DateInput } from '@/components/ui/date-input';
import { Input } from '@/components/ui/input';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { useModelStore } from '@/stores/useModelStore';
import { useToast } from "@/composables/useToast";

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Konveksi', href: '/konveksi' },
    { title: 'Create Model', href: '/konveksi/model/create' },
];

const modelStore = useModelStore();
const form = useForm({
    description: '',
    remark: '',
    start_date: null,
    estimation_price_pcs: 0,
    estimation_qty: 1,
});

const errors = ref<Record<string, string[]>>({});

const toast = useToast();

const handleSubmit = async () => {
    try {
        if (!form.start_date) {
            errors.value = {
                ...errors.value,
                start_date: ['Tanggal mulai harus diisi']
            };
            toast.error("Tanggal mulai harus diisi");
            return;
        }

        await modelStore.createModel({
            description: form.description,
            remark: form.remark,
            estimation_price_pcs: form.estimation_price_pcs,
            estimation_qty: form.estimation_qty,
            start_date: form.start_date
        });
        
        toast.success("Model berhasil dibuat");
        router.visit('/konveksi/model/list');
    } catch (error: any) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
            toast.error("Validasi gagal, silakan periksa kembali form anda");
        } else {
            toast.error("Terjadi kesalahan saat membuat model");
        }
    }
};

const showUploadDialog = ref(false);
const uploadedDocuments = ref<any[]>([]);

const handleUploadClick = () => {
    showUploadDialog.value = true;
};

const handleDocumentUploaded = (document: any) => {
    uploadedDocuments.value.push(document);
};

const removeDocument = (index: number) => {
    uploadedDocuments.value.splice(index, 1);
};
</script>

<template>
    <Head title="Create Model" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
            <div class="border-b pb-4 dark:border-gray-700">
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Buat Model Baru</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tambahkan model baru dengan mengisi detail di bawah ini.</p>
            </div>

            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Description -->
                <div class="field">
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Deskripsi</label>
                    <Input 
                        v-model="form.description" 
                        id="description" 
                        placeholder="Masukkan deskripsi model..." 
                        :class="{ 'border-destructive': errors.description }"
                    />
                    <small class="text-destructive" v-if="errors.description">{{ errors.description[0] }}</small>
                </div>

                <!-- Two columns layout -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- Start Date -->
                    <div class="field">
                        <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Tanggal Mulai</label>
                        <DateInput
                            type="date"
                            v-model="form.start_date"
                            id="start_date"
                            :class="{ 'border-destructive': errors.start_date }"
                        />
                        <small class="text-destructive" v-if="errors.start_date">{{ errors.start_date[0] }}</small>
                    </div>

                    <!-- Estimation Price -->
                    <div class="field">
                        <label for="estimation_price_pcs" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Estimasi Harga</label>
                        <Input
                            type="number"
                            v-model="form.estimation_price_pcs" 
                            id="estimation_price_pcs" 
                            :class="{ 'border-destructive': errors.estimation_price_pcs }"
                            class="w-full" 
                        />
                        <small class="text-destructive" v-if="errors.estimation_price_pcs">{{ errors.estimation_price_pcs[0] }}</small>
                    </div>
                </div>

                <!-- Remark -->
                <div class="field">
                    <label for="remark" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Catatan</label>
                    <Input 
                        v-model="form.remark" 
                        id="remark" 
                        placeholder="Tambahkan catatan tambahan..." 
                        :class="{ 'border-destructive': form.errors.remark }"
                    />
                    <small class="text-destructive" v-if="form.errors.remark">{{ form.errors.remark }}</small>
                </div>

                <!-- Upload Images -->
                <div class="field">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Gambar Model</label>
                    <div class="mt-3 space-y-4">
                        <Button 
                            type="button" 
                            variant="outline"
                            @click="handleUploadClick"
                        >
                            <i class="pi pi-upload" /> Upload Gambar
                        </Button>

                        <div v-if="uploadedDocuments.length > 0" class="grid grid-cols-2 gap-4 md:grid-cols-4">
                            <div v-for="(doc, index) in uploadedDocuments" 
                                :key="doc.id" 
                                class="group relative overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700"
                            >
                                <img 
                                    :src="doc.url" 
                                    :alt="doc.filename" 
                                    class="aspect-square w-full object-cover transition-transform duration-300 group-hover:scale-105" 
                                />
                                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-3">
                                    <p class="text-xs text-white truncate">{{ doc.filename }}</p>
                                </div>
                                <Button
                                    variant="destructive"
                                    size="icon"
                                    class="absolute right-2 top-2 opacity-0 transition-opacity group-hover:opacity-100"
                                    @click.prevent="removeDocument(index)"
                                >
                                    <i class="pi pi-times" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex items-center justify-end space-x-3 border-t pt-6 dark:border-gray-700">
                    <Button 
                        type="button" 
                        variant="secondary"
                        @click="router.visit('/konveksi')"
                    >
                        Batal
                    </Button>
                    <Button 
                        type="submit" 
                        :loading="form.processing"
                    >
                        <i class="pi pi-check" /> Simpan
                    </Button>
                </div>
            </form>
        </div>

        <!-- Upload Dialog -->
        <DocumentUpload
            v-model:visible="showUploadDialog"
            reference-id="MODEL"
            :doc-id="'MDL-' + Date.now()"
            module="model"
            accept="image/*"
            @uploaded="handleDocumentUploaded"
        />
    </AppLayout>
</template>
