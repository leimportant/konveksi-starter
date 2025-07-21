<script setup lang="ts">
import { storeToRefs } from 'pinia';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { MessageCircle } from 'lucide-vue-next';
import { useChatStore } from '@/stores/chatStore';
import type { BreadcrumbItemType } from '@/types';

// Props
const { breadcrumbs = [] } = defineProps<{
  breadcrumbs?: BreadcrumbItemType[];
}>();

// Chat Store
const chatStore = useChatStore();
const { unreadCount } = storeToRefs(chatStore);
const unreadMessageCount = unreadCount;

// Navigation

</script>

<template>
  <header
    class="bg-gray-100 fixed-top dark:bg-gray-50 flex h-16 shrink-0 items-center justify-between border-b border-sidebar-border/70 px-4 md:px-6"
  >
    <!-- Left: Sidebar & Breadcrumbs -->
    <div class="flex items-center gap-3">
      <SidebarTrigger class="-ml-1 bg-black-100" />
      <template v-if="breadcrumbs.length > 0">
        <Breadcrumbs :breadcrumbs="breadcrumbs" />
      </template>
    </div>

    <!-- Right: Message Icon -->
    <div class="flex items-center gap-6">
      <Button
        variant="ghost"
        size="icon"
        class="relative h-9 w-9 p-0 hover:bg-gray-200 transition"
         @click="$inertia.visit('/messages')"
      >
       <MessageCircle class="w-5 h-5 text-gray-700 relative" />
        <span
          v-if="unreadMessageCount > 0"
          class="absolute -top-0.5 -right-0.4 h-5 w-5 text-[11px] leading-none rounded-full bg-red-600 text-white flex items-center justify-center"
        >
          {{ unreadMessageCount }}
        </span>

      </Button>
    </div>
  </header>
</template>
