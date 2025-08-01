<script setup lang="ts">
import Sheet from '@/components/ui/sheet/Sheet.vue';
import SheetContent from '@/components/ui/sheet/SheetContent.vue';
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';
import { SIDEBAR_WIDTH_MOBILE, useSidebar } from './utils';

defineOptions({
  inheritAttrs: false,
});

const props = withDefaults(
  defineProps<{
    side?: 'left' | 'right';
    variant?: 'sidebar' | 'floating' | 'inset';
    collapsible?: 'offcanvas' | 'icon' | 'none';
    class?: HTMLAttributes['class'];
  }>(),
  {
    side: 'left',
    variant: 'sidebar',
    collapsible: 'offcanvas',
  }
);

const { isMobile, state, openMobile, setOpenMobile } = useSidebar();
</script>

<template>
  <!-- Non-collapsible sidebar -->
  <div
    v-if="collapsible === 'none'"
    :class="cn(
      'flex h-full flex-col bg-sidebar text-sidebar-foreground',
      'w-[--sidebar-width] max-w-[240px] md:max-w-[260px] lg:max-w-[280px]',
      props.class
    )"
    style="--sidebar-width: 220px;"
    v-bind="$attrs"
  >
    <slot />
  </div>

  <!-- Mobile Sheet Sidebar -->
  <Sheet v-else-if="isMobile" :open="openMobile" @update:open="setOpenMobile" v-bind="$attrs">
    <SheetContent
      data-sidebar="sidebar"
      data-mobile="true"
      :side="side"
      class="w-[--sidebar-width] bg-sidebar  bg-white/100 px-2 py-3 text-sidebar-foreground [&>button]:hidden"
      :style="{ '--sidebar-width': SIDEBAR_WIDTH_MOBILE || '200px' }"
    >
      <div class="flex h-full w-full flex-col overflow-auto">
        <slot />
      </div>
    </SheetContent>
  </Sheet>

  <!-- Desktop Sidebar -->
  <div
    v-else
    class="group peer hidden md:block"
    :data-state="state"
    :data-collapsible="state === 'collapsed' ? collapsible : ''"
    :data-variant="variant"
    :data-side="side"
  >
    <!-- Sidebar ghost width container -->
    <div
      :class="cn(
        'relative h-svh bg-transparent transition-[width] duration-200 ease-linear',
        'w-[220px] md:w-[240px] lg:w-[260px]',
        'group-data-[collapsible=offcanvas]:w-0',
        'group-data-[side=right]:rotate-180',
        variant === 'floating' || variant === 'inset'
          ? 'group-data-[collapsible=icon]:w-[calc(var(--sidebar-width-icon)_+_theme(spacing.4))]'
          : 'group-data-[collapsible=icon]:w-[--sidebar-width-icon]'
      )"
    />

    <!-- Real Sidebar Content -->
    <div
      :class="cn(
        'fixed inset-y-0 z-10 hidden h-svh transition-[left,right,width] duration-200 ease-linear md:flex',
        'w-[220px] md:w-[240px] lg:w-[260px]',
        side === 'left'
          ? 'left-0 group-data-[collapsible=offcanvas]:left-[calc(var(--sidebar-width)*-1)]'
          : 'right-0 group-data-[collapsible=offcanvas]:right-[calc(var(--sidebar-width)*-1)]',
        variant === 'floating' || variant === 'inset'
          ? 'p-2 group-data-[collapsible=icon]:w-[calc(var(--sidebar-width-icon)_+_theme(spacing.4)_+1px)]'
          : 'group-data-[collapsible=icon]:w-[--sidebar-width-icon] group-data-[side=left]:border-r group-data-[side=right]:border-l',
        props.class,
        'flex items-center justify-start whitespace-nowrap overflow-hidden'
      )"
      v-bind="$attrs"
    >
      <div
        data-sidebar="sidebar"
        class="flex h-full w-full flex-col overflow-auto bg-sidebar bg-white/100 group-data-[variant=floating]:rounded-lg group-data-[variant=floating]:border group-data-[variant=floating]:border-sidebar-border group-data-[variant=floating]:shadow"
      >
        <slot />
      </div>
    </div>
  </div>
</template>
