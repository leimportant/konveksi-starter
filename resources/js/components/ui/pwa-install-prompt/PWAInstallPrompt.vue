<script setup lang="ts">
import { usePWAStore } from '@/stores/usePWAStore';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
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
    <DialogContent>
      <DialogHeader>
        <DialogTitle>Install Aninka Fashion App</DialogTitle>
        <DialogDescription>
          Add Aninka Fashion to your home screen for quick and easy access!
        </DialogDescription>
      </DialogHeader>
      <DialogFooter>
        <Button @click="closeDialog" variant="outline">Maybe later</Button>
        <Button @click="installApp">Install</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>