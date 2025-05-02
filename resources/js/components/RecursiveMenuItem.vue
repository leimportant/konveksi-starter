<script setup lang="ts">
import { SidebarMenu, SidebarMenuItem, SidebarMenuButton } from '@/components/ui/sidebar';
import { Link } from '@inertiajs/vue3';
import type { NavItem, SharedData } from '@/types';

const props = defineProps<{
  item: NavItem;
  page: SharedData;  // Correctly type the page prop
}>();

const { item, page } = props;
</script>

<template>
  <SidebarMenuItem>
    <SidebarMenuButton 
      as-child 
      :is-active="item.href === page.url" 
      :tooltip="item.title"
      class="hover:bg-gray-700 hover:text-white active:bg-gray-600"
    >
      <Link :href="item.href || '#'" class="flex items-center space-x-2">
        <component :is="item.icon" v-if="item.icon" class="text-xl"/>
        <span>{{ item.title }}</span>
      </Link>
    </SidebarMenuButton>

    <!-- Recursive rendering for children -->
    <SidebarMenu v-if="item.children && item.children.length" class="ml-4">
      <RecursiveMenuItem 
        v-for="child in item.children" 
        :key="child.title" 
        :item="child" 
        :page="page"
        class="transition-all duration-300 ease-in-out"
      />
    </SidebarMenu>
  </SidebarMenuItem>
</template>
