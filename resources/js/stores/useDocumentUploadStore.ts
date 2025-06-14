import { defineStore } from 'pinia';
import axios from 'axios';
import { ref } from 'vue';

export const useDocumentUploadStore = defineStore('documentUpload', () => {
  const file = ref<File | null>(null);
  const fileError = ref('');
  const isUploading = ref(false);
  const uploadProgress = ref(0);

  let progressCallback: ((progress: number) => void) | null = null;

  const setFile = (f: File) => {
    file.value = f;
    fileError.value = '';
  };

  const setUploadProgressCallback = (cb: (progress: number) => void) => {
    progressCallback = cb;
  };

  const uploadDocument = async (
    referenceId: string,
    docId: string,
    module: string
  ) => {
    if (!file.value) {
      fileError.value = 'File tidak boleh kosong';
      return null;
    }

    isUploading.value = true;
    uploadProgress.value = 0;

    const formData = new FormData();
    formData.append('file', file.value);
    formData.append('reference_id', referenceId);
    formData.append('doc_id', docId);
    formData.append('module', module);

    try {
      const response = await axios.post('/api/document-attachments/upload', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
        onUploadProgress: (progressEvent) => {
          const percent = Math.round((progressEvent.loaded * 100) / (progressEvent.total || 1));
          uploadProgress.value = percent;
          if (progressCallback) progressCallback(percent);
        },
      });

      return response.data;
    } catch (error: any) {
      console.error('Upload failed:', error);
      fileError.value = error.response?.data?.message || 'Upload gagal';
      return null;
    } finally {
      isUploading.value = false;
    }
  };

  const removeFile = async (id: string) => {
    try {
      const response = await axios.delete(`/api/document-attachments/${id}`);
      return response.status === 200;
    } catch {
      return false;
    }
  };

  const reset = () => {
    file.value = null;
    fileError.value = '';
    uploadProgress.value = 0;
    isUploading.value = false;
    progressCallback = null;
  };

  return {
    file,
    fileError,
    isUploading,
    uploadProgress,
    setFile,
    uploadDocument,
    removeFile,
    reset,
    setUploadProgressCallback,
  };
});
