<script setup lang="ts">
defineProps<{
  position?: 'popper' | 'item-aligned'
  sideOffset?: number
}>()
</script>

<template>
  <div class="z-50 min-w-[8rem] overflow-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-md">
    <slot />
  </div>
</template>