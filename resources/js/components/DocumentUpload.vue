<template>
  <div>
    <!-- Upload Dialog -->
    <Dialog 
      v-model:visible="dialogVisible" 
      header="Upload File" 
      :modal="true"
      :dismissableMask="false"
      class="w-full max-w-3xl"
      contentStyle="padding: 0;"  
    >
      <!-- Wrapper: Flex column layout -->
      <div class="flex flex-col max-h-[80vh]">

        <!-- Scrollable content -->
        <div class="flex-1 overflow-y-auto p-6 space-y-4">
          <div 
            class="border-2 border-dashed rounded-lg p-4 text-center cursor-pointer hover:bg-gray-50 transition-colors"
            @click="triggerFileInput"
            @dragover.prevent
            @drop.prevent="handleFileDrop"
          >
            <input
              ref="fileInput"
              type="file"
              @change="handleFileChange"
              class="hidden"
              multiple
              :accept="accept || 'image/*'"
            />
            <div v-if="!selectedFiles.length">
              <Upload class="h-12 w-12 mx-auto text-gray-400 mb-2" />
              <p class="text-sm text-gray-500">Klik atau seret file untuk upload</p>
              <p class="text-xs text-gray-400 mt-1">Bisa pilih lebih dari satu file</p>
            </div>
            <div v-else class="text-left">
              <p class="text-sm font-medium mb-2">File yang dipilih:</p>
              <ul class="space-y-2">
                <li 
                  v-for="file in selectedFiles" 
                  :key="file.name" 
                  class="space-y-1"
                >
                  <div class="flex items-center justify-between gap-2">
                    <span class="text-sm text-gray-700 truncate">{{ file.name }}</span>
                    <Button 
                      variant="ghost" 
                      size="icon" 
                      @click.stop="removeSelectedFile(file)"
                      class="h-6 w-6"
                    >
                      <XCircle class="h-4 w-4" />
                    </Button>
                  </div>
                </li>
              </ul>
            </div>
          </div>

          <div v-if="isUploading" class="mt-4">
            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
              <div 
                class="h-full bg-primary transition-all duration-300"
                :style="{ width: `${uploadProgress}%` }"
              ></div>
            </div>
            <p class="text-sm text-gray-500 text-center mt-2">
              Mengupload... {{ uploadProgress }}%
            </p>
          </div>
        </div>

        <!-- Sticky footer -->
        <div class="border-t p-4 flex justify-end gap-2 bg-white">
          <Button variant="outline" @click="closeDialog" :disabled="isUploading">Batal</Button>
          <Button 
            @click="handleUpload" 
            :disabled="!selectedFiles.length || isUploading"
            :loading="isUploading"
          >
            Upload
          </Button>
        </div>
      </div>
    </Dialog>
  </div>
</template>


<script setup lang="ts">
import { ref, watch } from 'vue';
import { Upload, XCircle } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';
import { useDocumentUploadStore } from '@/stores/useDocumentUploadStore';

const props = defineProps<{
  visible: boolean;
  referenceId: string;
  docId: string;
  module?: string;
  accept?: string;
}>();

const emit = defineEmits<{
  'update:visible': [val: boolean];
  'uploaded': [doc: any];
}>();

const dialogVisible = ref(props.visible);
const fileInput = ref<HTMLInputElement | null>(null);
const selectedFiles = ref<File[]>([]);

const documentUploadStore = useDocumentUploadStore();
const isUploading = ref(false);
const uploadProgress = ref(0);

const triggerFileInput = () => {
  console.log('triggerFileInput called');
  fileInput.value?.click();
};

const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (target.files) {
    selectedFiles.value = Array.from(target.files);
    console.log('Selected files:', selectedFiles.value);
  }
};

const handleFileDrop = (e: DragEvent) => {
  const files = e.dataTransfer?.files;
  if (files?.length) {
    selectedFiles.value = Array.from(files);
  }
};

const removeSelectedFile = (file: File) => {
  selectedFiles.value = selectedFiles.value.filter(f => f !== file);
};

const handleUpload = async () => {
  console.log('handleUpload called');
  isUploading.value = true;
  for (const file of selectedFiles.value) {
    documentUploadStore.setFile(file);
    documentUploadStore.setUploadProgressCallback(p => uploadProgress.value = p);

    const result = await documentUploadStore.uploadDocument(
      props.referenceId,
      props.docId,
      props.module || 'general'
    );

    if (result) {
      console.log('Upload successful:', result.document);
      emit('uploaded', result.document);
    } else {
      console.error('Upload failed for file:', file.name);
    }
  }

  closeDialog();
  isUploading.value = false;
};

const closeDialog = () => {
  if (selectedFiles.value.length) {
    if (!confirm('Data akan hilang. Tutup dialog?')) return;
  }

  selectedFiles.value = [];
  uploadProgress.value = 0;
  isUploading.value = false;
  documentUploadStore.reset();
  dialogVisible.value = false;
};

watch(() => props.visible, val => {
  dialogVisible.value = val;
});
watch(dialogVisible, val => {
  emit('update:visible', val);
});
</script>
