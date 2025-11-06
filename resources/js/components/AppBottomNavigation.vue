<script setup lang="ts">
import { Home, ShoppingCart, User2Icon } from 'lucide-vue-next';
import { Link, usePage } from '@inertiajs/vue3';

const currentUrl = usePage().url;
const cleanPath = (url: string) => url.split('?')[0].replace(/\/$/, '');
const isActive = (path: string) => cleanPath(currentUrl) === path;
</script>

<template>
  <div class="fixed bottom-0 left-0 right-0 bg-white shadow-md border-t border-gray-200 z-[999]">
    <nav class="flex justify-around items-center h-16 max-w-md mx-auto px-2">

      <!-- Home -->
      <Link
        href="/home"
        class="flex flex-col items-center justify-center px-4 py-2"
        :class="isActive('/home') 
          ? 'text-green-600 font-semibold' 
          : 'text-gray-500'"
      >
        <Home class="h-5 w-5" />
        <span class="text-xs mt-1">Home</span>
      </Link>

      <!-- Order -->
      <Link
        href="/order-history"
        class="flex flex-col items-center justify-center px-4 py-2"
        :class="isActive('/order-history') 
          ? 'text-green-600 font-semibold' 
          : 'text-gray-500'"
      >
        <ShoppingCart class="h-5 w-5" />
        <span class="text-xs mt-1">Order</span>
      </Link>

      <!-- Profile -->
      <Link
        href="/settings/profile"
        class="flex flex-col items-center justify-center px-4 py-2"
        :class="isActive('/settings/profile') 
          ? 'text-green-600 font-semibold' 
          : 'text-gray-500'"
      >
        <User2Icon class="h-5 w-5" />
        <span class="text-xs mt-1">Profile</span>
      </Link>

      <!-- Chatbot AI -->

      <!-- <Link
        href="/assistant-ai"
        class="flex flex-col items-center justify-center px-4 py-2"
        :class="isActive('/chatbot-ai') 
          ? 'text-green-600 font-semibold' 
          : 'text-gray-500'"
      >
        <MessageCircleHeartIcon class="h-5 w-5" />
        <span class="text-xs mt-1">Chat Assistant</span>
      </Link> -->


    </nav>
  </div>
</template>
