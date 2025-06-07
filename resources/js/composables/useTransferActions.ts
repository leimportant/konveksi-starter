import { useTransferStockStore } from '@/stores/useTransferStockStore';
import { useToast } from '@/composables/useToast';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

export function useTransferActions() {
  const store = useTransferStockStore();
  const toast = useToast();
  const loading = ref(false);

  const acceptTransfer = async (id: string) => {
    loading.value = true;
    try {
      await store.fetchTransferByID(id);
      await store.acceptTransfer(id);
      toast.success('Transfer diterima');
      router.visit('/transfer-stock');
    } catch (err: any) {
      console.error(err);
      toast.error(err?.response?.data?.message ?? 'Gagal menerima transfer');
    } finally {
      loading.value = false;
    }
  };

  const rejectTransfer = async (id: string) => {
    loading.value = true;
    try {
      await store.fetchTransferByID(id);
      await store.rejectTransfer(id);
      toast.success('Transfer ditolak');
      router.visit('/transfer-stock');
    } catch (err: any) {
      console.error(err);
      toast.error(err?.response?.data?.message ?? 'Gagal menolak transfer');
    } finally {
      loading.value = false;
    }
  };

  return {
    acceptTransfer,
    rejectTransfer,
    loading,
  };
}