<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
  class?: HTMLAttributes['class'];
  collapsed?: boolean;
}>();
</script>

<template>
  <div
    data-sidebar="group"
    :class="cn(
      'relative flex flex-col min-w-0 transition-all duration-200 ease-in-out',
      props.collapsed ? 'w-16 p-1' : 'w-60 p-2',
      props.class
    )"
  >
    <slot />
  </div>
</template>
