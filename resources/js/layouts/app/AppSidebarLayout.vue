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

  return isCustomer && ['/home', '/order-history', '/order'].includes(currentUrl);
});

withDefaults(defineProps<Props>(), {
  breadcrumbs: () => [],
});
</script>

<template>
  <AppShell variant="sidebar">
    <AppSidebar />
    <AppContent variant="sidebar">
      <AppSidebarHeader :breadcrumbs="breadcrumbs" />
      <slot /> 
      <div class="pb-16 md:pb-0"></div> 
    </AppContent>
  </AppShell>

  <!-- Show bottom navigation only on mobile and for customer role -->
  <div class="block md:hidden">
    <AppBottomNavigation v-if="showBottomNavigation" />
  </div>
</template>
