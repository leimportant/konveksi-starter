<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
  class?: HTMLAttributes['class'];
}>();
</script>

<template>
  <div
    data-sidebar="content"
    :class="cn(
      // Base layout
      'flex flex-1 flex-col gap-2 min-h-0 min-h-screen flex flex-col',
      // Responsiveness when collapsed
      'group-data-[collapsible=icon]:overflow-hidden group-data-[collapsible=icon]:items-center group-data-[collapsible=icon]:gap-0',
      // Optional classes
      props.class
    )"
  >
    <slot />
  </div>
</template>
