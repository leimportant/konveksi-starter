<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

const assistantUrl = import.meta.env.VITE_ASSISTANT_URL;
</script>


<template>
   <Head title="Assistant Chat" />
    <AppLayout>
      <div class="w-full h-screen">
        <iframe 
           :src="assistantUrl || 'http://localhost:3000'"
          class="w-full h-full border-0"
        ></iframe>
      </div>
    </AppLayout>
  
</template>
