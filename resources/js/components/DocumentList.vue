<template>
  <div class="document-list">
    <h3 class="text-lg font-semibold mb-4 p-2">List Gambar</h3>
    <div v-if="loading" class="text-center text-gray-500">Loading documents...</div>
    <div v-else-if="error" class="text-center text-red-500">Error loading documents: {{ error }}</div>
    <div v-else-if="documents.length === 0" class="text-center text-gray-500">No documents uploaded yet.</div>
    <ul v-else class="space-y-2">
      <li v-for="doc in documents" :key="doc.id" class="flex items-center justify-between p-2 border rounded-md">
        <a :href="doc.url" target="_blank" class="text-blue-600 hover:underline flex-grow truncate mr-4">
          {{ doc.filename }} ({{ doc.extension }})
        </a>
        <button @click="deleteDocument(doc.id)" class="text-red-500 hover:text-red-700">
          Delete
        </button>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';

interface Document {
  id: number;
  filename: string;
  path: string;
  extension: string;
  url: string;
  remark: string;
}

const documents = ref<Document[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

const fetchDocuments = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/api/document-attachments');
    documents.value = response.data.data;
  } catch (err: any) {
    error.value = err.message;
  } finally {
    loading.value = false;
  }
};

const deleteDocument = async (id: number) => {
  if (confirm('Are you sure you want to delete this document?')) {
    try {
      await axios.delete(`/api/document-attachments/${id}`);
      documents.value = documents.value.filter(doc => doc.id !== id);
    } catch (err: any) {
      alert('Failed to delete document: ' + err.message);
    }
  }
};

onMounted(() => {
  fetchDocuments();
});

defineExpose({
  fetchDocuments
});
</script>

<style scoped>
/* Add any specific styles here if needed */
</style>