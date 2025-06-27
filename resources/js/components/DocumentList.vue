<template>
  <div>
    <!-- Upload -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <span class="text-sm text-gray-500">Format yang didukung: JPG, PNG, GIF, MP4</span>
      </div>
      <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
        <input
          type="file"
          accept="image/*,video/*"
          @change="onFileChange"
          ref="fileInput"
          class="block text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
        />

      </div>
    </div>

    <!-- Progress -->
    <div v-if="uploadProgress > 0 && uploadProgress < 100" class="w-full bg-gray-200 rounded-full h-2.5 mb-4">
      <div class="bg-blue-600 h-2.5 rounded-full" :style="{ width: uploadProgress + '%' }"></div>
    </div>

    <!-- Daftar -->
    <div class="mt-8">
      <div v-if="loading" class="text-center text-gray-500 py-4">
        <svg class="animate-spin h-5 w-5 text-gray-500 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
        Memuat media...
      </div>

      <div v-else-if="error" class="text-center text-red-500">Terjadi kesalahan: {{ error }}</div>
      <div v-else-if="documents.length === 0" class="text-center text-gray-500">Belum ada media yang diunggah.</div>

      <ul v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <li
          v-for="doc in documents"
          :key="doc.id"
          class="relative border rounded-md p-3 flex flex-col gap-2 bg-white shadow hover:shadow-lg transition"
        >
          <template v-if="isImage(doc.extension)">
            <img
              :src="getImageUrl(doc.path)"
              :alt="doc.filename"
              class="w-full h-40 object-cover rounded cursor-pointer transition hover:scale-105"
              @click="openImage(getImageUrl(doc.path))"
            />
          </template>

          <template v-else-if="isVideo(doc.extension)">
            <video
              :src="getImageUrl(doc.path)"
              controls
              class="w-full h-40 object-cover rounded"
            ></video>
          </template>

          <div class="text-xs text-gray-600 truncate">{{ doc.filename }}</div>

          <button
            @click="deleteDocument(doc.id)"
            class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white text-sm p-1 rounded-full shadow transition duration-200"
  aria-label="Hapus"
          >
            <Trash class="w-5 h-5" />
          </button>
        </li>
      </ul>

      <!-- Modal -->
      <div
        v-if="previewImage"
        class="fixed inset-0 bg-black bg-opacity-80 flex flex-col items-center justify-center z-50 p-4"
        @click.self="previewImage = null"
      >
        <img :src="previewImage" class="max-h-[80vh] max-w-[90vw] rounded shadow-lg" />
        <button
          @click="previewImage = null"
          class="mt-4 px-4 py-2 bg-white text-black rounded shadow hover:bg-gray-200"
        >
          Tutup
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Trash } from 'lucide-vue-next';
import { useToast } from '@/composables/useToast';

interface Document {
  id: number;
  filename: string;
  path: string;
  extension: string;
  url: string;
  remark: string;
}

const props = defineProps<{
  referenceId: number | string | null;
  referenceType: string;
}>();

const documents = ref<Document[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const previewImage = ref<string | null>(null);
const uploadProgress = ref(0);
const uploading = ref(false);

const fetchDocuments = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/api/document-attachments', {
      params: {
        reference_id: props.referenceId,
        reference_type: props.referenceType,
      },
    });
    documents.value = response.data.data;
  } catch (err: any) {
    error.value = err.message || 'Gagal memuat media';
  } finally {
    loading.value = false;
  }
};

const onFileChange = async (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (!file) return;

  if (!props.referenceId) {
    useToast().error('ID Referensi tidak tersedia. Silakan pilih data yang benar.');
    return;
  }

  // Validasi ukuran maksimal (100MB)
  if (file.size > 100 * 1024 * 1024) {
    useToast().error('Ukuran file terlalu besar. Maksimal 100MB.');
    return;
  }

  const formData = new FormData();
  formData.append('file', file);
  formData.append('reference_id', String(props.referenceId));
  formData.append('reference_type', props.referenceType);
  formData.append('remark', '');

  uploading.value = true;
  uploadProgress.value = 0;

  try {
    const response = await axios.post('/api/document-attachments/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (event) => {
        if (event.total) {
          uploadProgress.value = Math.round((event.loaded / event.total) * 100);
        }
      },
    });
    documents.value.push(response.data);
    useToast().success('Media berhasil diunggah.');
  } catch (err: any) {
    useToast().error(err.response?.data?.message || 'Gagal mengunggah media.');
  } finally {
    uploading.value = false;
    uploadProgress.value = 0;
    target.value = ''; // Reset input file
  }
};

const deleteDocument = async (id: number) => {
  if (!confirm('Yakin ingin menghapus media ini?')) return;
  try {
    await axios.delete(`/api/document-attachments/${id}`);
    documents.value = documents.value.filter((doc) => doc.id !== id);
    useToast().success('Media berhasil dihapus.');
  } catch (err: any) {
    useToast().error(err.response?.data?.message || 'Gagal menghapus media.');
  }
};

const getImageUrl = (path: string) => {
  if (!path) return '';
  if (path.startsWith('storage/')) return '/' + path;
  if (path.startsWith('/storage/')) return path;
  return '/storage/' + path;
};

const isImage = (ext: string) => ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext.toLowerCase());
const isVideo = (ext: string) => ['mp4', 'webm', 'ogg'].includes(ext.toLowerCase());
const openImage = (src: string) => (previewImage.value = src);

onMounted(fetchDocuments);
defineExpose({ fetchDocuments });
</script>
