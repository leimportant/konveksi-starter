<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { useSidebarStore } from '@/stores/useSidebarStore'

const sidebarNavItems: NavItem[] = [
    {
        id: 1,
        title: 'Profile',
        href: '/settings/profile',
    },
    {
        id: 2,
        title: 'Password',
        href: '/settings/password',
    },
];

const page = usePage();

const sidebarStore = useSidebarStore()

const currentPath = page.props.ziggy?.location
  ? new URL(page.props.ziggy.location).pathname
  : '';
  
</script>

<template>
    <div class="px-4 py-4">
        <Heading title="Settings" description="Manage your profile and account settings" />

        <div class="flex flex-col space-y-8 md:space-y-0 lg:flex-row lg:space-x-12 lg:space-y-0">
           <aside 
                        class="sidebar transition-all duration-300 ease-in-out"
                        :class="{ 'w-64': !sidebarStore.isCollapsed, 'w-16': sidebarStore.isCollapsed }"
                    >
                <nav class="flex flex-col space-x-0 space-y-1">
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="item.href"
                        variant="ghost"
                        :class="['w-full justify-start text-sm', { 'bg-muted': currentPath === item.href }]"
                        as-child
                    >
                        <Link :href="item.href">
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 md:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
