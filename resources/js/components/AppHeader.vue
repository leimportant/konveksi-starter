<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { NavigationMenu, NavigationMenuList } from '@/components/ui/navigation-menu';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { getInitials } from '@/composables/useInitials';

// Remove the conflicting import
// import { resolveIcon } from '@/composables/useIcons';
import type { BreadcrumbItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { LucideIcon } from 'lucide-vue-next';
import { withDefaults, type Component } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

interface AuthUser {
    name?: string;
    avatar?: string;
}

const page = usePage();
const auth = page.props.auth as { user?: AuthUser };

const resolveIcon = async (iconName: string | LucideIcon | undefined): Promise<Component | null> => {
    if (!iconName) return null;
    const iconMap: Record<string, () => Promise<Component>> = {
        Menu: () => import('lucide-vue-next').then((m) => m.Menu),
        Search: () => import('lucide-vue-next').then((m) => m.Search),
    };
    const iconLoader =
        iconMap[typeof iconName === 'string' ? iconName : iconName.name] ||
        (() => import('lucide-vue-next').then((m) => m[typeof iconName === 'string' ? iconName : iconName.name]));
    const icon = await iconLoader();
    return icon || null;
};
</script>

<template>
    <div>
        
        <div class="fixed border-b border-sidebar-border/0">
            
            <div class="fixed left-0 right-0 top-0 z-[999] mx-auto flex h-4 items-center bg-background px-2 md:max-w-7xl">
                <!-- Mobile Menu -->
                <div class="lg:hidden">
                    <Sheet>
                        <SheetTrigger as-child>
                            <Button variant="ghost" size="icon" class="mr-2 h-9 w-9">
                                <component :is="resolveIcon('Menu')" class="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" class="w-[300px] p-6">
                            <SheetTitle class="sr-only">Navigation Menu</SheetTitle>
                            <SheetHeader class="flex justify-start text-left">
                                <AppLogoIcon class="size-6 fill-current text-black dark:text-white" />
                            </SheetHeader>
                            <div class="flex h-full flex-1 flex-col justify-between space-y-4 py-4"></div>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link :href="route('dashboard')" class="flex items-center gap-x-2">
                    <AppLogo />
                </Link>

                <!-- Desktop Menu -->
                <div class="flex flex-col hidden h-full lg:flex lg:flex-1">
                    
                    <NavigationMenu class="ml-10 flex h-full items-stretch">
                        <NavigationMenuList class="flex h-full items-stretch space-x-2"> </NavigationMenuList>
                    </NavigationMenu>
                </div>

                <div class="ml-auto flex items-center space-x-2">
                    <Button variant="ghost" size="icon" class="group h-9 w-9 cursor-pointer">
                        <component :is="resolveIcon('Search')" class="size-5 opacity-80 group-hover:opacity-100" />
                    </Button>

                

                    <!-- <div class="hidden space-x-1 lg:flex">
           
          </div> -->

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                            >
                                <Avatar class="size-8 overflow-hidden rounded-full">
                                    <AvatarImage v-if="auth.user?.avatar" :src="auth.user.avatar" alt="auth.user.name ?? 'User Avatar'" />
                                    <AvatarFallback class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white">
                                        {{ getInitials(auth.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <!-- Tambahkan konten dropdown user di sini -->
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>

        <div v-if="props.breadcrumbs.length > 1" class="flex w-full border-b border-sidebar-border/70">
           
            <div class="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500 md:max-w-7xl">
                <Breadcrumbs :breadcrumbs="props.breadcrumbs" />
            </div>
        </div>
    </div>
</template>
