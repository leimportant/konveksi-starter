import { defineStore } from 'pinia';
import { ref } from 'vue';

interface Size {
  id: number;
  name: string;
}

export const useSizeStore = defineStore('size', () => {
  const sizes = ref<Size[]>([]);
  const isLoading = ref(false);
  const error = ref<string | null>(null);

  async function fetchSizes() {
    try {
      isLoading.value = true;
      error.value = null;
      const res = await fetch('/api/sizes');
      const json = await res.json();
      sizes.value = json.data;
    } catch (err) {
      error.value = 'Failed to fetch sizes';
      console.error('Error fetching sizes:', err);
    } finally {
      isLoading.value = false;
    }
  }

  return {
    sizes,
    isLoading,
    error,
    fetchSizes
  };
});