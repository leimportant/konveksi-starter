<script setup lang="ts">
import { SidebarInset } from '@/components/ui/sidebar';
import { computed } from 'vue';

interface Props {
    variant?: 'header' | 'sidebar';
    class?: string;
}

const props = defineProps<Props>();
const className = computed(() => props.class);
</script>

<template>
    <SidebarInset v-if="props.variant === 'sidebar'" :class="className">
        <slot />
    </SidebarInset>
    <main  v-else
    :class="[
      'mx-auto w-full flex flex-1 flex-col gap-2 rounded-lg px-2 py-2',
      'sm:px-4 sm:py-3',
      'md:px-6 md:py-4',
      'lg:px-8 lg:py-6',
      'xl:px-10 xl:py-8 max-w-7xl',
      className
    ]">
        <slot />
    </main>
</template>
