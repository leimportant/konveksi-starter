<template>
    <div>
      <Dialog v-model:visible="dialogVisible" :header="title || 'Upload Document'" :modal="true">
        <div class="p-fluid">
          <div class="field">
            <label for="file">File</label>
            <input
              type="file"
              @change="handleFileChange"
              class="p-inputtext"
              :accept="accept || 'image/*'"
            />
            <small v-if="documentUploadStore.fileError" class="p-error">
              {{ documentUploadStore.fileError }}
            </small>
            <small v-if="documentUploadStore.selectedFile" class="text-gray-600">
              Selected: {{ documentUploadStore.selectedFile.name }}
            </small>
          </div>
  
          <div class="field">
            <label for="remark">Remark</label>
            <InputText
              v-model="form.remark"
              placeholder="Enter remark"
              :class="{ 'p-invalid': documentUploadStore.remarkError }"
            />
            <small v-if="documentUploadStore.remarkError" class="p-error">
              {{ documentUploadStore.remarkError }}
            </small>
          </div>
        </div>
  
        <template #footer>
          <Button
            label="Cancel"
            icon="pi pi-times"
            @click="closeDialog"
            class="p-button-text"
            :disabled="documentUploadStore.isUploading"
          />
          <Button
            label="Upload"
            icon="pi pi-upload"
            @click="handleUpload"
            :loading="documentUploadStore.isUploading"
            :disabled="!documentUploadStore.selectedFile"
          />
        </template>
      </Dialog>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, defineProps, defineEmits, watch } from 'vue'
  import { useForm } from '@inertiajs/vue3'
  import { useDocumentUploadStore } from '@/stores/useDocumentUploadStore'
  
  interface Props {
    visible: boolean
    title?: string
    referenceId: string
    docId: string
    module?: string
    accept?: string
  }
  
  const props = defineProps<Props>()
  const emit = defineEmits<{
    'update:visible': [value: boolean]
    'uploaded': [document: any]
  }>()
  
  const dialogVisible = ref(props.visible)
  
  // Sync from parent
  watch(() => props.visible, val => {
    dialogVisible.value = val
  })
  
  // Sync to parent
  watch(dialogVisible, val => {
    emit('update:visible', val)
  })
  
  const documentUploadStore = useDocumentUploadStore()
  
  const form = useForm({
    remark: '',
    module: props.module || 'general'
  })
  
  const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement
    if (target.files) {
      documentUploadStore.setFile(target.files[0])
    }
  }
  
  const handleUpload = async () => {
    const response = await documentUploadStore.uploadDocument(
      props.referenceId,
      props.docId,
      form.remark,
      form.module
    )
  
    if (response) {
      emit('uploaded', response.document)
      closeDialog()
    }
  }
  
  const closeDialog = () => {
    documentUploadStore.reset()
    form.reset()
    dialogVisible.value = false
  }
  </script>
  