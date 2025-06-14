<script setup lang="ts">
import { cn } from '@/lib/utils'

const props = withDefaults(
  defineProps<{
    class?: string
  }>(),
  {
    class: '',
  }
)
</script>

<template>
  <td :class="cn('p-4 align-middle [&:has([role=checkbox])]:pr-1 text-sm', props.class)">
    <slot />
  </td>
</template>
