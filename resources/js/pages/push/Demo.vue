<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PushNotificationDemo from '@/components/PushNotificationDemo.vue';

defineProps<{
  vapidPublicKey: string;
}>();
</script>

<template>
  <Head title="Push Notifications Demo" />
  
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <PushNotificationDemo />
        </div>
      </div>
    </div>
  </div>
</template>