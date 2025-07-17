<script setup lang="ts">
import { Home, ShoppingCart, User2Icon } from 'lucide-vue-next';
import { Link, usePage } from '@inertiajs/vue3';

// Ambil URL saat ini
const currentUrl = usePage().url;
const cleanPath = (url: string) => url.split('?')[0].replace(/\/$/, '');
const isActive = (path: string) => cleanPath(currentUrl) === path;
</script>

<template>
  <div class="fixed bottom-0 left-0 right-0 bg-indigo-600 shadow-lg border-t border-indigo-700 z-[999]">
    <nav class="flex justify-around items-center h-16 max-w-md mx-auto px-2">

      <!-- Home Tab -->
      <Link
        href="/home"
        class="flex flex-col items-center justify-center px-4 py-2 rounded-md transition-all duration-200"
        :class="isActive('/home') 
          ? 'bg-indigo-600 text-white-500 text-opacity-50 font-bold shadow-md' 
          : 'text-white/60 hover:text-white'"
      >
        <Home class="h-5 w-5" />
        <span class="text-xs mt-1">Home</span>
      </Link>

      <!-- Order Tab -->
      <Link
        href="/order-history"
        class="flex flex-col items-center justify-center px-4 py-2 rounded-md transition-all duration-200"
        :class="isActive('/order-history') 
          ? 'bg-indigo-600 text-white-500 text-opacity-50 font-bold shadow-md' 
          : 'text-white/60 hover:text-white'"
      >
        <ShoppingCart class="h-5 w-5" />
        <span class="text-xs mt-1">Order</span>
      </Link>

       <!-- Order Tab -->
      <Link
        href="/settings/profile"
        class="flex flex-col items-center justify-center px-4 py-2 rounded-md transition-all duration-200"
        :class="isActive('/settings/profile') 
          ? 'bg-indigo-600 text-white-500 text-opacity-50 font-bold shadow-md' 
          : 'text-white/60 hover:text-white'"
      >
        <User2Icon class="h-5 w-5" />
        <span class="text-xs mt-1">Profile</span>
      </Link>

    </nav>
  </div>
</template>
