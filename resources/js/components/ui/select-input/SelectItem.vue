<script setup lang="ts">
const props = defineProps<{
  value: string | number
  disabled?: boolean
}>()
</script>

<template>
  <option :value="value" :disabled="disabled" class="relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-2 pr-8 text-sm outline-none focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50">
    <slot />
  </option>
</template>