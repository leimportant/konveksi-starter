<script setup lang="ts">
import { SidebarGroup, SidebarMenu, SidebarMenuItem } from '@/components/ui/sidebar';
import { useMenuStore } from '@/stores/useMenuStore';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const page = usePage<SharedData>(); // SharedData harus punya properti `url`
const menuStore = useMenuStore();

// Fetch hanya jika belum pernah dimuat
onMounted(() => {
    if (menuStore.items.length === 0) {
        menuStore.fetchMenus();
    }
});

const items = computed<NavItem[]>(() => menuStore.items); // Pastikan tipe sudah benar
const openMenus = ref<{ [key: string]: boolean }>({});

const toggleMenu = (menuTitle: string) => {
    openMenus.value[menuTitle] = !openMenus.value[menuTitle];
};
</script>

<template>
    <SidebarGroup class="px-3 py-2">
        <SidebarMenu class="space-y-1">
            <template v-for="item in items" :key="item.title">
                <!-- Menu with children -->
                <SidebarMenuItem v-if="item.children?.length" class="relative">
                    <div
                        class="flex w-full cursor-pointer items-center justify-between rounded-md px-2 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-800"
                        @click="toggleMenu(item.title)"
                    >
                        <div class="flex items-center space-x-3">
                            <component :is="item.icon" class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                            <span class="text-gray-700 dark:text-gray-200">{{ item.title }}</span>
                        </div>
                        <ChevronDown
                            class="h-4 w-4 text-gray-400 transition-transform duration-200 dark:text-gray-500"
                            :class="{ 'rotate-180': openMenus[item.title] }"
                        />
                    </div>

                    <!-- Level 1 Children -->
                    <SidebarMenu v-show="openMenus[item.title]" class="ml-2 mt-0">
                        <template v-for="child in item.children" :key="child.title">
                            <SidebarMenuItem v-if="child.children?.length" class="relative">
                                <div
                                    class="flex w-full cursor-pointer items-center justify-between rounded-md px-2 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-800"
                                    @click="toggleMenu(child.title)"
                                >
                                    <div class="flex items-center space-x-3">
                                        <component :is="child.icon" class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                                        <span class="text-gray-700 dark:text-gray-200">{{ child.title }}</span>
                                    </div>
                                    <ChevronDown
                                        class="h-4 w-2 text-gray-400 transition-transform duration-200 dark:text-gray-500"
                                        :class="{ 'rotate-180': openMenus[child.title] }"
                                    />
                                </div>

                                <!-- Level 2 Children -->
                                <SidebarMenu
                                    v-show="openMenus[child.title]"
                                    class="ml-3 mt-1 space-y-1 border-l border-gray-200 dark:border-gray-700"
                                >
                                    <SidebarMenuItem v-for="grandChild in child.children" :key="grandChild.title" class="relative">
                                        <Link
                                            :href="grandChild.href"
                                            as-child
                                            :is-active="item.href === page.url"
                                            :tooltip="item.title"
                                            class="flex w-full items-center space-x-3 rounded-md px-2 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800"
                                        >
                                            <component :is="grandChild.icon" class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                                            <span>{{ grandChild.title }}</span>
                                        </Link>
                                    </SidebarMenuItem>
                                </SidebarMenu>
                            </SidebarMenuItem>

                            <!-- Child without children -->
                            <SidebarMenuItem v-else class="relative">
                                <Link
                                    :href="child.href"
                                    class="flex w-full items-center space-x-3 rounded-md px-2 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800"
                                >
                                    <component :is="child.icon" class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                                    <span>{{ child.title }}</span>
                                </Link>
                            </SidebarMenuItem>
                        </template>
                    </SidebarMenu>
                </SidebarMenuItem>

                <!-- Menu without children -->
                <SidebarMenuItem v-else class="relative">
                    <Link
                        :href="item.href"
                        class="flex w-full items-center space-x-3 rounded-md px-2 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800"
                    >
                        <component :is="item.icon" class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>
