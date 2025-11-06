<script setup lang="ts">
import { ref, computed, onMounted, type Component } from 'vue';
import { Link } from '@inertiajs/vue3';
import { type NavItem } from '@/types';
import { SidebarGroup, SidebarMenu, SidebarMenuItem } from '@/components/ui/sidebar';
import { useMenuStore } from '@/stores/useMenuStore';
import axios from 'axios';
import { ChevronDown } from 'lucide-vue-next';

// Icons lazy loader
const iconMap: Record<string, () => Promise<Component>> = { LayoutGrid: () => import('lucide-vue-next').then(m => m.LayoutGrid), List: () => import('lucide-vue-next').then(m => m.List), Ruler: () => import('lucide-vue-next').then(m => m.Ruler), Maximize: () => import('lucide-vue-next').then(m => m.Maximize), Package: () => import('lucide-vue-next').then(m => m.Package), LayoutDashboard: () => import('lucide-vue-next').then(m => m.LayoutDashboard), Shirt: () => import('lucide-vue-next').then(m => m.Shirt), ShoppingCart: () => import('lucide-vue-next').then(m => m.ShoppingCart), CreditCard: () => import('lucide-vue-next').then(m => m.CreditCard), Tag: () => import('lucide-vue-next').then(m => m.Tag), DollarSign: () => import('lucide-vue-next').then(m => m.DollarSign), Warehouse: () => import('lucide-vue-next').then(m => m.Warehouse), Settings: () => import('lucide-vue-next').then(m => m.Settings), Boxes: () => import('lucide-vue-next').then(m => m.Boxes), UserPlus: () => import('lucide-vue-next').then(m => m.UserPlus), Users: () => import('lucide-vue-next').then(m => m.Users), Shield: () => import('lucide-vue-next').then(m => m.Shield), House: () => import('lucide-vue-next').then(m => m.House), Repeat: () => import('lucide-vue-next').then(m => m.Repeat), FileText: () => import('lucide-vue-next').then(m => m.FileText), FileSearch: () => import('lucide-vue-next').then(m => m.FileSearch), BadgeCent: () => import('lucide-vue-next').then(m => m.BadgeCent), BookCheck: () => import('lucide-vue-next').then(m => m.BookCheck), BookOpen: () => import('lucide-vue-next').then(m => m.BookOpen), BadgeCentIcon: () => import('lucide-vue-next').then(m => m.BadgeCentIcon), Receipt: () => import('lucide-vue-next').then(m => m.Receipt), DollarSignIcon: () => import('lucide-vue-next').then(m => m.DollarSignIcon), CreditCardIcon: () => import('lucide-vue-next').then(m => m.CreditCardIcon), };

const iconCache: Record<string, Component> = {};
const loadedIcons = ref<Record<string, Component>>({});

async function loadIcon(iconName: string): Promise<Component | null> {
  if (!iconName) return null;
  if (iconCache[iconName]) return iconCache[iconName];

  const loader = iconMap[iconName];
  if (!loader) return null;

  try {
    const iconComp = await loader();
    iconCache[iconName] = iconComp;
    loadedIcons.value = { ...loadedIcons.value, [iconName]: iconComp };
    return iconComp;
  } catch {
    return null;
  }
}

async function preloadIcons(items: NavItem[]) {
  for (const item of items) {
    if (item.icon && !loadedIcons.value[item.icon]) {
      await loadIcon(item.icon);
    }
    if (item.children?.length) {
      await preloadIcons(item.children);
    }
  }
}

const menuStore = useMenuStore();
const badgeCounts = ref<{ [key: string]: number }>({ orders: 0, customers: 0 });
const openMenus = ref<Record<string, boolean>>({});

function toggleMenu(title: string) {
  openMenus.value[title] = !openMenus.value[title];
}

async function fetchBadgeCounts() {
  try {
    const res = await axios.get('/api/sidebar-badge-counts');
    badgeCounts.value = res.data;
  } catch (err) {
    console.error('Failed to fetch badge counts', err);
  }
}

onMounted(async () => {
  if (menuStore.items.length === 0) await menuStore.fetchMenus();
  await preloadIcons(menuStore.items);
  fetchBadgeCounts();
});

const items = computed(() => menuStore.items);
</script>

<template>
  <SidebarGroup class="px-3 py-2 text-gray-700 dark:text-gray-200 overflow-y-auto max-h-[calc(100vh-4rem)]">
    <SidebarMenu class="space-y-1">
      <template v-for="item in items" :key="item.title">
        <SidebarMenuItem class="relative">
          <!-- Top-level with children -->
          <div v-if="item.children?.length"
            class="flex w-full cursor-pointer items-center justify-between rounded-md px-2 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-800"
            @click="toggleMenu(item.title)"
          >
            <div class="flex items-center space-x-3">
              <component v-if="item.icon && loadedIcons[item.icon]" :is="loadedIcons[item.icon]"
                class="h-4 w-4 text-gray-500 dark:text-gray-400" />
              <span class="flex items-center gap-1 text-gray-700 dark:text-gray-200">
                {{ item.title }}
                <span v-if="item.href === '/order-request' || item.href === '/customers'"
                  class="ml-1 rounded-full bg-red-500 px-2 py-0.5 text-xs font-bold text-white">
                  {{ item.href === '/order-request' ? badgeCounts.orders : badgeCounts.customers }}
                </span>
              </span>
            </div>
            <ChevronDown class="h-4 w-4 text-gray-400 transition-transform duration-200 dark:text-gray-500"
              :class="{ 'rotate-180': openMenus[item.title] }" />
          </div>

          <!-- Top-level without children -->
          <Link v-else :href="item.href"
            class="flex w-full items-center space-x-3 rounded-md px-2 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-500 dark:hover:bg-gray-800"
          >
            <component v-if="item.icon && loadedIcons[item.icon]" :is="loadedIcons[item.icon]"
              class="h-4 w-4 text-gray-500 dark:text-gray-400" />
            <span>{{ item.title }}</span>
          </Link>

          <!-- Children -->
          <SidebarMenu v-if="item.children?.length" v-show="openMenus[item.title]" class="ml-2 mt-1 space-y-1">
            <template v-for="child in item.children" :key="child.title">
              <SidebarMenuItem class="relative">
                <div v-if="child.children?.length"
                  class="flex w-full cursor-pointer items-center justify-between rounded-md px-2 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-800"
                  @click="toggleMenu(child.title)"
                >
                  <div class="flex items-center space-x-3">
                    <component v-if="child.icon && loadedIcons[child.icon]" :is="loadedIcons[child.icon]"
                      class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                    <span class="text-gray-700 dark:text-gray-200">{{ child.title }}</span>
                  </div>
                  <ChevronDown class="h-4 w-4 text-gray-400 transition-transform duration-200 dark:text-gray-500"
                    :class="{ 'rotate-180': openMenus[child.title] }" />
                </div>

                <Link v-else :href="child.href"
                  class="flex w-full items-center space-x-3 rounded-md px-2 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-500 dark:hover:bg-gray-800"
                >
                  <component v-if="child.icon && loadedIcons[child.icon]" :is="loadedIcons[child.icon]"
                    class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                  <span>{{ child.title }}</span>
                  <span v-if="child.href === '/order-request' || child.href === '/customers'"
                    class="ml-1 rounded-full bg-red-500 px-2 py-0.5 text-xs font-bold text-white">
                    {{ child.href === '/order-request' ? badgeCounts.orders : badgeCounts.customers }}
                  </span>
                </Link>

                <!-- Grandchildren -->
                <SidebarMenu v-if="child.children?.length" v-show="openMenus[child.title]"
                  class="ml-3 mt-1 space-y-1 border-l border-gray-200 dark:border-gray-700"
                >
                  <SidebarMenuItem v-for="grandChild in child.children" :key="grandChild.title" class="relative">
                    <Link :href="grandChild.href"
                      class="flex w-full items-center space-x-3 rounded-md px-2 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-500 dark:hover:bg-gray-800"
                    >
                      <component v-if="grandChild.icon && loadedIcons[grandChild.icon]" :is="loadedIcons[grandChild.icon]"
                        class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                      <span>{{ grandChild.title }}</span>
                    </Link>
                  </SidebarMenuItem>
                </SidebarMenu>
              </SidebarMenuItem>
            </template>
          </SidebarMenu>
        </SidebarMenuItem>
      </template>
    </SidebarMenu>
  </SidebarGroup>
</template>
