<script setup lang="ts">
import { usePWAStore } from '@/stores/usePWAStore';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { computed } from 'vue';

const pwaStore = usePWAStore();

const showDialog = computed(() => pwaStore.showInstallPrompt);

const installApp = () => {
  pwaStore.installPWA();
};

const closeDialog = () => {
  pwaStore.hideInstallPrompt();
};
</script>

<template>
  <Dialog :open="showDialog" @update:open="closeDialog">
    <DialogContent
      class="w-[90%] max-w-sm sm:max-w-md p-6 rounded-2xl shadow-xl bg-white dark:bg-gray-900"
    >
      <DialogHeader class="text-center space-y-2">
        <DialogTitle class="text-lg sm:text-xl font-semibold">
          Install Aninka Fashion
        </DialogTitle>
        <DialogDescription class="text-sm text-gray-500 dark:text-gray-400">
          Dapatkan pengalaman lebih cepat dengan menginstal aplikasi ini di perangkatmu.
        </DialogDescription>
      </DialogHeader>

      <DialogFooter class="flex justify-between pt-4 gap-2">
        <Button
          @click="closeDialog"
          variant="outline"
          class="flex-1 text-sm py-2"
        >
          Nanti saja
        </Button>
        <Button
          @click="installApp"
          class="flex-1 text-sm py-2 bg-blue-600 hover:bg-blue-700 text-white"
        >
          Install
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<style scoped>
/* Optional enhancement for small screens */
@media (max-width: 480px) {
  .dialog-content {
    padding: 1rem !important;
  }
}
</style>
