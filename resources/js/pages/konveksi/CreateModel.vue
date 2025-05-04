<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import DocumentUpload from '@/components/DocumentUpload.vue';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import InputText from 'primevue/inputtext';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Konveksi', href: '/konveksi' },
    { title: 'Create Model', href: '/konveksi/model/create' },
];

const form = useForm({
    description: '',
    remark: '',
    start_date: null,
    estimation_price: 0,
});

const showUploadDialog = ref(false);
const uploadedDocuments = ref<any[]>([]);

const handleSubmit = () => {
    form.post('/konveksi/model', {
        onSuccess: () => {
            uploadedDocuments.value = [];
            showUploadDialog.value = false;
        },
    });
};

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
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tambahkan model baru dengan mengisi detail di bawah ini.</p>
            </div>

            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Description -->
                <div class="field">
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Description</label>
                    <Textarea 
                        v-model="form.description" 
                        id="description" 
                        rows="4" 
                        placeholder="Masukkan deskripsi model..." 
                        :class="{ 'p-invalid': form.errors.description }"
                        class="w-full rounded-lg transition-shadow focus:shadow-md" 
                    />
                    <small class="p-error" v-if="form.errors.description">{{ form.errors.description }}</small>
                </div>

                <!-- Two columns layout -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- Start Date -->
                    <div class="field">
                        <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Start Date</label>
                        <div class="mt-1">
                            <InputText
                                v-model="form.start_date"
                                id="start_date"
                                type="date"
                                :class="{ 'p-invalid': form.errors.start_date, 'w-full': true }"
                                class="w-full rounded-lg transition-shadow focus:shadow-md"
                            />
                            <small class="p-error" v-if="form.errors.start_date">{{ form.errors.start_date }}</small>
                        </div>
                    </div>

                    <!-- Estimation Price -->
                    <div class="field">
                        <label for="estimation_price" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Estimation Price</label>
                        <InputNumber 
                            v-model="form.estimation_price" 
                            id="estimation_price" 
                            mode="currency" 
                            currency="IDR" 
                            locale="id-ID" 
                            :class="{ 'p-invalid': form.errors.estimation_price }"
                            class="w-full" 
                        />
                        <small class="p-error" v-if="form.errors.estimation_price">{{ form.errors.estimation_price }}</small>
                    </div>
                </div>

                <!-- Remark -->
                <div class="field">
                    <label for="remark" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Remark</label>
                    <Textarea 
                        v-model="form.remark" 
                        id="remark" 
                        rows="3" 
                        placeholder="Tambahkan catatan tambahan..." 
                        :class="{ 'p-invalid': form.errors.remark }"
                        class="w-full rounded-lg transition-shadow focus:shadow-md" 
                    />
                    <small class="p-error" v-if="form.errors.remark">{{ form.errors.remark }}</small>
                </div>

                <!-- Upload Images -->
                <div class="field">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Model Images</label>
                    <div class="mt-3 space-y-4">
                        <Button 
                            type="button" 
                            icon="pi pi-upload" 
                            label="Upload Image" 
                            @click="handleUploadClick"
                            class="p-button-outlined" 
                        />

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
                                    icon="pi pi-times"
                                    class="absolute right-2 top-2 p-button-rounded p-button-danger p-button-sm opacity-0 transition-opacity group-hover:opacity-100"
                                    @click.prevent="removeDocument(index)"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex items-center justify-end space-x-3 border-t pt-6 dark:border-gray-700">
                    <Button 
                        type="button" 
                        label="Cancel" 
                        class="p-button-text" 
                        @click="router.visit('/konveksi')" 
                    />
                    <Button 
                        type="submit" 
                        label="Save Model" 
                        icon="pi pi-check" 
                        :loading="form.processing" 
                        class="p-button-primary" 
                    />
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
