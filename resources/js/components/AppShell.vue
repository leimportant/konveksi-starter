<script setup lang="ts">
import { SidebarProvider } from '@/components/ui/sidebar';
import { onMounted, ref } from 'vue';

interface Props {
  variant?: 'header' | 'sidebar';
}

defineProps<Props>();

const isOpen = ref(true);

onMounted(() => {
  isOpen.value = localStorage.getItem('sidebar') !== 'false';
});

const handleSidebarChange = (open: boolean) => {
  isOpen.value = open;
  localStorage.setItem('sidebar', String(open));
};
</script>

<template>
  <!-- HEADER VARIANT: No sidebar, full width layout -->
  <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
    <slot />
  </div>

  <!-- SIDEBAR VARIANT -->
  <SidebarProvider
    v-else
    :default-open="isOpen"
    :open="isOpen"
    @update:open="handleSidebarChange"
  >
    <!-- Root wrapper: full height -->
    <div class="flex min-h-screen w-full flex-col md:flex-row">
      
      <!-- SIDEBAR (desktop only) -->
      <aside
        class="hidden md:flex w-[250px] shrink-0 flex-col border-r bg-sidebar text-sidebar-foreground"
      >
        <slot name="sidebar" />
      </aside>

      <!-- MAIN CONTENT AREA -->
      <div class="flex flex-1 flex-col relative">
        <!-- Optional HEADER -->
        <slot name="header" />

        <!-- SCROLLABLE MAIN CONTENT -->
        <main class="flex-1 overflow-auto p-4 pb-[60px] bg-background">
          <slot />
        </main>

        <!-- BOTTOM NAV (mobile only) -->
        <div
          class="fixed bottom-0 left-0 right-0 z-50 md:hidden bg-white border-t shadow-lg"
        >
          <slot name="bottom" />
        </div>
      </div>
    </div>
  </SidebarProvider>
</template>
