<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { subscribeToPush } from '@/lib/push'; // pastikan path ini benar

const vapidPublicKey = import.meta.env.VITE_VAPID_PUBLIC_KEY as string; // Ambil dari .env

const isSubscribed = ref(false);
const message = ref('');

const handleSubscribe = async () => {
  try {
    if (!('serviceWorker' in navigator)) {
      message.value = 'Service Worker tidak didukung di browser ini.';
      return;
    }

    await subscribeToPush(vapidPublicKey);
    isSubscribed.value = true;
    message.value = 'Berhasil subscribe push notification.';
  } catch (err) {
    console.error('Subscription error:', err);
    message.value = 'Gagal subscribe. Cek console.';
  }
};
</script>

<template>
  <div class="p-4 space-y-4">
    <h1 class="text-xl font-bold">Push Notifications</h1>
    <p>Tekan tombol untuk subscribe ke notifikasi.</p>
    <Button @click="handleSubscribe" :disabled="isSubscribed">
      {{ isSubscribed ? 'Sudah Subscribe' : 'Subscribe Push' }}
    </Button>
    <p v-if="message" class="text-sm text-gray-600">{{ message }}</p>
  </div>
</template>
