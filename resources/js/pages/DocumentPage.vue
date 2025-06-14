<template>
  <div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-6">Document Management</h1>

    <div class="mb-8">
      <!-- <Button @click="showUploadDialog = true">Upload New Document</Button> -->
      <DocumentUpload
        :visible="showUploadDialog"
        @update:visible="showUploadDialog = $event"
        @uploaded="handleDocumentUploaded"
        referenceId="123" 
        docId="456" 
      />
    </div>

    <DocumentList ref="documentListRef" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import DocumentUpload from '@/components/DocumentUpload.vue';
import DocumentList from '@/components/DocumentList.vue';
// import { Button } from '@/components/ui/button';

const showUploadDialog = ref(false);
const documentListRef = ref<InstanceType<typeof DocumentList & { fetchDocuments: () => void }> | null>(null);

const handleDocumentUploaded = () => {
  showUploadDialog.value = false;
  // Refresh the list of documents after a successful upload
  if (documentListRef.value) {
    documentListRef.value.fetchDocuments();
  }
};
</script>

<style scoped>
/* Add any specific styles here if needed */
</style>