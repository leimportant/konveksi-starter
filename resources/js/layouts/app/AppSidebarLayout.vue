<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import AppBottomNavigation from '@/components/AppBottomNavigation.vue';
import type { BreadcrumbItemType } from '@/types';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

interface Props {
  breadcrumbs?: BreadcrumbItemType[];
}

// role_id is stored in a pivot table (user_role), and role 7 represents "customer"
const page = usePage();

const showBottomNavigation = computed(() => {
  const currentUrl = page.url;
  const user = page.props?.auth?.user;
  const isCustomer = user?.employee_status === "customer";

console.log(user.roles); // -> array of roles


  console.log('Current URL:', currentUrl);
  console.log('User Role ID:', user);

  return isCustomer && ['/home', '/order-history', '/order', '/settings/profile','/settings/password', 'messages', 'chatbot-ai'].includes(currentUrl);
});

withDefaults(defineProps<Props>(), {
  breadcrumbs: () => [],
});
</script>

<template>
  <AppShell variant="sidebar">
    <AppSidebar />

    <AppContent variant="sidebar">
      <!-- Fixed Header -->
      <div class="fixed top-0 left-0 right-0 z-30 bg-white border-b border-gray-200">
        <AppSidebarHeader :breadcrumbs="breadcrumbs" />
      </div>

      <!-- Padding top to prevent header overlap -->
      <div class="pt-[60px] pb-[60px]"> <!-- adjust these values to match your header/footer height -->
        <slot />
      </div>
    </AppContent>
  </AppShell>

  <!-- Fixed Bottom Navigation for Mobile -->
  <div class="fixed bottom-0 left-0 right-0 z-30 block md:hidden bg-white border-t border-gray-200">
    <AppBottomNavigation v-if="showBottomNavigation" />
  </div>
  <PWAInstallPrompt />
</template>
