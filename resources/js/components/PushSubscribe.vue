<template>
  <div>
    <button @click="subscribe" :disabled="store.isSubscribed">
      {{ store.isSubscribed ? 'Subscribed!' : 'Enable Push Notifications' }}
    </button>
    <p v-if="store.error" class="text-red-500">{{ store.error }}</p>

    <button @click="send" :disabled="!store.isSubscribed">
      Send Notification
    </button>
  </div>
</template>

<script setup lang="ts">
import { usePushNotificationStore } from '@/stores/usePushNotificationStore';
const store = usePushNotificationStore();

function subscribe() {
  store.subscribe();
}

function send() {
  store.send();
}
</script>
