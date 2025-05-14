<template>
  <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', variantClasses]">
    <slot />
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'default',
    validator: (value: string) => ['default', 'secondary', 'destructive', 'outline'].includes(value)
  }
})

const variantClasses = computed(() => {
  const classes = {
    default: 'bg-primary text-primary-foreground',
    secondary: 'bg-secondary text-secondary-foreground',
    destructive: 'bg-destructive text-destructive-foreground',
    outline: 'border border-input bg-background text-foreground'
  }
  return classes[props.variant as keyof typeof classes]
})
</script>