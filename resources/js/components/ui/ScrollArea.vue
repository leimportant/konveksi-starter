<!-- src/components/ui/ScrollArea.vue -->
<template>
  <div class="overflow-y-auto" :class="customClass" ref="container">
    <slot />
  </div>
</template>

<script setup lang="ts">
defineProps<{ customClass?: string }>();
</script>
