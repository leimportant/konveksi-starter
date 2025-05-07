import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useDocumentUploadStore = defineStore('documentUpload', () => {
  const file = ref<File | null>(null);
  const remark = ref('');
  const fileError = ref('');
  const remarkError = ref('');
  const isUploading = ref(false);
  const uploadProgress = ref(0);

  // Optional callback to sync with component
  let progressCallback: ((progress: number) => void) | null = null;

  const setFile = (f: File) => {
    file.value = f;
    fileError.value = '';
  };

  const setRemark = (r: string) => {
    remark.value = r;
    remarkError.value = '';
  };

  const setUploadProgressCallback = (cb: (progress: number) => void) => {
    progressCallback = cb;
  };

  const uploadDocument = async (
    referenceId: string,
    docId: string,
    remarkValue: string,
    module: string
  ) => {
    if (!file.value) {
      fileError.value = 'File tidak boleh kosong';
      return null;
    }

    if (!remarkValue.trim()) {
      remarkError.value = 'Keterangan tidak boleh kosong';
      return null;
    }

    isUploading.value = true;
    uploadProgress.value = 0;

    try {
      const formData = new FormData();
      formData.append('file', file.value);
      formData.append('remark', remarkValue);
      formData.append('reference_id', referenceId);
      formData.append('doc_id', docId);
      formData.append('module', module);

      const response = await fetch('/api/documents/upload', {
        method: 'POST',
        body: formData,
      });

      // Simulate progress manually
      let fakeProgress = 0;
      const progressInterval = setInterval(() => {
        fakeProgress += 10;
        uploadProgress.value = fakeProgress;
        if (progressCallback) progressCallback(fakeProgress);

        if (fakeProgress >= 100) clearInterval(progressInterval);
      }, 100);

      if (!response.ok) throw new Error('Upload gagal');

      const json = await response.json();
      return json;
    } catch (error) {
      console.error(error);
      return null;
    } finally {
      isUploading.value = false;
    }
  };

  const removeFile = async (referenceId: string, documentId: string) => {
    try {
      const res = await fetch(`/api/documents/${referenceId}/${documentId}`, {
        method: 'DELETE',
      });
      return res.ok;
    } catch {
      return false;
    }
  };

  const reset = () => {
    file.value = null;
    remark.value = '';
    fileError.value = '';
    remarkError.value = '';
    uploadProgress.value = 0;
    isUploading.value = false;
    progressCallback = null;
  };

  return {
    file,
    remark,
    fileError,
    remarkError,
    isUploading,
    uploadProgress,
    setFile,
    setRemark,
    uploadDocument,
    removeFile,
    reset,
    setUploadProgressCallback,
  };
});
